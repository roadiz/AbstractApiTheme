<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OptionsResolver;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface ApiRequestOptionResolverInterface
{
    /**
     * Allows OptionsResolver to be overrideable for custom projects.
     *
     * @return OptionsResolver
     */
    public function buildOptionsResolver(): OptionsResolver;
    public function getCriteriaFromOptions(array &$options): array;
}
