<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\SearchEngine\NodeSourceSearchHandlerInterface;
use RZ\Roadiz\Core\SearchEngine\SearchHandlerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Themes\AbstractApiTheme\Cache\CacheTagsCollection;
use Themes\AbstractApiTheme\ListManagers\SolrSearchListManager;
use Themes\AbstractApiTheme\OptionsResolver\ApiRequestOptionsResolver;

class NodesSourcesSearchApiController extends AbstractNodeTypeApiController
{
    protected function getSerializationGroups(): array
    {
        return [
            'nodes_sources_base',
            'document_display',
            'tag_base',
            'nodes_sources_default',
            'urls',
            'meta',
        ];
    }

    protected function denyAccessUnlessNodeTypeGranted(NodeType $nodeType): void
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
        /** @var ApiRequestOptionsResolver $apiOptionsResolver */
        $apiOptionsResolver = $this->get(ApiRequestOptionsResolver::class);
        $options = $apiOptionsResolver->resolve($request->query->all(), null);

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

        $this->getTranslationOrNotFound($options['_locale']);

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

        $entityListManager = new SolrSearchListManager(
            $request,
            $this->getSearchHandler(),
            $criteria
        );
        $entityListManager->setItemPerPage($options['itemsPerPage']);
        $entityListManager->setPage($options['page']);
        $entityListManager->handle();

        /** @var SerializerInterface $serializer */
        $serializer = $this->get('serializer');
        $context = $this->getSerializationContext();
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

        return $this->makeResponseCachable($request, $response, $this->get('api.cache.ttl'));
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
