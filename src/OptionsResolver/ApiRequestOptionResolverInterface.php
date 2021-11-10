<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OptionsResolver;

interface ApiRequestOptionResolverInterface
{
    public function getCriteriaFromOptions(array &$options): array;
}
