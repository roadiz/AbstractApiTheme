<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OptionsResolver;

use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\NodeTypeField;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApiRequestOptionsResolver extends AbstractApiRequestOptionsResolver
{
    /**
     * @param array         $params
     * @param NodeType|null $nodeType
     *
     * @return array
     * @throws \Exception
     */
    public function resolve(array $params, ?NodeType $nodeType): array
    {
        return $this->resolveOptions($this->normalizeQueryParams($params), $nodeType);
    }

    /**
     * @return array
     */
    protected function getMetaOptions(): array
    {
        return [
            'itemsPerPage' => 15,
            'maxChildrenCount' => 30,
            'page' => 1,
            '_locale' => $this->getDefaultLocale(),
            '_preview' => false,
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

        $resolver->setNormalizer('_preview', function (Options $options, $value) {
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
}
