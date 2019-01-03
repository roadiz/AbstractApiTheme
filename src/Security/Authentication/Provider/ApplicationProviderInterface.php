<?php
/**
 * AbstractApiTheme - ApplicationProviderInterface.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-01-03
 */
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Security\Authentication\Provider;

use Symfony\Component\Security\Core\User\UserInterface;

interface ApplicationProviderInterface
{
    /**
     * @param string $apiKey
     *
     * @return UserInterface
     */
    public function loadApplicationByApiKey(string $apiKey): UserInterface;
}