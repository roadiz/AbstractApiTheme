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
use RZ\Roadiz\Preview\PreviewResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\String\UnicodeString;
use Themes\AbstractApiTheme\Subscriber\CachableApiResponseSubscriber;

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
     * @return void
     */
    public static function addDefaultFirewallEntry(Container $container)
    {
        // do nothing
    }

    /**
     * @param array|string $scope
     * @return void
     */
    protected function denyAccessUnlessScopeGranted($scope)
    {
        if (is_array($scope)) {
            $scope = array_map(function (string $singleScope) {
                return (new UnicodeString($singleScope))
                    ->replace(' ', '_')
                    ->replace('-', '_')
                    ->replace('.', '_')
                    ->replace(':', '_')
                    ->prepend($this->get('api.oauth2_role_prefix'))
                    ->upper()
                    ->toString();
            }, $scope);
        }
        $this->denyAccessUnlessGranted($scope, null, 'Insufficient scopes');
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param int $minutes
     * @param bool $allowClientCache
     * @return Response
     */
    public function makeResponseCachable(
        Request $request,
        Response $response,
        int $minutes,
        bool $allowClientCache = false
    ) {
        /** @var Kernel $kernel */
        $kernel = $this->get('kernel');
        /** @var RequestStack $requestStack */
        $requestStack = $kernel->get('requestStack');
        /** @var Settings $settings */
        $settings = $this->get('settingsBag');
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->get('dispatcher');
        /** @var PreviewResolverInterface $previewResolver */
        $previewResolver = $this->get(PreviewResolverInterface::class);
        if (!$previewResolver->isPreview() &&
            !$kernel->isDebug() &&
            $requestStack->getMasterRequest() === $request &&
            $request->isMethodCacheable() &&
            $minutes > 0 &&
            !$settings->get('maintenance_mode', false)) {
            $dispatcher->addSubscriber(
                new CachableApiResponseSubscriber($minutes, true, $allowClientCache)
            );
        } else {
            $dispatcher->addSubscriber(
                new CachableApiResponseSubscriber(0, false, false)
            );
        }

        return $response;
    }
}
