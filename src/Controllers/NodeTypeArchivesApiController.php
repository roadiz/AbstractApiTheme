<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\ListManagers\QueryBuilderListManager;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractApiTheme\OptionsResolver\ApiRequestOptionsResolver;

class NodeTypeArchivesApiController extends AbstractNodeTypeApiController
{
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
        $queryBuilder = $this->getAvailableArchives(
            $nodeType,
            $this->getTranslation(),
            $criteria
        );
        $entityListManager = new QueryBuilderListManager(
            $request,
            $queryBuilder,
            'p',
            $this->get('kernel')->isDebug()
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

        return $this->makeResponseCachable($request, $response, $this->get('api.cache.ttl'));
    }

    /**
     * @param NodeType|null $nodeType
     * @param Translation|null $translation
     * @param array $criteria
     * @return QueryBuilder
     */
    protected function getAvailableArchives(
        ?NodeType $nodeType,
        ?Translation $translation,
        array &$criteria
    ): QueryBuilder {
        if (null !== $nodeType) {
            $qb = $this->get('em')
                ->getRepository($nodeType->getSourceEntityFullQualifiedClassName())
                ->createQueryBuilder('p');
        } else {
            $qb = $this->get('em')
                ->getRepository(NodesSources::class)
                ->createQueryBuilder('p');
        }
        $publicationField = 'p.' . $this->getPublicationField();

        $qb->select($publicationField)
            ->innerJoin('p.node', 'n')
            ->andWhere($qb->expr()->lte($publicationField, ':datetime'))
            ->addGroupBy($publicationField)
            ->orderBy($publicationField, 'DESC')
            ->setParameter(':datetime', new \Datetime('now'));

        if (null !== $translation) {
            $qb->andWhere($qb->expr()->eq('p.translation', ':translation'))
                ->setParameter(':translation', $translation);
        }
        /*
         * Enforce post nodes status not to display Archives which are linked to draft posts.
         */
        /** @var PreviewResolverInterface $previewResolver */
        $previewResolver = $this->get(PreviewResolverInterface::class);
        if ($previewResolver->isPreview()) {
            $qb->andWhere($qb->expr()->lte('n.status', Node::PUBLISHED));
        } else {
            $qb->andWhere($qb->expr()->eq('n.status', Node::PUBLISHED));
        }

        if (null !== $criteria['node.parent']) {
            $qb->andWhere($qb->expr()->eq('n.parent', ':parentNode'))
                ->setParameter(':parentNode', $criteria['node.parent']);
        }
        if (null !== $criteria['tags']) {
            if ($criteria['tagExclusive'] === true) {
                /**
                 * @var int $index
                 * @var Tag|null $tag Tag can be null if not found
                 */
                foreach ($criteria['tags'] as $index => $tag) {
                    if (null !== $tag && $tag instanceof Tag) {
                        $alias = 'tg' . $index;
                        $qb->innerJoin('n.tags', $alias);
                        $qb->andWhere($qb->expr()->eq($alias . '.id', $tag->getId()));
                    }
                }
            } else {
                $qb->innerJoin(
                    'n.tags',
                    'tg',
                    'WITH',
                    'tg.id IN (:tags)'
                )->setParameter(':tags', $criteria['tags']);
            }
        }

        return $qb;
    }

    protected function getSerializationGroups(): array
    {
        return [
            'urls',
            'meta',
        ];
    }

    protected function denyAccessUnlessNodeTypeGranted(NodeType $nodeType): void
    {
        // TODO: implement your own access-control logic for each node-type.
        // $this->denyAccessUnlessScopeGranted(['tags']);
    }

    protected function getPublicationField(): string
    {
        return 'publishedAt';
    }
}
