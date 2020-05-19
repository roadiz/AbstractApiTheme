<?php
/**
 * AbstractApiTheme - AbstractApiThemeApp.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-01-03
 */
declare(strict_types=1);

namespace Themes\AbstractApiTheme;

use Pimple\Container;
use RZ\Roadiz\CMS\Controllers\FrontendController;

class AbstractApiThemeApp extends FrontendController
{
    use AbstractApiThemeTrait;

    protected static $themeName = 'Abstract Api theme';
    protected static $themeAuthor = 'REZO ZERO';
    protected static $themeCopyright = 'REZO ZERO';
    protected static $themeDir = 'AbstractApiTheme';
    protected static $backendTheme = false;
    public static $priority = 9;

    /**
     * @inheritDoc
     */
    public static function addDefaultFirewallEntry(Container $container)
    {
        // do nothing
    }
}
