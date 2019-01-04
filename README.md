# Abstract API theme

**Base theme for restricting public RESTful API with API keys.**

## Configuration

- Add current theme’ entity path to your *Roadiz* config to persist any `Application` that will hold your api-key

```yaml
entities:
    - "../vendor/roadiz/abstract-api-theme/src/Entity"
```

- Add API base services to your *Roadiz* config:

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
- and add the API authentication scheme to Roadiz’ firewall-map…

```php
<?php
declare(strict_types=1);

namespace Themes\MyApiTheme;

use Themes\AbstractApiTheme\AbstractApiThemeApp;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Pimple\Container;

class MyApiThemeApp extends AbstractApiThemeApp
{
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
        $requestMatcher = new RequestMatcher('^/api/1.0');
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

