<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OptionsResolver;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\CMS\Utils\NodeApi;
use RZ\Roadiz\CMS\Utils\TagApi;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodeType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class SearchApiRequestOptionsResolver extends AbstractApiRequestOptionsResolver implements SimpleApiRequestOptionResolverInterface
{
    use NodeTypeAwareOptionResolverTrait;

    private ManagerRegistry $managerRegistry;

    /**
     * @param TagApi $tagApi
     * @param NodeApi $nodeApi
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        TagApi $tagApi,
        NodeApi $nodeApi,
        ManagerRegistry $managerRegistry
    ) {
        parent::__construct($tagApi, $nodeApi);
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager(): ObjectManager
    {
        return $this->managerRegistry->getManager();
    }

    /**
     * @param array $params
     *
     * @return array
     * @throws \Exception
     */
    public function resolve(array $params): array
    {
        return $this->configureOptions($this->normalizeQueryParams($params));
    }

    /**
     * @inheritDoc
     */
    protected function getMetaOptions(): array
    {
        return [
            'itemsPerPage' => 15,
            'page' => 1,
            '_locale' => null,
            '_preview' => false,
            'search' => null,
            'api_key' => null,
            'archive' => null,
            'properties' => null,
        ];
    }

    /**
     * @param array $options
     *
     * @return array
     * @throws \Exception
     */
    protected function configureOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array_merge($this->getMetaOptions(), [
            'publishedAt' => null,
            'tags' => null,
            'node.parent' => false,
            'node.visible' => null,
            'node.nodeType' => null,
        ]));
        $resolver->setAllowedTypes('_locale', ['string', 'null']);
        $resolver->setAllowedTypes('search', ['string', 'null']);
        $resolver->setAllowedTypes('api_key', ['string', 'null']);
        $resolver->setAllowedTypes('properties', ['string[]', 'null']);
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
