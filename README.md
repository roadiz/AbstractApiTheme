# Abstract API theme

**Base theme for createing simple public RESTful API protected with referrer API keys.**

## Configuration

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
            '^'.$container['api.prefix'].'/'.$container['api.version']
        );
        /*
         * Add default API firewall entry.
         */
        $container['firewallMap']->add(
            $requestMatcher, // launch firewall rules for any request within /api/1.0 path
            [$container['api.firewall_listener']]
        );

        $container['accessMap']->add(
            $requestMatcher,
            [$container['api.base_role']]
        );

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

## Create a new application

Applications hold your API keys and control incoming requests `Referer` against a *regex* pattern.

## Generic Roadiz API

### Listing nodes-sources

If you created a `Event` node-type, API content will be available at `/api/1.0/event` endpoint.

Note: In listing context, only node-type-fields from *default* group will be exposed.

### Getting node-source details

For each node-source, API will expose detailled content on `/api/1.0/event/{id}` endpoint.

### Listing node-source children

For each node-source, API will expose recursively its children `/api/1.0/event/{id}/children` endpoint.

Note: In children context, only node-type-fields from *default* group will be exposed.

### Filters

- itemsPerPage: `int`
- maxChildrenCount: `int` (only in *children* requests)
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
- node.parent: `int`
- node.visible: `bool`
- node.nodeType.reachable: `bool`

Plus **any** date, datetime and boolean node-type fields which are **indexed**.
