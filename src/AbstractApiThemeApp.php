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
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;

class AbstractApiThemeApp extends FrontendController
{
    protected static $themeName = 'Abstract Api theme';
    protected static $themeAuthor = 'REZO ZERO';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'AbstractApiTheme';
    protected static $backendTheme = false;
    public static $priority = 9;

    /**
     * @inheritDoc
     */
    public function makeResponseCachable(Request $request, Response $response, $minutes)
    {
        $response = parent::makeResponseCachable($request, $response, $minutes);

        /** @var Request $request */
        $response->headers->add([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET',
        ]);
        $response->setVary('Accept-Encoding, X-Partial, x-requested-with, Access-Control-Allow-Origin, x-api-key, Referer');

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
