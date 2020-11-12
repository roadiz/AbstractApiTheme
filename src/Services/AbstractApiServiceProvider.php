<?php
/**
 * AbstractApiTheme - AbstractApiServiceProvider.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-01-03
 */
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Services;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Kernel;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouteCollection;
use Themes\AbstractApiTheme\Entity\Application;
use Themes\AbstractApiTheme\Extractor\ApplicationExtractor;
use Themes\AbstractApiTheme\OptionsResolver\ApiRequestOptionsResolver;
use Themes\AbstractApiTheme\Security\Authentication\Provider\AuthenticationProvider;
use Themes\AbstractApiTheme\Security\Firewall\ApplicationListener;
use Themes\AbstractApiTheme\Routing\ApiRouteCollection;
use Themes\AbstractApiTheme\Serialization\ChildrenApiSubscriber;
use Themes\AbstractApiTheme\Serialization\EntityListManagerSubscriber;
use Themes\AbstractApiTheme\Serialization\NodeSourceApiSubscriber;
use Themes\AbstractApiTheme\Serialization\TagTranslationNameSubscriber;

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
         * @return int
         */
        $container['api.cache.ttl'] = 5;

        /**
         * @param Container $c
         *
         * @return string
         */
        $container['api.application_class'] = function (Container $c) {
            return Application::class;
        };

        /**
         * @param Container $c
         *
         * @return ApplicationExtractor
         */
        $container['api.application_extractor'] = function (Container $c) {
            return new ApplicationExtractor($c, $c['api.application_class']);
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

        $container[ApiRequestOptionsResolver::class] = $container->factory(function ($c) {
            return new ApiRequestOptionsResolver(
                $c['defaultTranslation']->getLocale(),
                $c['tagApi'],
                $c['nodeApi']
            );
        });

        $container['api.application_factory'] = $container->factory(function ($c) {
            $className = $c['api.application_class'];
            return new $className($c['api.base_role'], $c['config']["appNamespace"]);
        });

        $container['api.reference_type'] = function () {
            return UrlGeneratorInterface::ABSOLUTE_URL;
        };

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
            $subscribers[] = new NodeSourceApiSubscriber($c['router'], $c['api.reference_type']);
            $subscribers[] = new ChildrenApiSubscriber($c['em']);
            $subscribers[] = new TagTranslationNameSubscriber();
            return $subscribers;
        });
    }
}
