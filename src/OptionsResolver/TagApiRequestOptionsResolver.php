<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OptionsResolver;

use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TagApiRequestOptionsResolver extends AbstractApiRequestOptionsResolver
{
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
            'order' => null
        ];
    }

    /**
     * @param array $options
     *
     * @return array
     * @throws \Exception
     */
    protected function resolveOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array_merge($this->getMetaOptions(), [
            'tagName' => null,
            'parent' => false,
            'visible' => null,
        ]));
        $resolver->setAllowedTypes('search', ['string', 'null']);
        $resolver->setAllowedTypes('tagName', ['string', 'null']);
        $resolver->setAllowedTypes('api_key', ['string', 'null']);
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

        $resolver->setNormalizer('parent', function (Options $options, $value) {
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
        return $options;
    }
}
