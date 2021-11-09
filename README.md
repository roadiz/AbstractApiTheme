# Abstract API theme
**Exposes Roadiz content as a public REST API.** Mainly used in *Roadiz Headless edition*.

[![Build Status](https://travis-ci.org/roadiz/AbstractApiTheme.svg?branch=master)](https://travis-ci.org/roadiz/AbstractApiTheme)

*OAuth2* classes and logic are highly based on [trikoder/oauth2-bundle](https://github.com/trikoder/oauth2-bundle)
which implemented [thephpleague/oauth2-server](https://github.com/thephpleague/oauth2-server) to 
Symfony ecosystem.

* [Configuration](#configuration)
  + [Use .env file](#use-env-file)
  + [Registering API theme](#registering-api-theme)
  + [Choose between simple *API-Key* or full *OAuth2* authentication schemes](#choose-between-simple-api-key-or-full-oauth2-authentication-schemes)
  + [Enable grant types for your website](#enable-grant-types-for-your-website)
  + [Customize CORS](#customize-cors)
  + [Use cache-tags](#use-cache-tags)
* [Create a new application](#create-a-new-application)
  + [Confidential applications: *OAuth2*](#confidential-applications-oauth2)
* [Generic Roadiz API](#generic-roadiz-api)
  + [API Route listing](#api-route-listing)
  + [OAuth2 entry points](#oauth2-entry-points)
  + [User detail entry point](#user-detail-entry-point)
  + [Listing nodes-sources](#listing-nodes-sources)
  + [Search nodes-sources](#search-nodes-sources)
  + [Listing tags per node-types](#listing-tags-per-node-types)
  + [Listing archives per node-types](#listing-archives-per-node-types)
  + [Getting node-source details](#getting-node-source-details)
  + [Getting node-source details directly from its path](#getting-node-source-details-directly-from-its-path)
  + [Listing node-source children](#listing-node-source-children)
  + [Serialization context](#serialization-context)
  + [Breadcrumbs](#breadcrumbs)
  + [Errors](#errors)
  + [Using Etags](#using-etags)
  
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
    'expose_headers' => ['link', 'etag'],
    'max_age' => 60*60*24
];
```

### Use cache-tags

Serialization context can gather every *nodes* ID, *documents* ID and *tags* ID they find during requests,
as known as *cache tags*.

```php
// In your application/theme service provider
$container['api.use_cache_tags'] = true;
```

Cache tags will be appended to response `X-Cache-Tags` header and will allow you to clear your reverse-proxy caches
more selectively. Here are cache tags syntax:

- `n{node.id}` (i.e: `n98`) for a node
- `t{tag.id}` (i.e: `t32`) for a tag
- `d{document.id}` (i.e: `d291`) for a document

Cache-tags syntax is the shortest possible to avoid hitting *maximum header size* limit in your Nginx configuration.

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
            "@id": "http://example.test/dev.php/api/1.0/page/2/fr",
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

**Note:** In listing context, only node-type-fields from *default* group will be exposed. If you want 
to prevent some node-type fields to be serialized during listing you can give them a *Group name*. This
can be helpful for avoiding *document* or *node reference* fields to bloat your JSON responses.

#### Filters

- itemsPerPage: `int`
- page: `int`
- _locale: `string` If _locale is not set, Roadiz will negotiate with existing `Accept-Language` header
- search: `string`
- order: `array` Example `order[publishedAt]: DESC` with values:
  - `ASC`
  - `DESC`
- properties: `array` Filters serialized properties by their names    
- archive: `string` Example `archive: 2019-02` or `archive: 2019`. This parameter only works on `publishedAt` field

On `NodesSources` content:

- path: `string` Filters nodes-sources against a valid path (based on node' name or alias), example: `/home`. Path does require `_locale` filter to fetch right translation. Path filter can resolve any *Redirection* too if it is linked to a valid node-source.
- id: `id` Nodes-sources ID
- title: `string`
- not: `array<int|string>|int|string`, filters out one or many nodes using their numeric ID, node-name or *@id*
- publishedAt: `DateTime` or `array` with :
  - `after`
  - `before`
  - `strictly_after`
  - `strictly_before`
- tags: `array<string>` filter by tags (cannot be used with `search`)
- tagExclusive: `bool` filter by tags with AND logic (cannot be used with `search`)
- node.parent: `int|string` numeric ID, node-name or *@id*
- node.aNodes.nodeA: `int|string` (numeric ID, node-name or *@id*) Filter by a node reference (finds nodes which are referenced)
- node.bNodes.nodeB: `int|string` (numeric ID, node-name or *@id*) Filter by a node reference (finds node which owns reference)
- node.aNodes.field.name: `string` Filter node references by a node-type field name (optional, if not set, `node.aNodes.nodeA` filter will apply on any node reference)
- node.bNodes.field.name: `string` Filter node references by a node-type field name (optional, if not set, `node.bNodes.nodeB` filter will apply on any node reference)
- node.visible: `bool`
- node.home: `bool`
- node.nodeType: `array|string` Filter nodes-sources by their type
- node.nodeType.reachable: `bool`

Plus **any** date, datetime and boolean node-type fields which are **indexed**.

#### Locale filter

`_locale` filter **set Roadiz main translation** for all database lookups, make sure to always set it to
the right locale, or you won't get any result with `search` or `path` filters against French queries.

#### Path filter

`path` filter **uses Roadiz internal router** to search only one result to match against your query. You can use:

- node-source canonical path, i.e: `/about-us`
- node-source nodeName path: i.e: `/en/about-us`
- a redirected path, i.e: `/old-about-us`

If you get one result, you'll find canonical path in `hydra:member > 0 > url` field to create a redirection in
your frontend framework and advertise node-source new URL.

##### Redirect home path with Accept-Language

Using `path` filter with `/` value **only**, you can send `Accept-Language` header to the API to let it decide with
translation is best for your consumer. If a valid data is found, API will respond with `Content-Language` header
contain accepted locale.
To enable this behaviour, you must enable `force_locale` Roadiz setting to make sure each home page path 
displays its locale and to avoid infinite redirection loops.

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
- _locale: `string` If _locale is not set, Roadiz will negotiate with existing `Accept-Language` header
- search: `string`
- tags: `array<string>`
- node.parent: `int` or `string` (node-name)
- node.visible: `bool`
- node.nodeType: `array|string` Filter nodes-sources search by their type
- properties: `array` Filters serialized properties by their names

### Listing tags per node-types

- `/api/1.0/{node-type-name}/tags`: Fetch all tags used in nodes-sources from a given type.

If you created a `Event` node-type, you may want to list any `Tags` attached to *events*, API will be available at 
`/api/1.0/event/tags` endpoint. Be careful, this endpoint will display all tags, visible or not, unless you filter them.

#### Filters

- itemsPerPage: `int`
- page: `int`
- _locale: `string` If _locale is not set, Roadiz will negotiate with existing `Accept-Language` header
- search: `string`: This will search on `tagName` and translation `name`
- order: `array` Example `order[position]: ASC` with values:
  - `ASC`
  - `DESC`
- properties: `array` Filters serialized properties by their names    

On `Tag` content:

- tagName: `string`
- parent: `int` or `string` (tag-name)
- visible: `bool`

### Listing archives per node-types

- `/api/1.0/{node-type-name}/archives`: Fetch all publication months used in nodes-sources from a given type.

If you created a `Event` node-type, you may want to list any archives from *events*, API will be available at 
`/api/1.0/event/archives` endpoint. Here is a response example which list all archives grouped by year:

```json
{
    "hydra:member": {
        "2021": {
            "2021-01": "2021-01-01T00:00:00+01:00"
        },
        "2020": {
            "2020-12": "2020-12-01T00:00:00+01:00",
            "2020-10": "2020-10-01T00:00:00+02:00",
            "2020-07": "2020-07-01T00:00:00+02:00"
        }
    },
    "@id": "/api/1.0/event/archives",
    "@type": "hydra:Collection",
    "hydra:view": {
        "@id": "/api/1.0/event/archives",
        "@type": "hydra:PartialCollectionView"
    }
}
```

#### Filters

- _locale: `string` If _locale is not set, Roadiz will negotiate with existing `Accept-Language` header
- tags: `array<string>`
- tagExclusive: `bool`
- node.parent: `int` or `string` (node-name)

### Getting node-source details

- `/api/1.0/{node-type-name}/{id}/{_locale}`: fetch a node-source with its node' ID and translation `locale`. This is the default route used to generate your content JSON-LD `@id` field.
- `/api/1.0/{node-type-name}/{id}`: fetch a node-source with its node' ID and system **default** locale (or query string one)
- `/api/1.0/{node-type-name}/by-slug/{slug}`: fetch a node-source with its slug (`nodeName` or `urlAlias`)

For each node-source, API will expose detailed content on `/api/1.0/event/{id}` and `/api/1.0/event/by-slug/{slug}` endpoints.

### Getting node-source details directly from its `path`

- `/api/1.0/nodes-sources/by-path/?path={path}`: fetch one node-source details against its `path` (including homepages root paths)

#### Filters

- properties: `array` Filters serialized properties by their names

#### Alternate resources URLs

Any node-source detail response will have a `Link` header carrying URLs for all alternate translations.
For example a *legal* page which is translated in English and French will have this `Link` header data:

```
<https://api.mysite.test/api/1.0/page/23/en>; rel="alternate"; hreflang="en"; type="application/json", 
<https://api.mysite.test/api/1.0/page/23/fr>; rel="alternate"; hreflang="fr"; type="application/json", 
</mentions-legales>; rel="alternate"; hreflang="fr"; type="text/html", 
</legal>; rel="alternate"; hreflang="en"; type="text/html"
```

*text/html* resources URL will always be **absolute paths** instead of absolute URL in order to generate your own
URL in your front-end framework without carrying API scheme.


### Listing node-source children

For safety reasons, we do not embed node-sources children automatically. We invite you to use [TreeWalker](https://github.com/rezozero/tree-walker) library to extend your JSON serialization to build a safe graph for each of your node-types. Create a `JMS\Serializer\EventDispatcher\EventSubscriberInterface` subscriber to extend
`serializer.post_serialize` event with `StaticPropertyMetadata`.

```php
# Any JMS\Serializer\EventDispatcher\EventSubscriberInterface implementation…

$exclusionStrategy = $context->getExclusionStrategy() ?? 
    new \JMS\Serializer\Exclusion\DisjunctExclusionStrategy();
/** @var array<string> $groups */
$groups = $context->hasAttribute('groups') ? 
    $context->getAttribute('groups') : 
    [];
$groups = array_unique(array_merge($groups, [
    'walker',
    'children'
]));
$propertyMetadata = new \JMS\Serializer\Metadata\StaticPropertyMetadata(
    'Collection',
    'children',
    [],
    $groups
);
# Check if virtual property children has been requested with properties[] filter…
if (!$exclusionStrategy->shouldSkipProperty($propertyMetadata, $context)) {
    $blockWalker = BlockNodeSourceWalker::build(
        $nodeSource,
        $this->get(NodeSourceWalkerContext::class),
        4, // max graph level
        $this->get('nodesSourcesUrlCacheProvider')
    );
    $visitor->visitProperty(
        $propertyMetadata,
        $blockWalker->getChildren()
    );
}
```

### Serialization context

For each request, serialization context holds many useful objects during `serializer.post_serialize` events:

- `request`: Symfony current request object
- `nodeType`: Initial node-source type (or `null` if not applicable)
- `cache-tags`: Cache-tags collection which is filled up during serialization graph
- `translation`: Current request translation
- `groups`: Serialization groups for current request
    - Serialization groups during a **listing** nodes-sources request:
        - `nodes_sources_base`
        - `document_display`
        - `thumbnail`
        - `tag_base`
        - `nodes_sources_default`
        - `urls`
        - `meta`
  - Serialization groups during a **single** node-source request:
        - `walker`: rezozero tree-walker
        - `children`: rezozero tree-walker
        - `nodes_sources`
        - `nodes_sources_single`: for displaying custom objects only on main entity
        - `document_display`
        - `thumbnail`
        - `url_alias`
        - `tag_base`
        - `urls`
        - `meta`
        - `breadcrumbs`: only allows breadcrumbs on detail requests

```php
# Any JMS\Serializer\EventDispatcher\EventSubscriberInterface implementation…

public function onPostSerialize(\JMS\Serializer\EventDispatcher\ObjectEvent $event): void
{
    $context = $event->getContext();
    
    /** @var \Symfony\Component\HttpFoundation\Request $request */
    $request = $context->hasAttribute('request') ? $context->getAttribute('request') : null;
    
    /** @var \RZ\Roadiz\Contracts\NodeType\NodeTypeInterface|null $nodeType */
    $nodeType = $context->hasAttribute('nodeType') ? $context->getAttribute('nodeType') : null;
    
    /** @var \RZ\Roadiz\Core\AbstractEntities\TranslationInterface|null $translation */
    $translation = $context->hasAttribute('translation') ? $context->getAttribute('translation') : null;
    
    /** @var array<string> $groups */
    $groups = $context->hasAttribute('groups') ? $context->getAttribute('groups') : [];
}
```

### Breadcrumbs

If you want your API to provide breadcrumbs for each reachable nodes-sources, you can implement 
`Themes\AbstractApiTheme\Breadcrumbs\BreadcrumbsFactoryInterface` and register it in your `AppServiceProvider`.
For each *NodeTypeSingle* API request (i.e. not in listing context), a `breadcrumbs` will be injected with all your node parents as defined in your *BreadcrumbsFactoryInterface*.

Here is a vanilla implementation which respects Roadiz node tree structure:

```php
<?php
declare(strict_types=1);

namespace App\Breadcrumbs;

use RZ\Roadiz\Core\Entities\NodesSources;
use Themes\AbstractApiTheme\Breadcrumbs\BreadcrumbsFactoryInterface;
use Themes\AbstractApiTheme\Breadcrumbs\BreadcrumbsInterface;
use Themes\AbstractApiTheme\Breadcrumbs\Breadcrumbs;

final class BreadcrumbsFactory implements BreadcrumbsFactoryInterface
{
    /**
     * @param NodesSources|null $nodesSources
     * @return BreadcrumbsInterface|null
     */
    public function create(?NodesSources $nodesSources): ?BreadcrumbsInterface
    {
        if (null === $nodesSources ||
            null === $nodesSources->getNode() ||
            null === $nodesSources->getNode()->getNodeType() ||
            !$nodesSources->getNode()->getNodeType()->isReachable()) {
            return null;
        }
        $parents = [];

        while (null !== $nodesSources = $nodesSources->getParent()) {
            if (null !== $nodesSources->getNode() &&
                $nodesSources->getNode()->isPublished() &&
                $nodesSources->getNode()->isVisible()) {
                $parents[] = $nodesSources;
            }
        }
        return new Breadcrumbs(array_reverse($parents));
    }
}
```

```php
# App\AppServiceProvider
$container[BreadcrumbsFactoryInterface::class] = function (Container $c) {
    return new BreadcrumbsFactory();
};
```

### Errors

If you want to get detailed errors in JSON, do not forget to add the header: `Accept: application/json` to
every request you make. You'll get message such as:

```json
{
    "error": "general_error",
    "error_message": "Search engine does not respond.",
    "message": "Search engine does not respond.",
    "exception": "Symfony\\Component\\HttpKernel\\Exception\\HttpException",
    "humanMessage": "A problem occurred on our website. We are working on this to be back soon.",
    "status": "danger"
}
```

with the right *status code* (40x or 50x). Make sure to catch and read your response data from your frontend
framework when your request fails to know more about errors.

### Using Etags

Every NodeSources based response will contain a `ETag` header calculated on API response content checksum. 

You can setup your API consumer to send a `If-None-Match` header containing the latest ETag found. API will return 
an empty **304 Not Modified** response if content has not changed, or the whole response if it changed with a new *ETag* header. 
