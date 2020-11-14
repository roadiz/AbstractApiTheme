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
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractApiTheme\Events\CachableApiResponseSubscriber;

class AbstractApiThemeApp extends FrontendController
{
    use AbstractApiThemeTrait;

    protected static $themeName = 'Abstract Api theme';
    protected static $themeAuthor = 'REZO ZERO';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'AbstractApiTheme';
    protected static $backendTheme = false;
    public static $priority = 9;

    /**
     * @inheritDoc
     */
    public static function addDefaultFirewallEntry(Container $container)
    {
        // do nothing
    }

    /**
     * @param array|string $scope
     */
    protected function denyAccessUnlessScopeGranted($scope)
    {
        if (is_array($scope)) {
            $scope = array_map(function (string $singleScope) {
                return strtoupper($this->get('api.oauth2_role_prefix') . $singleScope);
            }, $scope);
        }
        $this->denyAccessUnlessGranted($scope);
    }

    /**
     * Make current response cacheable by reverse proxy and browsers.
     *
     * Pay attention that, some reverse proxies systems will need to remove your response
     * cookies header to actually save your response.
     *
     * Do not cache, if
     * - we are in preview mode
     * - we are in debug mode
     * - Request forbids cache
     * - we are in maintenance mode
     * - this is a sub-request
     *
     * @param Request $request
     * @param Response $response
     * @param int $minutes TTL in minutes
     *
     * @return Response
     */
    public function makeResponseCachable(Request $request, Response $response, $minutes)
    {
        /** @var Kernel $kernel */
        $kernel = $this->get('kernel');
        /** @var RequestStack $requestStack */
        $requestStack = $kernel->get('requestStack');
        /** @var Settings $settings */
        $settings = $this->get('settingsBag');
        if (!$kernel->isPreview() &&
            !$kernel->isDebug() &&
            $requestStack->getMasterRequest() === $request &&
            $request->isMethodCacheable() &&
            $minutes > 0 &&
            !$settings->get('maintenance_mode', false)) {
            /** @var EventDispatcherInterface $dispatcher */
            $dispatcher = $this->get('dispatcher');
            $dispatcher->addSubscriber(new CachableApiResponseSubscriber($minutes, true));
        }

        return $response;
    }
}
