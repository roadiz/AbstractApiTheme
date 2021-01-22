# Abstract API theme

**Base theme for createing simple public RESTful API protected with referrer API keys.**

[![Build Status](https://travis-ci.org/roadiz/AbstractApiTheme.svg?branch=master)](https://travis-ci.org/roadiz/AbstractApiTheme)

*OAuth2* classes and logic are highly based on [trikoder/oauth2-bundle](https://github.com/trikoder/oauth2-bundle)
which implemented [thephpleague/oauth2-server](https://github.com/thephpleague/oauth2-server) to 
Symfony ecosystem.

- [Configuration](#configuration)
    * [Use .env file](#use-env-file)
    * [Registering API theme](#registering-api-theme)
    * [Choose between simple *API-Key* or full *OAuth2* authentication schemes](#choose-between-simple-api-key-or-full-oauth2-authentication-schemes)
    * [Enable grant types for your website](#enable-grant-types-for-your-website)
    * [Customize CORS](#customize-cors)
- [Create a new application](#create-a-new-application)
    * [Confidential applications: *OAuth2*](#confidential-applications-oauth2)
        + [Reserved roles / scope](#reserved-roles-scope)
- [Generic Roadiz API](#generic-roadiz-api)
    * [API Route listing](#api-route-listing)
    * [OAuth2 entry points](#oauth2-entry-points)
    * [User detail entry point](#user-detail-entry-point)
    * [Listing nodes-sources](#listing-nodes-sources)
        + [Filters](#filters)
    * [Search nodes-sources](#search-nodes-sources)
        + [Filters](#filters-1)
    * [Listing tags per node-types](#listing-tags-per-node-types)
        + [Filters](#filters-2)
    * [Getting node-source details](#getting-node-source-details)
    * [Listing node-source children](#listing-node-source-children)

## Configuration

### Use .env file

This middleware theme uses `symfony/dotenv` to import `.env` variables to your project.
Be sure to create one with at least this configuration:

```dotenv
JWT_PASSPHRASE=changeme
# vendor/bin/generate-defuse-key
DEFUSE_KEY=changeme
```

Your *Roadiz* entry points must initialize `DotEnv` object to fetch this configuration from a `.env` file
our from your system environment (i.e. your *Docker* container environment).

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

- *You do not need to register this abstract theme* to enable its routes or translations
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
going further: just *extend* the `AuthorizationServer::class` Roadiz service as below.

AbstractApiTheme currently supports:

- `client_credentials` grant
- `authorization_code` grant (**without** refresh token)

```php
/*
 * Enable grant types
 */
$container->extend(AuthorizationServer::class, function (AuthorizationServer $server, Container $c) {
    // Enable the client credentials grant on the server
    $server->enableGrantType(
        new \League\OAuth2\Server\Grant\ClientCredentialsGrant(),
        new \DateInterval('PT1H') // access tokens will expire after 1 hour
    );
    // Enable the authorization grant on the server
    $authCodeGrant = new \League\OAuth2\Server\Grant\AuthCodeGrant(
        $c[AuthCodeRepositoryInterface::class],
        $c[RefreshTokenRepositoryInterface::class],
        new \DateInterval('PT10M') // authorization_codes will expire after 10 min
    );
    $server->enableGrantType(
        $authCodeGrant,
        new \DateInterval('PT3H') // access tokens will expire after 3 hours
    );
    return $server;
});
```

### Customize CORS

CORS handling is highly based on [nelmio/NelmioCorsBundle](https://github.com/nelmio/NelmioCorsBundle), options
are just handled as a service you can extend for your website. \
This will automatically intercept requests containing an `Origin` header. Pre-flight requests must be performed
using `OPTIONS` verb and must contain `Origin` and `Access-Control-Request-Method` headers.

```php
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
```

## Create a new application

Applications hold your API keys and control incoming requests `Referer` against a *regex* pattern.

### Confidential applications: *OAuth2*

#### Reserved roles / scope

- `preview` scope will be converted to `ROLE_BACKEND_USER` which is the required role name to access unpublished nodes.

## Generic Roadiz API

### API Route listing

- `/api/1.0` entry point will list all available routes

### OAuth2 entry points

- `GET /authorize` for *authorization code* grant flow (part one)
- `GET /token` for *authorization code* grant flow (part two) and `client_credential` grant flow (only part)

For authorization code grant you will find more detail on [ThePHPLeague OAuth2 Server documentation](https://oauth2.thephpleague.com/authorization-server/auth-code-grant/)

*Authorization code* grant flow will redirect non-authenticated users to `GET /oauth2-login` with the classic
Roadiz login form. You can call `GET /authorize/logout` to force user logout.
Note that *authorization code* grant won't give each application' roles **if logged-in user does not have them before** 
(except for `ROLE_SUPERADMIN`). User will be asked to grant permission on application role **but** 
he won't benefit from them for security reasons (permissions escalation). Make sure your users have the right
roles before inviting them to use your OAuth2 application.

### User detail entry point

- `/api/1.0/me` entry point will display details about your Application / User

### Listing nodes-sources

- `/api/1.0/nodes-sources`: list all nodes-sources no matter type they are.
- `/api/1.0/{node-type-name}`: list nodes-sources by type

If you created a `Event` node-type, API content will be available at `/api/1.0/event` endpoint.
Serialization context will automatically add `@id`, `@type`, `slug` and `url` fields in your API resource:

```json
{
    "hydra:member": [
        {
            "slug": "home",
            "@type": "Page",
            "node": {
                "nodeName": "accueil",
                "tags": []
            },
            "title": "Accueil",
            "publishedAt": "2021-01-18T23:32:39+01:00",
            "@id": "http://example.test/dev.php/api/1.0/page/2",
            "url": "/dev.php/home"
        }
    ],
    "hydra:totalItems": 1,
    "@id": "/api/1.0/page",
    "@type": "hydra:Collection",
    "hydra:view": {
        "@id": "/api/1.0/page",
        "@type": "hydra:PartialCollectionView"
    }
}
```

Note: In listing context, only node-type-fields from *default* group will be exposed.

#### Filters

- itemsPerPage: `int`
- page: `int`
- _locale: `string`
- search: `string`
- order: `array` Example `order[publishedAt]: DESC` with values:
  - `ASC`
  - `DESC`
- archive: `string` Example `archive: 2019-02` or `archive: 2019`. This parameter only works on `publishedAt` field

On `NodesSources` content:

- path: `string` Filters nodes-sources against a valid path (based on node' name or alias), example: `/home`
- id: `id` Nodes-sources ID
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
- node.nodeType: `array|string` Filter nodes-sources by their type
- node.nodeType.reachable: `bool`

Plus **any** date, datetime and boolean node-type fields which are **indexed**.

### Search nodes-sources

- `/api/1.0/nodes-sources/search`: Search all nodes-sources against a `search` param using *Apache Solr* engine

If your search parameter is longer than 3 characters, each API result item will be composed with:

```json
{
    "nodeSource": {
        ...
    },
    "highlighting": {
        "collection_txt": [
            "In aliquam at dignissimos quasi in. Velit et vero non ut quidem. Sunt est <span class=\"solr-highlight\">tempora</span> sed. Rem nam asperiores modi in quidem quia voluptatum. Aliquid ut doloribus sit et ea eum natus. Eius commodi porro"
        ]
    }
}
```

#### Filters

- itemsPerPage: `int`
- page: `int`
- _locale: `string`
- search: `string`
- tags: `array<string>`
- node.parent: `int` or `string` (node-name)
- node.visible: `bool`
- node.nodeType: `array|string` Filter nodes-sources search by their type

### Listing tags per node-types

- `/api/1.0/{node-type-name}/tags`: Fetch all tags used in nodes-sources from a given type.

If you created a `Event` node-type, you may want to list any `Tags` attached to *events*, API will be available at 
`/api/1.0/event/tags` endpoint.

#### Filters

- itemsPerPage: `int`
- page: `int`
- _locale: `string`
- search: `string`: This will search on `tagName` and translation `name`
- order: `array` Example `order[position]: ASC` with values:
  - `ASC`
  - `DESC`

On `Tag` content:

- tagName: `string`
- parent: `int` or `string` (tag-name)
- visible: `bool`

### Getting node-source details

- `/api/1.0/{node-type-name}/{id}`: fetch a node-source with its ID
- `/api/1.0/{node-type-name}/by-slug/{slug}`: fetch a node-source with its slug (`nodeName` or `urlAlias`)

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
