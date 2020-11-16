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

        $container->extend('backoffice.entries', function (array $entries, Container $c) {
            $entries['api'] = [
                'name' => 'api.menu',
                'path' => null,
                'icon' => 'uk-icon-gears',
                'roles' => ['ROLE_ADMIN_API'],
                'subentries' => [
                    'applications' => [
                        'name' => 'api.menu.applications',
                        'path' => $c['urlGenerator']->generate('adminApiApplications'),
                        'icon' => 'uk-icon-gears',
                        'roles' => ['ROLE_ADMIN_API'],
                    ],
                ]
            ];

            return $entries;
        });
    }
}
