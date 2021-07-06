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
use RZ\Roadiz\Core\Entities\Redirection;
use RZ\Roadiz\Core\Routing\PathResolverInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ApiRequestOptionsResolver extends AbstractApiRequestOptionsResolver
{
    use NodeTypeAwareOptionResolverTrait;

    private PathResolverInterface $pathResolver;
    private EntityManagerInterface $entityManager;

    /**
     * @param TagApi $tagApi
     * @param NodeApi $nodeApi
     * @param PathResolverInterface $pathResolver
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        TagApi $tagApi,
        NodeApi $nodeApi,
        PathResolverInterface $pathResolver,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($tagApi, $nodeApi);
        $this->pathResolver = $pathResolver;
        $this->entityManager = $entityManager;
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @param array $params
     * @param NodeTypeInterface|null $nodeType
     *
     * @return array
     * @throws \Exception
     */
    public function resolve(array $params, ?NodeTypeInterface $nodeType): array
    {
        return $this->configureOptions($this->normalizeQueryParams($params), $nodeType);
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
            '_locale' => null,
            '_preview' => false,
            'search' => null,
            'api_key' => null,
            'order' => null,
            'archive' => null,
            'properties' => null,
            '_node_source' => null,
            'path' => null
        ];
    }

    /**
     * @param array         $options
     * @param NodeTypeInterface|null $nodeType
     *
     * @return array
     * @throws \Exception
     */
    protected function configureOptions(array $options, ?NodeTypeInterface $nodeType): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array_merge($this->getMetaOptions(), [
            'id' => null,
            'title' => null,
            'publishedAt' => null,
            'tags' => null,
            'tagExclusive' => null,
            'not' => null,
            'node.id' => null,
            'node.nodeName' => null,
            'node.parent' => false,
            'node.bNodes.nodeB' => false,
            'node.aNodes.nodeA' => false,
            'node.bNodes.field.name' => null,
            'node.aNodes.field.name' => null,
            'node.visible' => null,
            'node.nodeType.reachable' => null,
            'node.nodeType' => null,
            'node.home' => null
        ]));
        $resolver->setAllowedTypes('_locale', ['string', 'null']);
        $resolver->setAllowedTypes('_node_source', [NodesSources::class, 'null']);
        $resolver->setAllowedTypes('search', ['string', 'null']);
        $resolver->setAllowedTypes('title', ['string', 'null']);
        $resolver->setAllowedTypes('api_key', ['string', 'null']);
        $resolver->setAllowedTypes('order', ['array', 'null']);
        $resolver->setAllowedTypes('not', ['array', 'string', 'int', 'null']);
        $resolver->setAllowedTypes('properties', ['string[]', 'null']);
        $resolver->setAllowedTypes('publishedAt', ['array', 'string', 'null']);
        $resolver->setAllowedTypes('tags', ['array', 'string', 'null']);
        $resolver->setAllowedTypes('tagExclusive', ['boolean', 'string', 'int', 'null']);
        $resolver->setAllowedTypes('node.nodeType.reachable', ['boolean', 'string', 'int', 'null']);
        $resolver->setAllowedTypes('node.nodeType', ['array', NodeType::class, 'string', 'int', 'null']);
        $resolver->setAllowedTypes('node.visible', ['boolean', 'string', 'int', 'null']);
        $resolver->setAllowedTypes('node.parent', ['boolean', 'string', Node::class, 'null']);
        $resolver->setAllowedTypes('node.id', ['array', 'int', 'null']);
        $resolver->setAllowedTypes('node.nodeName', ['string', 'null']);
        $resolver->setAllowedTypes('node.bNodes.nodeB', ['boolean', 'string', Node::class, 'null']);
        $resolver->setAllowedTypes('node.aNodes.nodeA', ['boolean', 'string', Node::class, 'null']);
        $resolver->setAllowedTypes('node.bNodes.field.name', ['string', 'null']);
        $resolver->setAllowedTypes('node.aNodes.field.name', ['string', 'null']);
        $resolver->setAllowedTypes('node.home', ['boolean', 'string', 'int', 'null']);
        $resolver->setAllowedTypes('path', ['string', 'null']);
        $resolver->setAllowedTypes('id', ['int', 'array', NodesSources::class, 'null']);

        $resolver->setNormalizer('tagExclusive', function (Options $options, $value) {
            if (null !== $value && null !== $options['search']) {
                throw new InvalidOptionsException('tagExclusive filter cannot be used with search filter');
            }
            return null !== $value ? $this->normalizeBoolean($value) : null;
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

        $resolver->setNormalizer('node.home', function (Options $options, $value) {
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

        $resolver->setNormalizer('not', function (Options $options, $value) {
            if (null !== $value) {
                if (!is_array($value)) {
                    if (!is_numeric($value) && !is_string($value)) {
                        throw new InvalidOptionsException('Not filter must be an ID or a node-name');
                    }
                } else {
                    foreach ($value as $key => $notValue) {
                        if (!is_numeric($notValue) && !is_string($notValue)) {
                            throw new InvalidOptionsException('Not filter value must be an ID or a node-name');
                        }
                    }
                }
            }
            return $value;
        });

        $resolver->setNormalizer('node.parent', function (Options $options, $value) {
            return $this->normalizeNodeFilter($value);
        });

        $resolver->setNormalizer('node.bNodes.nodeB', function (Options $options, $value) {
            return $this->normalizeNodeFilter($value);
        });

        $resolver->setNormalizer('node.aNodes.nodeA', function (Options $options, $value) {
            return $this->normalizeNodeFilter($value);
        });

        $resolver->setNormalizer('publishedAt', function (Options $options, $value) {
            return $this->normalizePublishedAtFilter($options, $value);
        });

        $resolver->setNormalizer('tags', function (Options $options, $value) {
            if (null !== $value && null !== $options['search']) {
                throw new InvalidOptionsException('tags filter cannot be used with search filter');
            }
            if (is_array($value)) {
                return array_filter(array_map(function ($singleValue) {
                    return $this->normalizeTagFilter($singleValue);
                }, $value));
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
                        $resolver->setInfo($field->getVarName(), $field->getDescription() ?? $field->getLabel());
                        $resolver->setDefault($field->getVarName(), null);
                        $resolver->setNormalizer($field->getVarName(), function (Options $options, $value) {
                            return $this->normalizeDateTimeFilter($value);
                        });
                        break;
                    case NodeTypeField::BOOLEAN_T:
                        $resolver->setInfo($field->getVarName(), $field->getDescription() ?? $field->getLabel());
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
                        $resolver->setInfo($field->getVarName(), $field->getDescription() ?? $field->getLabel());
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

    /**
     * @param string $path
     * @return NodesSources|null Returns nodes-sources or null if no NS found for path to filter all results.
     */
    protected function normalizeNodesSourcesPath(string $path): ?NodesSources
    {
        $resourceInfo = $this->pathResolver->resolvePath($path, ['html', 'json'], true);
        $resource = $resourceInfo->getResource();
        /*
         * Normalize redirected node-sources
         */
        if (null !== $resource &&
            $resource instanceof Redirection &&
            null !== $resource->getRedirectNodeSource()) {
            return $resource->getRedirectNodeSource();
        }
        /*
         * Or plain node-source
         */
        if (null !== $resource && $resource instanceof NodesSources) {
            return $resource;
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
                case 'node_bNodes_nodeB':
                    $options['node.bNodes.nodeB'] = $this->normalizeNodeFilter($value);
                    if (null === $options['node.bNodes.nodeB']) {
                        // Force NO results if filter does not resolve.
                        $options['id'] = 0;
                    }
                    unset($options['node_bNodes_nodeB']);
                    break;
                case 'node_aNodes_nodeA':
                    $options['node.aNodes.nodeA'] = $this->normalizeNodeFilter($value);
                    if (null === $options['node.aNodes.nodeA']) {
                        // Force NO results if filter does not resolve.
                        $options['id'] = 0;
                    }
                    unset($options['node_aNodes_nodeA']);
                    break;
                case 'node_bNodes_field_name':
                    $options['node.bNodes.field.name'] = $value;
                    if (null === $options['node.bNodes.field.name']) {
                        // Force NO results if filter does not resolve.
                        $options['id'] = 0;
                    }
                    unset($options['node_bNodes_field_name']);
                    break;
                case 'node_aNodes_field_name':
                    $options['node.aNodes.field.name'] = $value;
                    if (null === $options['node.aNodes.field.name']) {
                        // Force NO results if filter does not resolve.
                        $options['id'] = 0;
                    }
                    unset($options['node_aNodes_field_name']);
                    break;
                case 'node_visible':
                    $options['node.visible'] = $this->normalizeBoolean($value);
                    unset($options['node_visible']);
                    break;
                case 'node_home':
                    $options['node.home'] = $this->normalizeBoolean($value);
                    unset($options['node_home']);
                    break;
                case 'node_nodeType_reachable':
                    $options['node.nodeType.reachable'] = $this->normalizeBoolean($value);
                    unset($options['node_nodeType_reachable']);
                    break;
                case 'node_nodeType':
                    $options['node.nodeType'] = $this->normalizeNodeTypes($value);
                    if (null === $options['node.nodeType']) {
                        // Force NO results if filter does not resolve.
                        $options['id'] = 0;
                    }
                    unset($options['node_nodeType']);
                    break;
                case 'node_nodeName':
                    $options['node.nodeName'] = trim($value);
                    unset($options['node_nodeName']);
                    break;
                case 'not':
                    if (is_array($value)) {
                        $notNodes = array_filter(array_map([$this, 'normalizeNodeFilter'], $value));
                        if (count($notNodes) > 0) {
                            $options['node.id'] = ['NOT IN', $notNodes];
                        }
                    } else {
                        $notNodes = $this->normalizeNodeFilter($value);
                        if (null !== $notNodes) {
                            $options['node.id'] = ['!=', $notNodes];
                        }
                    }
                    unset($options['not']);
                    break;
                case 'path':
                    $nodesSource = $this->normalizeNodesSourcesPath($value);
                    if (null !== $nodesSource) {
                        $options['id'] = $nodesSource->getId();
                        $options['_node_source'] = $nodesSource;
                        $options['_locale'] = $nodesSource->getTranslation()->getPreferredLocale();
                    } else {
                        // Force NO results if path is not resolved.
                        $options['id'] = 0;
                    }
                    unset($options['path']);
                    break;
            }
        }
        return $options;
    }
}
