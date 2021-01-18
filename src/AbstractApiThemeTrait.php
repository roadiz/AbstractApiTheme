<?php

namespace Themes\AbstractApiTheme;

use Pimple\Container;

trait AbstractApiThemeTrait
{
    /**
     * @param Container $container
     * @return void
     */
    public static function setupDependencyInjection(Container $container)
    {
        parent::setupDependencyInjection($container);
    }
}
