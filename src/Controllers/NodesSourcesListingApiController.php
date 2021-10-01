<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractApiTheme\OptionsResolver\ApiRequestOptionsResolver;

class NodesSourcesListingApiController extends AbstractNodeTypeApiController
{
    protected function getListingType(array $options): string
    {
        if ($options['node.nodeType'] instanceof NodeTypeInterface) {
            return $options['node.nodeType']->getSourceEntityFullQualifiedClassName();
        }
        if (is_array($options['node.nodeType']) &&
            count($options['node.nodeType']) === 1 &&
            $options['node.nodeType'][0] instanceof NodeTypeInterface) {
            return $options['node.nodeType'][0]->getSourceEntityFullQualifiedClassName();
        }
        return NodesSources::class;
    }

    /**
     * @param Request $request
     *
     * @return Response|JsonResponse
     * @throws \Exception
     */
    public function defaultAction(Request $request): Response
    {
        /** @var ApiRequestOptionsResolver $apiOptionsResolver */
        $apiOptionsResolver = $this->get(ApiRequestOptionsResolver::class);
        $options = $apiOptionsResolver->resolve($request->query->all(), null);
        $this->translation = $this->getTranslationFromLocaleOrRequest($request, $options['_locale']);

        $defaultCriteria = [
            'translation' => $this->getTranslation(),
        ];

        $criteria = array_merge(
            $defaultCriteria,
            $apiOptionsResolver->getCriteriaFromOptions($options)
        );

        return $this->getEntityListManagerResponse(
            $request,
            null,
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
            $this->getListingType($options),
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
