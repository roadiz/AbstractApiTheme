<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\Tag;
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
    protected array $implicitTags = [];

    protected function getDefaultSerializationGroups(): array
    {
        return [
            'tag',
            'urls',
            'meta',
        ];
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

        /** @var TagApiRequestOptionsResolver $apiOptionsResolver */
        $apiOptionsResolver = $this->get(TagApiRequestOptionsResolver::class);
        $options = $apiOptionsResolver->resolve($request->query->all());
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
        $queryBuilder = $this->getAvailableTags(
            $nodeType,
            $this->getTranslation(),
            $criteria['parent'] ?? null,
            $criteria['visible'] ?? null
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
     * @param NodeTypeInterface|null $nodeType
     * @param TranslationInterface|null $translation
     * @param Tag|null $parentTag
     * @param bool|null $visible
     * @return QueryBuilder
     */
    protected function getAvailableTags(
        ?NodeTypeInterface $nodeType,
        ?TranslationInterface $translation,
        Tag $parentTag = null,
        ?bool $visible = null
    ): QueryBuilder {
        $qb = $this->getDoctrine()
            ->getRepository(Tag::class)
            ->createQueryBuilder('t');

        $qb->select('t, tt')
            ->leftJoin('t.translatedTags', 'tt')
            ->innerJoin('t.nodes', 'n');

        if (null !== $visible) {
            $qb->andWhere($qb->expr()->eq('t.visible', ':visible'))
                ->setParameter('visible', $visible);
        }

        if (null !== $translation) {
            $qb->andWhere($qb->expr()->eq('tt.translation', ':translation'))
                ->setParameter(':translation', $translation);
        }

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

    protected function denyAccessUnlessNodeTypeGranted(NodeTypeInterface $nodeType): void
    {
        // TODO: implement your own access-control logic for each node-type.
        // $this->denyAccessUnlessScopeGranted(['tags']);
    }
}
