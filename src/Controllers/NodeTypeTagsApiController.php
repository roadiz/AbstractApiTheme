<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractApiTheme\ListManagers\TagQueryBuilderListManager;
use Themes\AbstractApiTheme\OptionsResolver\TagApiRequestOptionsResolver;

class NodeTypeTagsApiController extends AbstractNodeTypeApiController
{
    /**
     * @var Tag[] Pre-filled tags to alter every requests with
     */
    protected $implicitTags;

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

        /** @var TagApiRequestOptionsResolver $apiOptionsResolver */
        $apiOptionsResolver = $this->get(TagApiRequestOptionsResolver::class);
        $options = $apiOptionsResolver->resolve($request->query->all());

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
        $queryBuilder = $this->getAvailableTags(
            $nodeType,
            $criteria['translation'],
            $criteria['parent'] ?? null
        );
        if (isset($options['order'])) {
            foreach ($options['order'] as $field => $direction) {
                $queryBuilder->addOrderBy(sprintf('%s.%s', 't', $field), $direction);
            }
        }
        $entityListManager = new TagQueryBuilderListManager(
            $request,
            $queryBuilder,
            't',
            $this->get('kernel')->isDebug()
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
     * @param NodeType|null $nodeType
     * @param Translation $translation
     * @param Tag|null $parentTag
     *
     * @return QueryBuilder
     */
    protected function getAvailableTags(
        ?NodeType $nodeType,
        Translation $translation,
        Tag $parentTag = null
    ): QueryBuilder {
        /**
         * @var QueryBuilder $qb
         */
        $qb = $this->get('em')
            ->getRepository(Tag::class)
            ->createQueryBuilder('t');

        $qb->select('t, tt')
            ->leftJoin('t.translatedTags', 'tt')
            ->innerJoin('t.nodes', 'n')
            ->andWhere($qb->expr()->eq('t.visible', true))
            ->andWhere($qb->expr()->eq('tt.translation', ':translation'))
            ->setParameter(':translation', $translation);

        if (null !== $nodeType) {
            $qb->andWhere($qb->expr()->eq('n.nodeType', ':nodeType'))
                ->setParameter(':nodeType', $nodeType);

            if ($nodeType->isPublishable()) {
                $qb->innerJoin('n.nodeSources', 'ns')
                    ->andWhere($qb->expr()->lte('ns.translation', ':translation'))
                    ->andWhere($qb->expr()->lte('ns.publishedAt', ':now'))
                    ->setParameter('now', new \DateTime());
            }
        }

        if (null !== $this->getImplicitTags() && count($this->getImplicitTags())) {
            $qb->innerJoin('n.tags', 'implicitTags')
                ->andWhere($qb->expr()->in('implicitTags.id', ':implicitTags'))
                ->setParameter(':implicitTags', $this->getImplicitTags());
        }

        if (null !== $parentTag) {
            $parentTagId = $parentTag->getId();
            $qb->innerJoin('t.parent', 'pt')
                ->andWhere('pt.id = :parent')
                ->setParameter('parent', $parentTagId);
        }
        /*
         * Enforce tags nodes status not to display Tags which are linked to draft posts.
         */
        /** @var PreviewResolverInterface $previewResolver */
        $previewResolver = $this->get(PreviewResolverInterface::class);
        if ($previewResolver->isPreview()) {
            $qb->andWhere($qb->expr()->lte('n.status', Node::PUBLISHED));
        } else {
            $qb->andWhere($qb->expr()->eq('n.status', Node::PUBLISHED));
        }

        return $qb;
    }

    /**
     * @return Tag[]|null
     */
    protected function getImplicitTags(): ?array
    {
        return $this->implicitTags;
    }

    protected function getSerializationGroups(): array
    {
        return [
            'tag',
            'urls',
            'meta',
        ];
    }

    protected function denyAccessUnlessNodeTypeGranted(NodeType $nodeType): void
    {
        // TODO: implement your own access-control logic for each node-type.
        // $this->denyAccessUnlessScopeGranted(['tags']);
    }
}
