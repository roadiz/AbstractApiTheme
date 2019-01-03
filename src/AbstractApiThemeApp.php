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
    /**
     * {@inheritdoc}
     */
    public static $priority = 10;

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
            'Vary' => 'Access-Control-Allow-Origin',
        ]);

        return $response;
    }

    /**
     * @inheritDoc
     */
    public static function addDefaultFirewallEntry(Container $container)
    {
        /*
         * Add default API firewall entry.
         */
        $container['firewallMap']->add(
            $container['api.request_matcher'],
            $container['api.firewall_listener']
        );

        $container['accessMap']->add(
            $container['api.request_matcher'],
            $container['api.base_role']
        );
    }
}