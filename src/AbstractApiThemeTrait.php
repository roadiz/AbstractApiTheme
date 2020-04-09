<?php


namespace Themes\AbstractApiTheme;

use Pimple\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

trait AbstractApiThemeTrait
{
    /**
     * @param Request  $request
     * @param Response $response
     * @param int      $minutes
     *
     * @return Response
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
