<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Services;

use Defuse\Crypto\Key;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use RZ\Roadiz\Core\Kernel;
use RZ\Roadiz\Core\Routing\NodesSourcesPathResolver;
use RZ\Roadiz\JWT\JwtConfigurationFactory;
use RZ\Roadiz\Utils\Security\FirewallEntry;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Security\Http\Firewall\ExceptionListener;
use Symfony\Component\Security\Http\FirewallMap;
use Symfony\Component\Translation\Translator;
use Themes\AbstractApiTheme\Controllers\NodesSourcesListingApiController;
use Themes\AbstractApiTheme\Controllers\NodesSourcesSearchApiController;
use Themes\AbstractApiTheme\Controllers\NodeTypeArchivesApiController;
use Themes\AbstractApiTheme\Controllers\NodeTypeListingApiController;
use Themes\AbstractApiTheme\Controllers\NodeTypeSingleApiController;
use Themes\AbstractApiTheme\Controllers\NodeTypeTagsApiController;
use Themes\AbstractApiTheme\Controllers\RootApiController;
use Themes\AbstractApiTheme\Controllers\UserApiController;
use Themes\AbstractApiTheme\Converter\ScopeConverter;
use Themes\AbstractApiTheme\Converter\UserConverter;
use Themes\AbstractApiTheme\Converter\UserConverterInterface;
use Themes\AbstractApiTheme\Entity\Application;
use Themes\AbstractApiTheme\Event\AuthorizationRequestResolveEventFactory;
use Themes\AbstractApiTheme\Extractor\ApplicationExtractor;
use Themes\AbstractApiTheme\Form\RoleNameType;
use Themes\AbstractApiTheme\OAuth2\JwtRequestFactory;
use Themes\AbstractApiTheme\OAuth2\OAuth2JwtConfigurationFactory;
use Themes\AbstractApiTheme\OAuth2\Repository\AccessTokenRepository;
use Themes\AbstractApiTheme\OAuth2\Repository\AuthCodeRepository;
use Themes\AbstractApiTheme\OAuth2\Repository\ClientRepository;
use Themes\AbstractApiTheme\OAuth2\Repository\RefreshTokenRepository;
use Themes\AbstractApiTheme\OAuth2\Repository\ScopeRepository;
use Themes\AbstractApiTheme\OptionsResolver\ApiRequestOptionsResolver;
use Themes\AbstractApiTheme\OptionsResolver\TagApiRequestOptionsResolver;
use Themes\AbstractApiTheme\Routing\ApiRouteCollection;
use Themes\AbstractApiTheme\Security\Authentication\Provider\AuthenticationProvider;
use Themes\AbstractApiTheme\Security\Authentication\Provider\OAuth2Provider;
use Themes\AbstractApiTheme\Security\Authentication\Token\OAuth2TokenFactory;
use Themes\AbstractApiTheme\Security\Firewall\ApplicationListener;
use Themes\AbstractApiTheme\Security\Firewall\OAuth2Listener;
use Themes\AbstractApiTheme\Serialization\ChildrenApiSubscriber;
use Themes\AbstractApiTheme\Serialization\DocumentApiSubscriber;
use Themes\AbstractApiTheme\Serialization\EntityListManagerSubscriber;
use Themes\AbstractApiTheme\Serialization\NodeSourceApiSubscriber;
use Themes\AbstractApiTheme\Serialization\NodeSourceUriSubscriber;
use Themes\AbstractApiTheme\Serialization\SeoDataSubscriber;
use Themes\AbstractApiTheme\Serialization\SerializationContextFactory;
use Themes\AbstractApiTheme\Serialization\SerializationContextFactoryInterface;
use Themes\AbstractApiTheme\Serialization\TagApiSubscriber;
use Themes\AbstractApiTheme\Serialization\TagTranslationNameSubscriber;
use Themes\AbstractApiTheme\Serialization\TokenSubscriber;
use Themes\AbstractApiTheme\Subscriber\AuthorizationRequestSubscriber;
use Themes\AbstractApiTheme\Subscriber\CorsSubscriber;
use Themes\AbstractApiTheme\Subscriber\LinkedApiResponseSubscriber;
use Themes\AbstractApiTheme\Subscriber\RoadizUserRoleResolveSubscriber;
use Twig\Loader\FilesystemLoader;

class AbstractApiServiceProvider implements ServiceProviderInterface
{
    /**
     * @inheritDoc
     * @return void
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

        $container[RoleNameType::class] = function (Container $c) {
            return new RoleNameType(
                $c['api.oauth2_role_prefix'],
                $c['api.base_role'],
                $c['em'],
            );
        };

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
         * @return string
         */
        $container['api.oauth2_public_key_path'] = function (Container $c) {
            /** @var Kernel $kernel */
            $kernel = $c['kernel'];
            return 'file://' . $kernel->getRootDir() . '/jwt/public.pem';
        };

        /**
         * @param Container $c
         * @return string|null
         */
        $container['api.oauth2_encryption_key'] = function (Container $c) {
            return $_ENV['DEFUSE_KEY'] ?? null;
        };

        /**
         * @param Container $c
         * @return string|null
         */
        $container['api.oauth2_jwt_passphrase'] = function (Container $c) {
            return $_ENV['JWT_PASSPHRASE'] ?? null;
        };

        $container['api.oauth2_private_key'] = function (Container $c) {
            return new CryptKey($c['api.oauth2_private_key_path'], $c['api.oauth2_jwt_passphrase']);
        };

        $container['api.oauth2_public_key'] = function (Container $c) {
            return new CryptKey($c['api.oauth2_public_key_path']);
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
         * @return bool
         */
        $container['api.use_cache_tags'] = true;

        /**
         * @return class-string
         */
        $container['api.root_controller_class'] = RootApiController::class;
        /**
         * @return class-string
         */
        $container['api.node_type_single_controller_class'] = NodeTypeSingleApiController::class;
        /**
         * @return class-string
         */
        $container['api.node_type_listing_controller_class'] = NodeTypeListingApiController::class;
        /**
         * @return class-string
         */
        $container['api.node_type_archives_controller_class'] = NodeTypeArchivesApiController::class;
        /**
         * @return class-string
         */
        $container['api.node_type_tags_controller_class'] = NodeTypeTagsApiController::class;
        /**
         * @return class-string
         */
        $container['api.nodes_sources_listing_controller_class'] = NodesSourcesListingApiController::class;
        /**
         * @return class-string
         */
        $container['api.nodes_sources_search_controller_class'] = NodesSourcesSearchApiController::class;
        /**
         * @return class-string
         */
        $container['api.user_controller_class'] = UserApiController::class;
        /**
         * @return int
         */
        $container['api.node_source_uri_reference_type'] = UrlGeneratorInterface::ABSOLUTE_PATH;

        /**
         * @return int in minutes
         */
        $container['api.cache.ttl'] = 5;

        /**
         * @return array
         */
        $container['api.cors_options'] = [
            'allow_credentials' => true,
            'allow_origin' => ['*'],
            'allow_headers' => true,
            'origin_regex' => false,
            'allow_methods' => ['GET'],
            'expose_headers' => [],
            'max_age' => 60*60*24
        ];

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
         * @return OAuth2Provider
         */
        $container['api.oauth2_authentication_manager'] = function (Container $c) {
            return new OAuth2Provider(
                $c['userProvider'],
                $c[ResourceServer::class],
                $c[OAuth2TokenFactory::class],
                Kernel::SECURITY_DOMAIN
            );
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
                $c['api.oauth2_authentication_manager'],
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

        /**
         * @param Container $c
         * @return OAuth2JwtConfigurationFactory
         */
        $container['api.oauth2_configuration_factory'] = function (Container $c) {
            /** @var CryptKey $cryptKey */
            $cryptKey = $c['api.oauth2_private_key'];
            /** @var CryptKey $publicKey */
            $publicKey = $c['api.oauth2_public_key'];
            return new OAuth2JwtConfigurationFactory(
                $cryptKey->getKeyPath(),
                $publicKey->getKeyPath(),
                $cryptKey->getPassPhrase()
            );
        };

        $container[JwtRequestFactory::class] = function (Container $c) {
            /** @var JwtConfigurationFactory $jwtConfigurationFactory */
            $jwtConfigurationFactory = $c['api.oauth2_configuration_factory'];
            return new JwtRequestFactory($jwtConfigurationFactory->create());
        };

        $container[OAuth2TokenFactory::class] = function (Container $c) {
            return new OAuth2TokenFactory(
                $c['api.oauth2_role_prefix'],
                $c['api.base_role']
            );
        };

        $container[ApiRequestOptionsResolver::class] = $container->factory(function ($c) {
            return new ApiRequestOptionsResolver(
                $c['defaultTranslation']->getLocale(),
                $c['tagApi'],
                $c['nodeApi'],
                $c[NodesSourcesPathResolver::class],
                $c['em']
            );
        });

        $container[TagApiRequestOptionsResolver::class] = $container->factory(function ($c) {
            return new TagApiRequestOptionsResolver(
                $c['defaultTranslation']->getLocale(),
                $c['tagApi'],
                $c['nodeApi']
            );
        });

        $container[SerializationContextFactoryInterface::class] = function (Container $c) {
            return new SerializationContextFactory($c['api.use_cache_tags']);
        };

        $container['api.application_factory'] = $container->factory(function ($c) {
            $className = $c['api.application_class'];
            return new $className($c['api.base_role'], $c['config']["appNamespace"]);
        });

        $container['api.reference_type'] = function () {
            return UrlGeneratorInterface::ABSOLUTE_URL;
        };

        $container['api.route_collection'] = function (Container $c) {
            return new ApiRouteCollection(
                $c['nodeTypesBag'],
                $c['settingsBag'],
                $c['stopwatch'],
                $c['api.prefix'],
                $c['api.version'],
                $c['api.node_type_whitelist'],
                $c['api.root_controller_class'],
                $c['api.node_type_listing_controller_class'],
                $c['api.node_type_single_controller_class'],
                $c['api.user_controller_class'],
                $c['api.node_type_tags_controller_class'],
                $c['api.nodes_sources_listing_controller_class'],
                $c['api.nodes_sources_search_controller_class'],
                $c['api.node_type_archives_controller_class']
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

        $container['api.file_locator'] = function (Container $c) {
            $resourcesFolder = dirname(__DIR__) . '/Resources';
            return new FileLocator([
                $resourcesFolder,
                $resourcesFolder . '/routing',
                $resourcesFolder . '/config',
            ]);
        };

        $container->extend('routeCollection', function (RouteCollection $routeCollection, Container $c) {
            $c['api.route_collection']->parseResources();
            $loader = new YamlFileLoader($c['api.file_locator']);
            $routeCollection->addCollection($c['api.route_collection']);
            $routeCollection->addCollection($loader->load('routes.yml'));
            return $routeCollection;
        });

        $container->extend('serializer.subscribers', function (array $subscribers, $c) {
            $subscribers[] = new NodeSourceUriSubscriber($c['router'], $c['api.node_source_uri_reference_type']);
            $subscribers[] = new EntityListManagerSubscriber($c['requestStack']);
            $subscribers[] = new NodeSourceApiSubscriber($c['router'], $c['api.reference_type']);
            $subscribers[] = new TagApiSubscriber();
            $subscribers[] = new DocumentApiSubscriber($c['assetPackages']);
            $subscribers[] = new ChildrenApiSubscriber($c['em']);
            $subscribers[] = new SeoDataSubscriber(
                $c['settingsBag']->get('site_name', ''),
                $c['settingsBag']->get('seo_description', $c['settingsBag']->get('site_name', ''))
            );
            $subscribers[] = new TagTranslationNameSubscriber();
            $subscribers[] = new TokenSubscriber();
            return $subscribers;
        });

        $container->extend('dispatcher', function (EventDispatcherInterface $dispatcher, Container $c) {
            $dispatcher->addSubscriber(new CorsSubscriber($c['api.cors_options']));
            $dispatcher->addSubscriber(new LinkedApiResponseSubscriber());
            $dispatcher->addSubscriber(new RoadizUserRoleResolveSubscriber($c['em']));
            $dispatcher->addSubscriber(new AuthorizationRequestSubscriber(
                $c['securityTokenStorage'],
                $c['requestStack'],
                new InlineFragmentRenderer($c['kernel'], $dispatcher)
            ));
            return $dispatcher;
        });

        /**
         * @param Container $c
         * @return ClientRepository
         */
        $container[ClientRepositoryInterface::class] = function (Container $c) {
            return new ClientRepository($c['em']);
        };

        $container[RefreshTokenRepositoryInterface::class] = function (Container $c) {
            return new RefreshTokenRepository();
        };

        /**
         * @param Container $c
         * @return ScopeRepository
         */
        $container[ScopeRepositoryInterface::class] = function (Container $c) {
            return new ScopeRepository(
                $c[ScopeConverter::class],
                $c['dispatcher']
            );
        };

        /**
         * @param Container $c
         * @return AuthCodeRepositoryInterface
         */
        $container[AuthCodeRepositoryInterface::class] = function (Container $c) {
            return new AuthCodeRepository(
                $c['em'],
                $c[ScopeConverter::class],
                $c[ClientRepositoryInterface::class]
            );
        };

        /**
         * @param Container $c
         * @return AccessTokenRepository
         */
        $container[AccessTokenRepositoryInterface::class] = function (Container $c) {
            return new AccessTokenRepository($c['em']);
        };

        $container[AuthorizationRequestResolveEventFactory::class] = function (Container $c) {
            return new AuthorizationRequestResolveEventFactory(
                $c[ScopeRepositoryInterface::class],
                $c[ClientRepositoryInterface::class]
            );
        };

        $container[ScopeConverter::class] = function (Container $c) {
            return new ScopeConverter($c['rolesBag'], $c['api.oauth2_role_prefix'], $c['api.base_role']);
        };

        $container[UserConverterInterface::class] = function () {
            return new UserConverter();
        };

        $container[AuthorizationServer::class] = function (Container $c) {
            return new AuthorizationServer(
                $c[ClientRepositoryInterface::class],
                $c[AccessTokenRepositoryInterface::class],
                $c[ScopeRepositoryInterface::class],
                $c['api.oauth2_private_key'],
                Key::loadFromAsciiSafeString($c['api.oauth2_encryption_key'])
            );
        };

        $container[ResourceServer::class] = function (Container $c) {
            return new ResourceServer(
                $c[AccessTokenRepositoryInterface::class],
                $c['api.oauth2_public_key']
            );
        };

        $container->extend('firewallMap', function (FirewallMap $firewallMap, Container $c) {
            $firewallEntry = new FirewallEntry(
                $c,
                '^/authorize',
                'api_get_authorize',
                'api_get_authorize_login',
                'api_get_authorize_logout',
                'api_get_authorize_check',
                'ROLE_USER'
            );
            $firewallMap->add(
                $firewallEntry->getRequestMatcher(),
                $firewallEntry->getListeners(),
                $firewallEntry->getExceptionListener()
            );
            return $firewallMap;
        });

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

        /*
         * Register Twig namespace
         * not to register AbstractApiTheme into config.themes
         */
        $container->extend('twig.loaderFileSystem', function (FilesystemLoader $loader) {
            $themeDir = dirname(__DIR__) . '/Resources/views';
            /*
            * Enable theme templates in main namespace and in its own theme namespace.
            */
            $loader->prependPath($themeDir);
            // Add path into a namespaced loader to enable using same template name
            // over different static themes.
            $loader->prependPath($themeDir, 'AbstractApiTheme');

            return $loader;
        });

        /*
         * Register custom migrations
         */
        $container->extend('doctrine.migrations_paths', function (array $paths) {
            $migrationPath = \dirname(__DIR__) . '/Migrations';
            if (\file_exists($migrationPath)) {
                $paths['Themes\AbstractApiTheme\Migrations'] = $migrationPath;
            }
            return $paths;
        });

        /*
         * Register translations messages
         * not to register AbstractApiTheme into config.themes
         */
        $container->extend('translator', function (Translator $translator) {
            $translator->addResource(
                'xlf',
                dirname(__DIR__) . '/Resources/translations/messages.en.xlf',
                'en',
                'messages'
            );
            $translator->addResource(
                'xlf',
                dirname(__DIR__) . '/Resources/translations/messages.fr.xlf',
                'fr',
                'messages'
            );
            return $translator;
        });
    }
}
