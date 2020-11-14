<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Security\Authentication\Token;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class OAuth2TokenFactory
{
    /**
     * @var string
     */
    private $rolePrefix;
    /**
     * @var string
     */
    private $baseRole;

    /**
     * @param string $rolePrefix
     * @param string $baseRole
     */
    public function __construct(string $rolePrefix, string $baseRole)
    {
        $this->rolePrefix = $rolePrefix;
        $this->baseRole = $baseRole;
    }

    public function createOAuth2Token(
        ServerRequestInterface $serverRequest,
        ?UserInterface $user,
        string $providerKey
    ): OAuth2Token {
        return new OAuth2Token($serverRequest, $user, $this->rolePrefix, $this->baseRole, $providerKey);
    }
}
