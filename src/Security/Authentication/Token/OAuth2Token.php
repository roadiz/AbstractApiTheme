<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Security\Authentication\Token;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\String\UnicodeString;

class OAuth2Token extends AbstractToken
{
    /**
     * @var string
     */
    private $providerKey;

    public function __construct(
        ServerRequestInterface $serverRequest,
        ?UserInterface $user,
        string $rolePrefix,
        string $baseRole,
        string $providerKey
    ) {
        $this->setAttribute('server_request', $serverRequest);
        $this->setAttribute('role_prefix', $rolePrefix);
        $this->setAttribute('base_role', $baseRole);

        $roles = $this->buildRolesFromScopes();

        if (null !== $user) {
            // Merge the user's roles with the OAuth 2.0 scopes.
            $roles = array_merge($roles, $user->getRoles());

            $this->setUser($user);
        }

        parent::__construct(array_unique($roles));

        $this->providerKey = $providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return $this->getAttribute('server_request')->getAttribute('oauth_access_token_id');
    }

    public function getProviderKey(): string
    {
        return $this->providerKey;
    }

    public function __serialize(): array
    {
        return [$this->providerKey, parent::__serialize()];
    }

    public function __unserialize(array $data): void
    {
        [$this->providerKey, $parentData] = $data;
        parent::__unserialize($parentData);
    }

    private function buildRolesFromScopes(): array
    {
        $prefix = $this->getAttribute('role_prefix');
        $roles = [
            $this->getAttribute('base_role')
        ];

        foreach ($this->getAttribute('server_request')->getAttribute('oauth_scopes', []) as $scope) {
            $roles[] = (new UnicodeString($scope))
                ->replace(' ', '_')
                ->replace('-', '_')
                ->replace('.', '_')
                ->prepend($prefix)
                ->upper()
                ->toString();
        }

        return $roles;
    }
}
