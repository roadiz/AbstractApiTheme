<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Themes\AbstractApiTheme\AbstractApiThemeApp;

class NodeTypeApiController extends AbstractApiThemeApp
{
    /**
     * @return array
     */
    protected function getMetaOptions(): array
    {
        return [
            'itemsPerPage' => 15,
            'maxChildrenCount' => 30,
            'page' => 1,
            '_locale' => $this->get('defaultTranslation')->getLocale(),
            'search' => null,
            'api_key' => null,
            'order' => null,
            'archive' => null,
        ];
    }

    /**
     * @param array         $options
     * @param NodeType|null $nodeType
     *
     * @return array
     * @throws \Exception
     */
    protected function resolveOptions(array $options, ?NodeType $nodeType): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array_merge($this->getMetaOptions(), [
            'title' => null,
            'publishedAt' => null,
            'tags' => null,
            'tagExclusive' => false,
            'node.parent' => false,
            'node.visible' => null,
            'node.nodeType.reachable' => null,
        ]));
        $resolver->setAllowedTypes('search', ['string', 'null']);
        $resolver->setAllowedTypes('title', ['string', 'null']);
        $resolver->setAllowedTypes('api_key', ['string', 'null']);
        $resolver->setAllowedTypes('order', ['array', 'null']);
        $resolver->setAllowedTypes('publishedAt', ['array', 'string', 'null']);
        $resolver->setAllowedTypes('tags', ['array', 'string', 'null']);
        $resolver->setAllowedTypes('tagExclusive', ['boolean', 'string', 'int']);
        $resolver->setAllowedTypes('node.nodeType.reachable', ['boolean', 'string', 'int', 'null']);
        $resolver->setAllowedTypes('node.visible', ['boolean', 'string', 'int', 'null']);

        $resolver->setNormalizer('tagExclusive', function (Options $options, $value) {
            return $this->normalizeBoolean($value);
        });

        $resolver->setNormalizer('maxChildrenCount', function (Options $options, $value) {
            return intval($value) >= 0 && intval($value) < 100 ? intval($value) : 30;
        });

        $resolver->setNormalizer('itemsPerPage', function (Options $options, $value) {
            return intval($value) > 0 && intval($value) < 200 ? intval($value) : 15;
        });

        $resolver->setNormalizer('node.nodeType.reachable', function (Options $options, $value) {
            if (null !== $value) {
                return $this->normalizeBoolean($value);
            }
            return null;
        });

        $resolver->setNormalizer('node.visible', function (Options $options, $value) {
            if (null !== $value) {
                return $this->normalizeBoolean($value);
            }
            return null;
        });

        $resolver->setNormalizer('order', function (Options $options, $value) {
            if (null !== $value) {
                if (!is_array($value)) {
                    throw new InvalidOptionsException();
                }
                foreach ($value as $key => $direction) {
                    if (!preg_match('#^[a-zA-Z\.]+$#', $key)) {
                        throw new InvalidOptionsException('Order fields key must be only alpha and dot.');
                    }
                    if (!in_array(strtolower($direction), ['asc', 'desc'])) {
                        throw new InvalidOptionsException('Order fields value must be ASC or DESC.');
                    }
                }
            }
            return $value;
        });

        $resolver->setNormalizer('node.parent', function (Options $options, $value) {
            return $this->normalizeNodeFilter($value);
        });

        $resolver->setNormalizer('publishedAt', function (Options $options, $value) {
            return $this->normalizePublishedAtFilter($options, $value);
        });

        $resolver->setNormalizer('tags', function (Options $options, $value) {
            if (is_array($value)) {
                return array_map(function ($singleValue) {
                    return $this->normalizeTagFilter($singleValue);
                }, $value);
            }
            return $this->normalizeTagFilter($value);
        });

        /*
         * Search criteria is enabled on NodeTypeFields ONLY if they are
         * indexed
         */
        if (null !== $nodeType) {
            $indexedFields = $nodeType->getFields()->filter(function (NodeTypeField $field) {
                return $field->isIndexed();
            });
            /** @var NodeTypeField $field */
            foreach ($indexedFields as $field) {
                switch ($field->getType()) {
                    case NodeTypeField::DATE_T:
                    case NodeTypeField::DATETIME_T:
                        $resolver->setDefault($field->getVarName(), null);
                        $resolver->setNormalizer($field->getVarName(), function (Options $options, $value) {
                            return $this->normalizeDateTimeFilter($value);
                        });
                        break;
                    case NodeTypeField::BOOLEAN_T:
                        $resolver->setDefault($field->getVarName(), null);
                        $resolver->setNormalizer($field->getVarName(), function (Options $options, $value) {
                            if (null !== $value) {
                                return $this->normalizeBoolean($value);
                            }
                            return null;
                        });
                        break;
                    case NodeTypeField::STRING_T:
                    case NodeTypeField::COUNTRY_T:
                    case NodeTypeField::ENUM_T:
                        $resolver->setDefault($field->getVarName(), null);
                        $resolver->setAllowedTypes($field->getVarName(), ['null', 'string', 'array']);
                        $resolver->setNormalizer($field->getVarName(), function (Options $options, $value) {
                            if (null !== $value) {
                                if (is_array($value)) {
                                    return array_filter($value);
                                }
                                if (is_string($value)) {
                                    return trim($value);
                                }
                            }
                            return null;
                        });
                        break;
                }
            }
        }

        return $resolver->resolve($options);
    }

    protected function limitPublishedAtEndDate(\DateTime $endDate): \DateTime
    {
        $now = new \DateTime();
        if ($endDate > $now) {
            return $now;
        }
        return $endDate;
    }

    /**

     * Support archive parameter with year or year-month
     *
     * @param Options $options
     * @param mixed   $value
     *
     * @return array
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
     * @param mixed $value
     *
     * @return array|\DateTime
     * @throws \Exception
     */
    protected function normalizeDateTimeFilter($value)
    {
        if (null !== $value && is_string($value)) {
            return new \DateTime($value);
        }
        if (is_array($value)) {
            if (isset($value['after']) && isset($value['before'])) {
                return ['BETWEEN', new \DateTime($value['after']), new \DateTime($value['before'])];
            }
            if (isset($value['strictly_after']) && isset($value['strictly_before'])) {
                return ['BETWEEN', new \DateTime($value['strictly_after']), new \DateTime($value['strictly_before'])];
            }
            if (isset($value['after'])) {
                return ['>=', new \DateTime($value['after'])];
            }
            if (isset($value['strictly_after'])) {
                return ['>', new \DateTime($value['strictly_after'])];
            }
            if (isset($value['before'])) {
                return ['<=', new \DateTime($value['before'])];
            }
            if (isset($value['strictly_before'])) {
                return ['<', new \DateTime($value['strictly_before'])];
            }
        }
        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    protected function normalizeBoolean($value): bool
    {
        return $value === true ||
            $value === 'true' ||
            $value === 'ON' ||
            $value === 'on' ||
            $value === 'yes' ||
            $value === '1' ||
            $value === 1
        ;
    }

    /**
     * @param mixed $value
     *
     * @return Tag|null
     */
    protected function normalizeTagFilter($value): ?Tag
    {
        if (null !== $value && $value instanceof Tag) {
            return $value;
        }
        if (null !== $value && is_string($value)) {
            return $this->get('tagApi')->getOneBy([
                'tagName' => $value,
            ]);
        }
        if (null !== $value && is_numeric($value)) {
            return $this->get('tagApi')->getOneBy([
                'id' => $value,
            ]);
        }
        return null;
    }

    /**
     * @param mixed $value
     *
     * @return Node|null
     */
    protected function normalizeNodeFilter($value): ?Node
    {
        if (null !== $value && $value instanceof Node) {
            return $value;
        }
        if (null !== $value && is_numeric($value)) {
            return $this->get('nodeApi')->getOneBy([
                'id' => $value,
            ]);
        }
        if (null !== $value && is_string($value)) {
            return $this->get('nodeApi')->getOneBy([
                'nodeName' => $value,
            ]);
        }
        return null;
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function getCriteriaFromOptions(array &$options): array
    {
        $activeOptions = array_filter($options, function ($value) {
            return null !== $value;
        });
        return array_filter($activeOptions, function ($key) {
            return !array_key_exists($key, $this->getMetaOptions());
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function normalizeQueryParams(array $options): array
    {
        foreach ($options as $key => $value) {
            if ($key === 'node_parent') {
                $options['node.parent'] = $this->normalizeNodeFilter($value);
                unset($options['node_parent']);
            } elseif ($key === 'node_visible') {
                $options['node.visible'] = $this->normalizeBoolean($value);
                unset($options['node_visible']);
            } elseif ($key === 'node_nodeType_reachable') {
                $options['node.nodeType.reachable'] = $this->normalizeBoolean($value);
                unset($options['node_nodeType_reachable']);
            }
        }
        return $options;
    }

    protected function getListingSerializationGroups(): array
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
     * @return SerializationContext
     */
    protected function getListingSerializationContext(): SerializationContext
    {
        $context = SerializationContext::create()
            ->setAttribute('translation', $this->getTranslation())
            ->enableMaxDepthChecks();
        if (count($this->getListingSerializationGroups()) > 0) {
            $context->setGroups($this->getListingSerializationGroups());
        }

        return $context;
    }

    protected function getListingChildrenSerializationGroups(): array
    {
        return [
            'nodes_sources_base',
            'nodes_source_children',
            'tag_base',
            'nodes_sources_default',
            'urls',
            'meta',
        ];
    }

    /**
     * @return SerializationContext
     */
    protected function getListingChildrenSerializationContext(array $criteria, int $maxChildrenCount = 30): SerializationContext
    {
        $context = SerializationContext::create()
            ->setAttribute('translation', $this->getTranslation())
            ->setAttribute('childrenCriteria', $criteria)
            ->setAttribute('maxChildrenCount', $maxChildrenCount)
            ->enableMaxDepthChecks();
        if (count($this->getListingChildrenSerializationGroups()) > 0) {
            $context->setGroups($this->getListingChildrenSerializationGroups());
        }

        return $context;
    }

    protected function getDetailSerializationGroups(): array
    {
        return [
            'nodes_sources',
            'tag_base',
            'urls',
        ];
    }

    /**
     * @return SerializationContext
     */
    protected function getDetailSerializationContext(): SerializationContext
    {
        $context = $this->getListingSerializationContext();
        if (count($this->getDetailSerializationGroups()) > 0) {
            $context->setGroups($this->getDetailSerializationGroups());
        }

        return $context;
    }

    /**
     * @param Request $request
     * @param int     $nodeTypeId
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function getListingAction(Request $request, int $nodeTypeId)
    {
        /** @var NodeType|null $nodeType */
        $nodeType = $this->get('em')->find(NodeType::class, $nodeTypeId);
        if (null === $nodeType) {
            throw $this->createNotFoundException();
        }

        $options = $this->resolveOptions($this->normalizeQueryParams($request->query->all()), $nodeType);

        /** @var Translation|null $translation */
        $translation = $this->get('em')->getRepository(Translation::class)->findOneByLocale($options['_locale']);
        if (null === $translation) {
            throw $this->createNotFoundException();
        }

        $defaultCriteria = [
            'translation' => $translation,
        ];
        if ($nodeType->isPublishable()) {
            $defaultCriteria['publishedAt'] = ['<=', new \DateTime()];
        }

        $criteria = array_merge(
            $defaultCriteria,
            $this->getCriteriaFromOptions($options)
        );

        $entityListManager = $this->createEntityListManager(
            $nodeType->getSourceEntityFullQualifiedClassName(),
            $criteria,
            null !== $options['order'] ? $options['order'] : []
        );
        $entityListManager->setItemPerPage($options['itemsPerPage']);
        $entityListManager->setPage($options['page']);
        $entityListManager->handle();

        /** @var SerializerInterface $serializer */
        $serializer = $this->get('serializer');
        return new JsonResponse(
            $serializer->serialize(
                $entityListManager,
                'json',
                $this->getListingSerializationContext()
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @param Request $request
     * @param int     $nodeTypeId
     * @param int     $id
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function getChildrenAction(Request $request, int $nodeTypeId, int $id)
    {
        $options = $this->resolveOptions($this->normalizeQueryParams($request->query->all()), null);

        /** @var Translation|null $translation */
        $translation = $this->get('em')->getRepository(Translation::class)->findOneByLocale($options['_locale']);
        if (null === $translation) {
            throw $this->createNotFoundException();
        }

        $defaultCriteria = [
            'translation' => $translation,
            'node.parent' => $id,
        ];

        $criteria = array_merge(
            $defaultCriteria,
            $this->getCriteriaFromOptions($options)
        );

        $entityListManager = $this->createEntityListManager(
            NodesSources::class,
            $criteria,
            null !== $options['order'] ? $options['order'] : [
                'node.position' => 'ASC'
            ]
        );
        $entityListManager->setItemPerPage($options['itemsPerPage']);
        $entityListManager->setPage($options['page']);
        $entityListManager->handle();

        /** @var SerializerInterface $serializer */
        $serializer = $this->get('serializer');
        return new JsonResponse(
            $serializer->serialize(
                $entityListManager,
                'json',
                $this->getListingChildrenSerializationContext($criteria, $options['maxChildrenCount'])
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @param Request $request
     * @param int     $nodeTypeId
     * @param int     $id
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function getDetailAction(Request $request, int $nodeTypeId, int $id)
    {
        /** @var NodeType|null $nodeType */
        $nodeType = $this->get('em')->find(NodeType::class, $nodeTypeId);
        $options = $this->resolveOptions($this->normalizeQueryParams($request->query->all()), $nodeType);

        if (null === $nodeType) {
            throw $this->createNotFoundException();
        }

        /** @var Translation|null $translation */
        $translation = $this->get('em')->getRepository(Translation::class)->findOneByLocale($options['_locale']);
        if (null === $translation) {
            throw $this->createNotFoundException();
        }

        $criteria = [
            'node.nodeType' => $nodeType,
            'node.id' => $id,
            'translation' => $translation,
        ];

        if ($nodeType->isPublishable()) {
            $criteria['publishedAt'] = ['<=', new \DateTime()];
        }

        $nodeSource = $this->get('nodeSourceApi')->getOneBy($criteria);

        if (null === $nodeSource) {
            throw $this->createNotFoundException();
        }

        /** @var SerializerInterface $serializer */
        $serializer = $this->get('serializer');
        return new JsonResponse(
            $serializer->serialize(
                $nodeSource,
                'json',
                $this->getDetailSerializationContext()
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }
}
