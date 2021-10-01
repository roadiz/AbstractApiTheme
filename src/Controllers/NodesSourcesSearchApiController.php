<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
use RZ\Roadiz\Core\SearchEngine\NodeSourceSearchHandlerInterface;
use RZ\Roadiz\Core\SearchEngine\SearchHandlerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Themes\AbstractApiTheme\ListManagers\SolrSearchListManager;
use Themes\AbstractApiTheme\OptionsResolver\SearchApiRequestOptionsResolver;

class NodesSourcesSearchApiController extends AbstractNodeTypeApiController
{
    protected function denyAccessUnlessNodeTypeGranted(NodeTypeInterface $nodeType): void
    {
        // Override denyAccessUnlessNodeTypeGranted() in your custom controller.
    }

    /**
     * @param Request $request
     *
     * @return Response|JsonResponse
     * @throws \Exception
     */
    public function defaultAction(Request $request): Response
    {
        /** @var SearchApiRequestOptionsResolver $apiOptionsResolver */
        $apiOptionsResolver = $this->get(SearchApiRequestOptionsResolver::class);
        $options = $apiOptionsResolver->resolve($request->query->all());

        if (empty($options['search'])) {
            throw new BadRequestHttpException('Search parameter is not valid.');
        }

        if (null !== $options['node.nodeType']) {
            if (is_array($options['node.nodeType'])) {
                foreach ($options['node.nodeType'] as $nodeType) {
                    // Checks if node-type is granted for current request.
                    $this->denyAccessUnlessNodeTypeGranted($nodeType);
                }
            } else {
                $this->denyAccessUnlessNodeTypeGranted($options['node.nodeType']);
            }
        }

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
        $entityListManager = new SolrSearchListManager(
            $request,
            $this->getSearchHandler(),
            $criteria
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
     * @return SearchHandlerInterface
     */
    protected function getSearchHandler(): SearchHandlerInterface
    {
        /** @var NodeSourceSearchHandlerInterface|null $searchHandler */
        $searchHandler = $this->get(NodeSourceSearchHandlerInterface::class);
        if (null === $searchHandler) {
            throw new HttpException(Response::HTTP_SERVICE_UNAVAILABLE, 'Search engine does not respond.');
        }
        $searchHandler->boostByPublicationDate();
        if ($this->getHighlightingFragmentSize() > 0) {
            $searchHandler->setHighlightingFragmentSize($this->getHighlightingFragmentSize());
        }
        return $searchHandler;
    }

    /**
     * @return int
     */
    protected function getHighlightingFragmentSize(): int
    {
        return 200;
    }
}
