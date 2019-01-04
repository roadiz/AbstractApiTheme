<?php
/**
 * AbstractApiTheme - ApplicationProviderInterface.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-01-03
 */
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Security\Authentication\Provider;

use Themes\AbstractApiTheme\Entity\Application;

interface ApplicationProviderInterface
{
    /**
     * @param string $apiKey
     *
     * @return Application|null
     */
    public function loadApplicationByApiKey(string $apiKey): ?Application;
}