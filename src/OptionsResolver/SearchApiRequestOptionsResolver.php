<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OptionsResolver;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\CMS\Utils\NodeApi;
use RZ\Roadiz\CMS\Utils\TagApi;
use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Routing\PathResolverInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SearchApiRequestOptionsResolver extends AbstractApiRequestOptionsResolver
{
    private EntityManagerInterface $entityManager;

    /**
     * @param string|null $defaultLocale
     * @param TagApi $tagApi
     * @param NodeApi $nodeApi
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        ?string $defaultLocale,
        TagApi $tagApi,
        NodeApi $nodeApi,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($defaultLocale, $tagApi, $nodeApi);
        $this->entityManager = $entityManager;
    }

    /**
     * @param array $params
     *
     * @return array
     * @throws \Exception
     */
    public function resolve(array $params): array
    {
        return $this->resolveOptions($this->normalizeQueryParams($params));
    }

    /**
     * @inheritDoc
     */
    protected function getMetaOptions(): array
    {
        return [
            'itemsPerPage' => 15,
            'page' => 1,
            '_locale' => $this->getDefaultLocale(),
            '_preview' => false,
            'search' => null,
            'api_key' => null,
            'archive' => null,
            'properties' => null,
        ];
    }

    /**
     * @param array         $options
     *
     * @return array
     * @throws \Exception
     */
    protected function resolveOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array_merge($this->getMetaOptions(), [
            'publishedAt' => null,
            'tags' => null,
            'node.parent' => false,
            'node.visible' => null,
            'node.nodeType' => null,
        ]));
        $resolver->setAllowedTypes('_locale', ['string']);
        $resolver->setAllowedTypes('search', ['string', 'null']);
        $resolver->setAllowedTypes('api_key', ['string', 'null']);
        $resolver->setAllowedTypes('properties', ['array', 'null']);
        $resolver->setAllowedTypes('publishedAt', ['array', 'string', 'null']);
        $resolver->setAllowedTypes('tags', ['array', 'string', 'null']);
        $resolver->setAllowedTypes('node.nodeType', ['array', NodeType::class, 'string', 'int', 'null']);
        $resolver->setAllowedTypes('node.visible', ['boolean', 'string', 'int', 'null']);
        $resolver->setAllowedTypes('node.parent', ['boolean', 'string', Node::class, 'null']);

        $resolver->setNormalizer('_preview', function (Options $options, $value) {
            return $this->normalizeBoolean($value);
        });

        $resolver->setNormalizer('itemsPerPage', function (Options $options, $value) {
            return intval($value) > 0 && intval($value) < 200 ? intval($value) : 15;
        });

        $resolver->setNormalizer('node.visible', function (Options $options, $value) {
            if (null !== $value) {
                return $this->normalizeBoolean($value);
            }
            return null;
        });

        $resolver->setNormalizer('node.parent', function (Options $options, $value) {
            return $this->normalizeNodeFilter($value);
        });

        $resolver->setNormalizer('publishedAt', function (Options $options, $value) {
            return $this->normalizePublishedAtFilter($options, $value);
        });

        $resolver->setNormalizer('tags', function (Options $options, $value) {
            if (is_array($value)) {
                return array_filter(array_map(function ($singleValue) {
                    return $this->normalizeTagFilter($singleValue);
                }, $value));
            }
            return $this->normalizeTagFilter($value);
        });

        return $resolver->resolve($options);
    }

    /**

     * Support archive parameter with year or year-month
     *
     * @param Options $options
     * @param mixed   $value
     *
     * @return array|\DateTime
     * @throws \Exception
     */
    protected function normalizePublishedAtFilter(Options $options, $value)
    {
        /*
         * Support archive parameter with year or year-month
         */
        if (null !== $options['archive'] && $options['archive'] !== '') {
            $archive = $options['archive'];
            if (preg_match('#[0-9]{4}\-[0-9]{2}\-[0-9]{2}#', $archive) > 0) {
                $startDate = new \DateTime($archive . ' 00:00:00');
                $endDate = clone $startDate;
                $endDate->add(new \DateInterval('P1D'));

                return ['BETWEEN', $startDate, $this->limitPublishedAtEndDate($endDate)];
            } elseif (preg_match('#[0-9]{4}\-[0-9]{2}#', $archive) > 0) {
                $startDate = new \DateTime($archive . '-01 00:00:00');
                $endDate = clone $startDate;
                $endDate->add(new \DateInterval('P1M'));

                return ['BETWEEN', $startDate, $this->limitPublishedAtEndDate($endDate)];
            } elseif (preg_match('#[0-9]{4}#', $archive) > 0) {
                $startDate = new \DateTime($archive . '-01-01 00:00:00');
                $endDate = clone $startDate;
                $endDate->add(new \DateInterval('P1Y'));

                return ['BETWEEN', $startDate, $this->limitPublishedAtEndDate($endDate)];
            }
        }
        return $this->normalizeDateTimeFilter($value);
    }

    /**
     * @param int|string|array<int|string> $nodeTypes
     * @return NodeTypeInterface|array<NodeTypeInterface>|null
     */
    protected function normalizeNodeTypes($nodeTypes)
    {
        if (is_array($nodeTypes)) {
            return array_values(array_filter(array_map([$this, 'normalizeSingleNodeType'], $nodeTypes)));
        } else {
            return $this->normalizeSingleNodeType($nodeTypes);
        }
    }

    /**
     * @param int|string $nodeType
     * @return NodeTypeInterface|null
     */
    protected function normalizeSingleNodeType($nodeType): ?NodeTypeInterface
    {
        if (is_integer($nodeType)) {
            return $this->entityManager->find(NodeType::class, (int) $nodeType);
        } elseif (is_string($nodeType)) {
            return $this->entityManager
                ->getRepository(NodeType::class)
                ->findOneByName($nodeType);
        }
        return null;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function normalizeQueryParams(array $options): array
    {
        foreach ($options as $key => $value) {
            switch ($key) {
                case 'node_parent':
                    $options['node.parent'] = $this->normalizeNodeFilter($value);
                    if (null === $options['node.parent']) {
                        // Force NO results if filter does not resolve.
                        $options['id'] = 0;
                    }
                    unset($options['node_parent']);
                    break;
                case 'node_visible':
                    $options['node.visible'] = $this->normalizeBoolean($value);
                    unset($options['node_visible']);
                    break;
                case 'node_nodeType':
                    $options['node.nodeType'] = $this->normalizeNodeTypes($value);
                    if (null === $options['node.nodeType']) {
                        // Force NO results if filter does not resolve.
                        $options['id'] = 0;
                    }
                    unset($options['node_nodeType']);
                    break;
            }
        }
        return $options;
    }
}
