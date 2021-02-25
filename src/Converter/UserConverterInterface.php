<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Converter;

use League\OAuth2\Server\Entities\UserEntityInterface;
use Symfony\Component\Security\Core\User\UserInterface;

interface UserConverterInterface
{
    public function toLeague(?UserInterface $user): UserEntityInterface;
}
