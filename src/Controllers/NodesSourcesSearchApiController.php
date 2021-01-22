<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\SearchEngine\AbstractSearchHandler;
use RZ\Roadiz\Core\SearchEngine\NodeSourceSearchHandler;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Themes\AbstractApiTheme\ListManagers\SolrSearchListManager;
use Themes\AbstractApiTheme\OptionsResolver\ApiRequestOptionsResolver;

class NodesSourcesSearchApiController extends AbstractNodeTypeApiController
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
     * @return AbstractSearchHandler
     */
    protected function getSearchHandler(): AbstractSearchHandler
    {
        $searchHandler = $this->get('solr.search.nodeSource');
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
        return 300;
    }
}
