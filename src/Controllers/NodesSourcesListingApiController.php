<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractApiTheme\OptionsResolver\ApiRequestOptionsResolver;

class NodesSourcesListingApiController extends AbstractNodeTypeApiController
{
    protected function getSerializationGroups(): array
    {
        return [
            'nodes_sources_base',
            'tag_base',
            'nodes_sources_default',
            'urls',
            'meta',
        ];
    }

    protected function getListingType(array $options): string
    {
        if ($options['node.nodeType'] instanceof NodeType) {
            return $options['node.nodeType']->getSourceEntityFullQualifiedClassName();
        }
        if (is_array($options['node.nodeType']) &&
            count($options['node.nodeType']) === 1 &&
            $options['node.nodeType'][0] instanceof NodeType) {
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

        /** @var Translation|null $translation */
        $translation = $this->get('em')->getRepository(Translation::class)->findOneByLocale($options['_locale']);
        if (null === $translation) {
            throw $this->createNotFoundException();
        }

        $defaultCriteria = [
            'translation' => $translation,
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
     * @param NodeType|null $nodeType
     * @param array $criteria
     * @param array $options
     * @return Response
     */
    protected function getEntityListManagerResponse(
        Request $request,
        ?NodeType $nodeType,
        array &$criteria,
        array &$options
    ): Response {
        $entityListManager = $this->createEntityListManager(
            $this->getListingType($options),
            $criteria,
            null !== $options['order'] ? $options['order'] : []
        );
        $entityListManager->setItemPerPage($options['itemsPerPage']);
        $entityListManager->setPage($options['page']);
        $entityListManager->handle();

        /** @var SerializerInterface $serializer */
        $serializer = $this->get('serializer');
        $response = new JsonResponse(
            $serializer->serialize(
                $entityListManager,
                'json',
                $this->getSerializationContext()
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );

        return $this->makeResponseCachable($request, $response, $this->get('api.cache.ttl'));
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
