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
use Themes\AbstractApiTheme\Entity\Application;
use Themes\AbstractApiTheme\Extractor\ApplicationExtractor;
use Themes\AbstractApiTheme\Security\Authentication\Provider\AuthenticationProvider;
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
        $container['api.base_role'] = 'ROLE_API';

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
         * @return AuthenticationProvider
         */
        $container['api.authentication_manager'] = function (Container $c) {
            return new AuthenticationProvider($c['api.application_extractor']);
        };

        /**
         * @param Container $c
         *
         * @return ApplicationListener
         */
        $container['api.firewall_listener'] = function (Container $c) {
            return new ApplicationListener(
                $c['api.authentication_manager'],
                $c['securityTokenStorage'],
                $c['api.application_extractor']
            );
        };

        $container['api.application_factory'] = $container->factory(function ($c) {
            return new Application($c['api.base_role'], $c['config']["appNamespace"]);
        });
    }
}