<?php
/**
 * AbstractApiTheme - AbstractApiThemeApp.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-01-03
 */
declare(strict_types=1);

namespace Themes\AbstractApiTheme;

use Pimple\Container;
use RZ\Roadiz\CMS\Controllers\FrontendController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AbstractApiThemeApp extends FrontendController
{
    protected static $themeName = 'Abstract Api theme';
    protected static $themeAuthor = 'REZO ZERO';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'AbstractApiTheme';
    protected static $backendTheme = false;
    public static $priority = 9;

    /**
     * @param Response $response
     *
     * @return Response
     */
    protected function restrictToDomains(Response $response): Response
    {
        /** @var Request $request */
        $request = $this->get('requestStack')->getMasterRequest();
        $response->headers->add([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET',
            'Vary' => ['Access-Control-Allow-Origin', 'x-api-key', 'Referer'],
        ]);

        return $response;
    }

    /**
     * @inheritDoc
     */
    public static function addDefaultFirewallEntry(Container $container)
    {
        // do nothing
    }


    public static function setupDependencyInjection(Container $container)
    {
        parent::setupDependencyInjection($container);

        $container->extend('backoffice.entries', function (array $entries, $c) {
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