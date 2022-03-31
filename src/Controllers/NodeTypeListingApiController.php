<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractApiTheme\OptionsResolver\ApiRequestOptionsResolver;
use Themes\AbstractApiTheme\OptionsResolver\NodeTypeApiRequestOptionResolverInterface;

class NodeTypeListingApiController extends AbstractNodeTypeApiController
{
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

        /** @var NodeTypeApiRequestOptionResolverInterface $apiOptionsResolver */
        $apiOptionsResolver = $this->get(ApiRequestOptionsResolver::class);
        /** @var PreviewResolverInterface $previewResolver */
        $previewResolver = $this->get(PreviewResolverInterface::class);
        $options = $apiOptionsResolver->resolve($request->query->all(), $nodeType);
        $this->translation = $this->getTranslationFromLocaleOrRequest($request, $options['_locale']);

        $defaultCriteria = [
            'translation' => $this->getTranslation(),
        ];

        if ($nodeType->isPublishable() && !$previewResolver->isPreview()) {
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
        $entityListManager->setItemPerPage((int) $options['itemsPerPage']);
        $entityListManager->setPage((int) $options['page']);
        $entityListManager->handle();

        /** @var SerializerInterface $serializer */
        $serializer = $this->get('serializer');
        $context = $this->getSerializationContext($options);
        $context
            ->setAttribute('request', $request)
            ->setAttribute('nodeType', $nodeType)
        ;

        return $this->getJsonResponse(
            $serializer->serialize(
                $entityListManager,
                'json',
                $context
            ),
            $context,
            $request,
            $this->get('api.cache.ttl')
        );
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
