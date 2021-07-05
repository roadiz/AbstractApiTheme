<?php
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
