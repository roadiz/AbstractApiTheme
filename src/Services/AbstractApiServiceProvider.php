<?php
/**
 * AbstractApiTheme - AbstractApiServiceProvider.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-01-03
 */
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Services;

use Defuse\Crypto\Key;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Kernel;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Themes\AbstractApiTheme\Controllers\NodeTypeApiController;
use Themes\AbstractApiTheme\Controllers\RootApiController;
use Themes\AbstractApiTheme\Entity\Application;
use Themes\AbstractApiTheme\Extractor\ApplicationExtractor;
use Themes\AbstractApiTheme\OAuth2\Repository\AccessTokenRepository;
use Themes\AbstractApiTheme\OAuth2\Repository\ClientRepository;
use Themes\AbstractApiTheme\OAuth2\Repository\ScopeRepository;
use Themes\AbstractApiTheme\OptionsResolver\ApiRequestOptionsResolver;
use Themes\AbstractApiTheme\Security\Authentication\Provider\AuthenticationProvider;
use Themes\AbstractApiTheme\Security\Authentication\Token\OAuth2TokenFactory;
use Themes\AbstractApiTheme\Security\Firewall\ApplicationListener;
use Themes\AbstractApiTheme\Routing\ApiRouteCollection;
use Themes\AbstractApiTheme\Security\Firewall\OAuth2Listener;
use Themes\AbstractApiTheme\Serialization\ChildrenApiSubscriber;
use Themes\AbstractApiTheme\Serialization\EntityListManagerSubscriber;
use Themes\AbstractApiTheme\Serialization\NodeSourceApiSubscriber;
use Themes\AbstractApiTheme\Serialization\TagTranslationNameSubscriber;
use Themes\AbstractApiTheme\Serialization\TokenSubscriber;

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

        $container['api.oauth2_role_prefix'] = 'ROLE_OAUTH2_';

        /**
         * @param Container $c
         * @return string
         */
        $container['api.oauth2_private_key_path'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return 'file://' . $kernel->getRootDir() . '/jwt/private.pem';
        };

        /**
         * @param Container $c
         * @return string|null
         */
        $container['api.oauth2_encryption_key'] = function (Container $c) {
            return getenv('DEFUSE_KEY') ?? null;
        };

        /**
         * @param Container $c
         * @return string|null
         */
        $container['api.oauth2_jwt_passphrase'] = function (Container $c) {
            return getenv('JWT_PASSPHRASE') ?? null;
        };

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
         * @return string
         */
        $container['api.root_controller_class'] = RootApiController::class;

        /**
         * @return string
         */
        $container['api.node_type_controller_class'] = NodeTypeApiController::class;

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

        /**
         * @param Container $c
         * @return OAuth2Listener
         */
        $container['api.oauth2_firewall_listener'] = function (Container $c) {
            return new OAuth2Listener(
                $c['securityTokenStorage'],
                $c['api.authentication_manager'],
                $c[HttpMessageFactoryInterface::class],
                $c[OAuth2TokenFactory::class],
                Kernel::SECURITY_DOMAIN
            );
        };

        $container['api.exception_listener'] = function (Container $c) {
            return new ExceptionListener(
                $c['securityTokenStorage'],
                $c['securityAuthenticationTrustResolver'],
                $c['httpUtils'],
                Kernel::SECURITY_DOMAIN,
                null,
                null,
                null,
                $c['logger'],
                true
            );
        };

        $container[HttpMessageFactoryInterface::class] = function () {
            $psr17Factory = new Psr17Factory();
            return new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
        };

        $container[OAuth2TokenFactory::class] = function (Container $c) {
            return new OAuth2TokenFactory($c['api.oauth2_role_prefix']);
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
                $c['api.node_type_whitelist'],
                $c['api.root_controller_class'],
                $c['api.node_type_controller_class']
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
            $subscribers[] = new TokenSubscriber();
            return $subscribers;
        });

        /**
         * @param Container $c
         * @return ClientRepository
         */
        $container[ClientRepositoryInterface::class] = function (Container $c) {
            return new ClientRepository($c['em']);
        };

        /**
         * @param Container $c
         * @return AccessTokenRepository
         */
        $container[AccessTokenRepositoryInterface::class] = function (Container $c) {
            return new AccessTokenRepository($c['em']);
        };

        /**
         * @param Container $c
         * @return ScopeRepository
         */
        $container[ScopeRepositoryInterface::class] = function (Container $c) {
            return new ScopeRepository($c['em']);
        };

        $container[AuthorizationServer::class] = function (Container $c) {
            return new AuthorizationServer(
                $c[ClientRepositoryInterface::class],
                $c[AccessTokenRepositoryInterface::class],
                $c[ScopeRepositoryInterface::class],
                new CryptKey($c['api.oauth2_private_key_path'], $c['api.oauth2_jwt_passphrase']),
                Key::loadFromAsciiSafeString($c['api.oauth2_encryption_key'])
            );
        };
    }
}
