<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OptionsResolver;

use RZ\Roadiz\Core\Entities\Node;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagApiRequestOptionsResolver extends AbstractApiRequestOptionsResolver implements SimpleApiRequestOptionResolverInterface
{
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
            'properties' => null,
        ];
    }

    public function buildOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array_merge($this->getMetaOptions(), [
            'tagName' => null,
            'parent' => false,
            'visible' => null,
            'node.parent' => false,
        ]));
        $resolver->setAllowedTypes('_locale', ['string', 'null']);
        $resolver->setAllowedTypes('properties', ['string[]', 'null']);
        $resolver->setAllowedTypes('search', ['string', 'null']);
        $resolver->setAllowedTypes('tagName', ['string', 'null']);
        $resolver->setAllowedTypes('api_key', ['string', 'null']);
        $resolver->setAllowedTypes('node.parent', ['boolean', 'string', Node::class, 'null']);
        $resolver->setAllowedTypes('order', ['array', 'null']);
        $resolver->setAllowedTypes('visible', ['boolean', 'string', 'int', 'null']);

        $resolver->setNormalizer('_preview', function (Options $options, $value) {
            return $this->normalizeBoolean($value);
        });

        $resolver->setNormalizer('maxChildrenCount', function (Options $options, $value) {
            return intval($value) >= 0 && intval($value) < 100 ? intval($value) : 30;
        });

        $resolver->setNormalizer('itemsPerPage', function (Options $options, $value) {
            return intval($value) > 0 && intval($value) < 200 ? intval($value) : 15;
        });

        $resolver->setNormalizer('visible', function (Options $options, $value) {
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

        $resolver->setNormalizer('parent', function (Options $options, $value) {
            return $this->normalizeTagFilter($value);
        });

        return $resolver;
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
                if (null === $options['node.parent']) {
                    // Force NO results if filter does not resolve.
                    $options['id'] = 0;
                }
                unset($options['node_parent']);
            }
        }
        return $options;
    }
}
