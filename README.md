# Abstract API theme

**Base theme for createing simple public RESTful API protected with referrer API keys.**

[![Build Status](https://travis-ci.org/roadiz/AbstractApiTheme.svg?branch=master)](https://travis-ci.org/roadiz/AbstractApiTheme)

*OAuth2* classes and logic are highly based on [trikoder/oauth2-bundle](https://github.com/trikoder/oauth2-bundle)
which implemented [thephpleague/oauth2-server](https://github.com/thephpleague/oauth2-server) to 
Symfony ecosystem.

## Configuration

### Registering API theme

- Add API base services to your project `app/AppKernel.php`:

```php
# AppKernel.php
/**
 * {@inheritdoc}
 */
public function register(\Pimple\Container $container)
{
    parent::register($container);

    /*
     * Add your own service providers.
     */
    $container->register(new \Themes\AbstractApiTheme\Services\AbstractApiServiceProvider());
}
```

or in your `config.yml`:

```yaml
additionalServiceProviders:
    - \Themes\AbstractApiTheme\Services\AbstractApiServiceProvider
```

- Register this abstract theme to enable its routes

```yaml
themes:
    - classname: \Themes\AbstractApiTheme\AbstractApiThemeApp
      hostname: '*'
      routePrefix: ''
```

- Create a new theme with your API logic by extending `AbstractApiThemeApp`
- **or** use `AbstractApiThemeTrait` in your custom theme app if you already inherits from
an other middleware theme,
- and add the API authentication scheme to Roadiz’ firewall-map…

### Choose between simple *API-Key* or full *OAuth2* authentication schemes

- *API-key* scheme is meant to control your **public** API usage using a Referer regex and *non-expiring* api-key. This is a very light protection that will only work from a browser and should only be used with public data.
- *OAuth2* scheme will secure your API behind Authentication and Authorization middlewares with a short-living *access-token*.

```php
<?php
declare(strict_types=1);

namespace Themes\MyApiTheme;

use Symfony\Component\HttpFoundation\RequestMatcher;
use Pimple\Container;
use Themes\AbstractApiTheme\AbstractApiThemeTrait;

class MyApiThemeApp extends FrontendController
{
    use AbstractApiThemeTrait;

    protected static $themeName = 'My API theme';
    protected static $themeAuthor = 'REZO ZERO';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'MyApiTheme';
    protected static $backendTheme = false;
    
    public static $priority = 10;
    
    /**
     * @inheritDoc
     */
    public static function addDefaultFirewallEntry(Container $container)
    {
        /*
         * API MUST be the first request matcher
         */
        $requestMatcher = new RequestMatcher(
            '^'.preg_quote($container['api.prefix']).'/'.preg_quote($container['api.version'])
        );

        $container['accessMap']->add(
            $requestMatcher,
            [$container['api.base_role']]
        );

        /*
         * Add default API firewall entry.
         */
        $container['firewallMap']->add(
            $requestMatcher, // launch firewall rules for any request within /api/1.0 path
            [$container['api.firewall_listener']],
            $container['api.exception_listener'] // do not forget to add exception listener to enforce accessMap rules
        );
        /*
         * OR add OAuth2 API firewall entry.
         */
        // $container['firewallMap']->add(
        //     $requestMatcher, // launch firewall rules for any request within /api/1.0 path
        //     [$container['api.oauth2_firewall_listener']],
        //     $container['api.exception_listener'] // do not forget to add exception listener to enforce accessMap rules
        // );

        // Do not forget to register default frontend entries
        // AFTER API not to lose preview feature
        parent::addDefaultFirewallEntry($container);
    }
}
```

- Create new roles `ROLE_ADMIN_API` and `ROLE_API` to enable API access and administration section
- Update your database schema to add `Applications` table. 

```shell
bin/roadiz orm:schema-tool:update --dump-sql --force
```

### Enable grant types for your website

If you opted for OAuth2 applications, you must enable grant-type(s) for the Authorization server before
going further: just *extend* the `AuthorizationServer::class` Roadiz service as below:

```php
/*
 * Enable grant types
 */
$container->extend(AuthorizationServer::class, function (AuthorizationServer $server) {
    // Enable the client credentials grant on the server
    $server->enableGrantType(
        new \League\OAuth2\Server\Grant\ClientCredentialsGrant(),
        new \DateInterval('PT1H') // access tokens will expire after 1 hour
    );
    return $server;
});
```

## Create a new application

Applications hold your API keys and control incoming requests `Referer` against a *regex* pattern.

### Confidential applications: *OAuth2*

#### Reserved roles / scope

- `preview` scope will be converted to `ROLE_BACKEND_USER` which is the required role name to access unpublished nodes.

## Generic Roadiz API

### API Route listing

- `/api/1.0` entry point will list all available routes

### User detail entry point

- `/api/1.0/me` entry point will display details about your Application / User

### Listing nodes-sources

If you created a `Event` node-type, API content will be available at `/api/1.0/event` endpoint.

Note: In listing context, only node-type-fields from *default* group will be exposed.

### Getting node-source details

For each node-source, API will expose detailed content on `/api/1.0/event/{id}` and `/api/1.0/event/by-slug/{slug}` endpoints.

### Listing node-source children

For safety reasons, we do not embed node-sources children automatically. We invite you to use [TreeWalker](https://github.com/rezozero/tree-walker) library to extend your JSON serialization to build a safe graph for each of your node-types.

```php
$blockWalker = BlockNodeSourceWalker::build(
    $nodeSource,
    $this->get(NodeSourceWalkerContext::class),
    4, // max graph level
    $this->get('nodesSourcesUrlCacheProvider')
);
$visitor->visitProperty(
    new StaticPropertyMetadata(
        'Collection',
        'children',
        [],
        array_merge($context->getAttribute('groups'), [
            'walker',
            'children'
        ])
    ),
    $blockWalker->getChildren()
);
```

### Filters

- itemsPerPage: `int`
- page: `int`
- _locale: `string`
- search: `string`
- order: `array` Example `order[publishedAt]: DESC` with values:
    - `ASC`
    - `DESC`
- archive: `string` Example `archive: 2019-02` or `archive: 2019`. This parameter only works on `publishedAt` field

On `NodesSources` content: 

- title: `string`
- publishedAt: `DateTime` or `array` with :
    - `after`
    - `before`
    - `strictly_after`
    - `strictly_before`
- tags: `array<string>`
- tagExclusive: `bool`
- node.parent: `int` or `string` (node-name)
- node.visible: `bool`
- node.nodeType.reachable: `bool`

Plus **any** date, datetime and boolean node-type fields which are **indexed**.
