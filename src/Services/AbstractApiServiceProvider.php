<?php
/**
 * AbstractApiTheme - AbstractApiServiceProvider.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-01-03
 */
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Services;

use Doctrine\ORM\EntityRepository;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Themes\AbstractApiTheme\Entity\Application;
use Themes\AbstractApiTheme\Extractor\ApplicationExtractor;
use Themes\AbstractApiTheme\Security\Firewall\ApplicationListener;

class AbstractApiServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     */
    public function register(Container $container)
    {
        /**
         * @param Container $c
         *
         * @return string
         */
        $container['api.base_role'] = function (Container $c) {
            return 'ROLE_API';
        };

        /**
         * @param Container $c
         *
         * @return RequestMatcher
         */
        $container['api.request_matcher'] = function (Container $c) {
            return new RequestMatcher('^/api/1.0/');
        };

        /**
         * @param Container $c
         *
         * @return EntityRepository
         */
        $container['api.application_repository'] = function (Container $c) {
            return $c['em']->getRepository(Application::class);
        };

        /**
         * @param Container $c
         *
         * @return ApplicationExtractor
         */
        $container['api.application_extractor'] = function (Container $c) {
            return new ApplicationExtractor($c['api.application_repository']);
        };

        /**
         * @param Container $c
         *
         * @return ApplicationListener
         */
        $container['api.firewall_listener'] = function (Container $c) {
            return new ApplicationListener(
                $c['authentificationManager'],
                $c['securityTokenStorage'],
                $c['api.application_extractor']
            );
        };

        $container['api.application_factory'] = $container->factory(function ($c) {
            return new Application($c['api.base_role'], $c['config']["appNamespace"]);
        });
    }
}