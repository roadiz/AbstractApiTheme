<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OptionsResolver;

interface SimpleApiRequestOptionResolverInterface extends ApiRequestOptionResolverInterface
{
    public function resolve(array $params): array;
}
