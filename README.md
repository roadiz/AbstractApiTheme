# Abstract API theme

**Base theme for restricting public RESTful API with API keys.**

## Configuration

- Add custom entity to your *Roadiz* config: `Application` that will hold your api-key

```yaml
entities:
    - "../vendor/roadiz/abstract-api-theme/src/Entity"
```

- Add API base services to your *Roadiz* config:

```yaml
additionalServiceProviders:
    - \Themes\AbstractApiTheme\Services\AbstractApiServiceProvider
```

- Create a new theme with your API logic by extending `AbstractApiThemeApp`
- Add the API firewall to the dependency injection with your own route prefix…

```php
<?php
declare(strict_types=1);

namespace Themes\MyApiTheme;

use Themes\AbstractApiTheme\AbstractApiThemeApp;

class MyApiThemeApp extends AbstractApiThemeApp
{
    protected static $themeName = 'My API theme';
    protected static $themeAuthor = 'REZO ZERO';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'MyApiTheme';
    protected static $backendTheme = false;
    
    public static $priority = 10;
}
```