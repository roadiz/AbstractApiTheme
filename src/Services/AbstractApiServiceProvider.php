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
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Routing\RouteCollection;
use Themes\AbstractApiTheme\Entity\Application;
use Themes\AbstractApiTheme\Extractor\ApplicationExtractor;
use Themes\AbstractApiTheme\Security\Authentication\Provider\AuthenticationProvider;
use Themes\AbstractApiTheme\Security\Firewall\ApplicationListener;
use Themes\AbstractApiTheme\src\Routing\ApiRouteCollection;
use Themes\AbstractApiTheme\src\Serialization\EntityListManagerSubscriber;
use Themes\AbstractApiTheme\src\Serialization\NodeSourceApiSubscriber;

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
         * @return string
         */
        $container['api.version'] = '1.0';
        /**
         * @param Container $c
         *
         * @return string
         */
        $container['api.prefix'] = '/api';

        /**
         * @return null|array
         */
        $container['api.node_type_whitelist'] = null;

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

        $container['api.route_collection'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return new ApiRouteCollection(
                $c['nodeTypesBag'],
                $c['settingsBag'],
                $c['stopwatch'],
                $kernel->isPreview(),
                $c['api.prefix'],
                $c['api.version'],
                $c['api.node_type_whitelist']
            );
        };

        $container->extend('authenticationProviderList', function (array $list, Container $c) {
            $list[] = $c['api.authentication_manager'];
            return $list;
        });

        $container->extend('doctrine.relative_entities_paths', function (array $paths) {
            return array_filter(array_unique(array_merge($paths, [
                '../vendor/roadiz/abstract-api-theme/src/Entity'
            ])));
        });

        $container->extend('routeCollection', function (RouteCollection $routeCollection, Container $c) {
            $c['api.route_collection']->parseResources();
            $routeCollection->addCollection($c['api.route_collection']);
            return $routeCollection;
        });

        $container->extend('serializer.subscribers', function (array $subscribers, $c) {
            $subscribers[] = new EntityListManagerSubscriber($c['requestStack']);
            $subscribers[] = new NodeSourceApiSubscriber($c['router']);
            return $subscribers;
        });
    }
}
