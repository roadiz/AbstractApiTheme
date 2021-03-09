<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractApiTheme\Cache\CacheTagsCollection;
use Themes\AbstractApiTheme\OptionsResolver\ApiRequestOptionsResolver;

class NodeTypeListingApiController extends AbstractNodeTypeApiController
{
    protected function getSerializationGroups(): array
    {
        return [
            'nodes_sources_base',
            'document_display',
            'thumbnail',
            'tag_base',
            'nodes_sources_default',
            'urls',
            'meta',
        ];
    }

    protected function getListingType(?NodeTypeInterface $nodeType): string
    {
        return $nodeType ? $nodeType->getSourceEntityFullQualifiedClassName() : NodesSources::class;
    }

    /**
     * @param Request $request
     * @param int     $nodeTypeId
     *
     * @return Response|JsonResponse
     * @throws \Exception
     */
    public function defaultAction(Request $request, int $nodeTypeId): Response
    {
        $nodeType = $this->getNodeTypeOrDeny($nodeTypeId);

        /** @var ApiRequestOptionsResolver $apiOptionsResolver */
        $apiOptionsResolver = $this->get(ApiRequestOptionsResolver::class);
        $options = $apiOptionsResolver->resolve($request->query->all(), $nodeType);

        $this->getTranslationOrNotFound($options['_locale']);

        $defaultCriteria = [
            'translation' => $this->getTranslation(),
        ];

        if ($nodeType->isPublishable()) {
            $defaultCriteria['publishedAt'] = ['<=', new \DateTime()];
        }

        $criteria = array_merge(
            $defaultCriteria,
            $apiOptionsResolver->getCriteriaFromOptions($options)
        );

        return $this->getEntityListManagerResponse(
            $request,
            $nodeType,
            $criteria,
            $options
        );
    }

    /**
     * @param Request $request
     * @param NodeTypeInterface|null $nodeType
     * @param array $criteria
     * @param array $options
     * @return Response
     */
    protected function getEntityListManagerResponse(
        Request $request,
        ?NodeTypeInterface $nodeType,
        array &$criteria,
        array &$options
    ): Response {
        $entityListManager = $this->createEntityListManager(
            $this->getListingType($nodeType),
            $criteria,
            null !== $options['order'] ? $options['order'] : []
        );
        $entityListManager->setItemPerPage($options['itemsPerPage']);
        $entityListManager->setPage($options['page']);
        $entityListManager->handle();

        /** @var SerializerInterface $serializer */
        $serializer = $this->get('serializer');
        $context = $this->getSerializationContext($options);
        $context
            ->setAttribute('request', $request)
            ->setAttribute('nodeType', $nodeType)
        ;
        $response = new JsonResponse(
            $serializer->serialize(
                $entityListManager,
                'json',
                $context
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );

        if ($context->hasAttribute('cache-tags') &&
            $context->getAttribute('cache-tags') instanceof CacheTagsCollection) {
            /** @var CacheTagsCollection $cacheTags */
            $cacheTags = $context->getAttribute('cache-tags');
            if ($cacheTags->count() > 0) {
                $response->headers->add([
                    'X-Cache-Tags' => implode(', ', $cacheTags->toArray())
                ]);
            }
        }

        $this->injectAlternateHrefLangLinks($request, $nodeType);

        return $this->makeResponseCachable($request, $response, $this->get('api.cache.ttl'));
    }

    /**
     * @param NodeTypeInterface $nodeType
     * @return void
     */
    protected function denyAccessUnlessNodeTypeGranted(NodeTypeInterface $nodeType): void
    {
        // TODO: implement your own access-control logic for each node-type.
        // $this->denyAccessUnlessScopeGranted([strtolower($nodeType->getName())]);
    }
}
