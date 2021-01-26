<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractApiTheme\Cache\CacheTagsCollection;
use Themes\AbstractApiTheme\OptionsResolver\ApiRequestOptionsResolver;

class NodeTypeSingleApiController extends AbstractNodeTypeApiController
{
    protected function getSerializationGroups(): array
    {
        return [
            'walker', // rezozero tree-walker
            'children', // rezozero tree-walker
            'nodes_sources',
            'document_display',
            'url_alias',
            'tag_base',
            'urls',
            'meta',
        ];
    }

    /**
     * @param Request $request
     * @param int     $nodeTypeId
     * @param int     $id
     * @param string|null $_locale
     *
     * @return Response|JsonResponse
     * @throws \Exception
     */
    public function defaultAction(Request $request, int $nodeTypeId, int $id, ?string $_locale = null): Response
    {
        $nodeType = $this->getNodeTypeOrDeny($nodeTypeId);

        /** @var ApiRequestOptionsResolver $apiOptionsResolver */
        $apiOptionsResolver = $this->get(ApiRequestOptionsResolver::class);
        if (null !== $_locale) {
            $queryAll = array_merge($request->query->all(), [
                '_locale' => $_locale
            ]);
        } else {
            $queryAll = $request->query->all();
        }
        $options = $apiOptionsResolver->resolve($queryAll, $nodeType);

        $this->getTranslationOrNotFound($options['_locale']);

        $criteria = [
            'node.nodeType' => $nodeType,
            'node.id' => $id,
            'translation' => $this->getTranslation(),
        ];

        if ($nodeType->isPublishable()) {
            $criteria['publishedAt'] = ['<=', new \DateTime()];
        }

        return $this->getNodesSourcesResponse($request, $criteria);
    }

    /**
     * @param Request $request
     * @param int $nodeTypeId
     * @param string $slug
     * @return Response
     * @throws \Exception
     */
    public function bySlugAction(Request $request, int $nodeTypeId, string $slug): Response
    {
        /** @var NodeType|null $nodeType */
        $nodeType = $this->get('em')->find(NodeType::class, $nodeTypeId);
        /** @var ApiRequestOptionsResolver $apiOptionsResolver */
        $apiOptionsResolver = $this->get(ApiRequestOptionsResolver::class);
        $options = $apiOptionsResolver->resolve($request->query->all(), $nodeType);

        if (null === $nodeType) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessNodeTypeGranted($nodeType);

        $this->getTranslationOrNotFound($options['_locale']);

        /*
         * Get a routing resource array
         */
        $array = $this->get('em')->getRepository(Node::class)
            ->findNodeTypeNameAndSourceIdByIdentifier(
                $slug,
                $this->getTranslation(),
                true // only find available translations
            );

        if (null === $array) {
            throw $this->createNotFoundException();
        }

        $criteria = [
            'node.nodeType' => $nodeType,
            'id' => $array['id'],
            'translation' => $this->getTranslation(),
        ];

        if ($nodeType->isPublishable()) {
            $criteria['publishedAt'] = ['<=', new \DateTime()];
        }

        return $this->getNodesSourcesResponse($request, $criteria);
    }

    /**
     * @param Request $request
     * @param array $criteria
     * @return Response
     */
    protected function getNodesSourcesResponse(Request $request, array &$criteria): Response
    {
        /** @var NodesSources|null $nodeSource */
        $nodeSource = $this->get('nodeSourceApi')->getOneBy($criteria);

        if (null === $nodeSource || null === $nodeSource->getNode()) {
            throw $this->createNotFoundException();
        }

        /** @var SerializerInterface $serializer */
        $serializer = $this->get('serializer');
        $context = $this->getSerializationContext();
        $response = new JsonResponse(
            $serializer->serialize(
                $nodeSource,
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

        return $this->makeResponseCachable(
            $request,
            $response,
            $nodeSource->getNode()->getTtl() ?? $this->get('api.cache.ttl')
        );
    }

    /**
     * @param NodeType $nodeType
     * @return void
     */
    protected function denyAccessUnlessNodeTypeGranted(NodeType $nodeType): void
    {
        // TODO: implement your own access-control logic for each node-type.
        // $this->denyAccessUnlessScopeGranted([strtolower($nodeType->getName())]);
    }
}
