<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Converter;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use RZ\Roadiz\Core\Bags\Roles;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\String\UnicodeString;
use Themes\AbstractApiTheme\Model\Scope;

class ScopeConverter
{
    /**
     * @var Roles
     */
    protected $rolesBag;
    /**
     * @var string
     */
    private $rolePrefix;
    /**
     * @var string
     */
    private $baseRole;

    /**
     * @param Roles $rolesBag
     * @param string $rolePrefix
     * @param string $baseRole
     */
    public function __construct(Roles $rolesBag, string $rolePrefix = 'ROLE_OAUTH2_', string $baseRole = 'ROLE_API')
    {
        $this->rolesBag = $rolesBag;
        $this->rolePrefix = $rolePrefix;
        $this->baseRole = $baseRole;
    }

    /**
     * @param Role|string $role
     * @return ScopeEntityInterface
     */
    public function toScope($role): ScopeEntityInterface
    {
        if ((string) $role === Role::ROLE_BACKEND_USER) {
            return new Scope('preview');
        }
        if ((string) $role === $this->baseRole) {
            return new Scope('api');
        }
        return new Scope(
            (new UnicodeString((string) $role))
                ->replace($this->rolePrefix, '')
                ->replace('_', '-')
                ->lower()
                ->toString()
        );
    }

    /**
     * @param string $identifier
     * @return Role|null
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function identifierToRole(string $identifier): ?Role
    {
        if ($identifier === 'preview') {
            $roleName = Role::ROLE_BACKEND_USER;
        } elseif ($identifier === 'api') {
            $roleName = $this->baseRole;
        } else {
            $roleName = (new UnicodeString($identifier))
                ->replace(' ', '_')
                ->replace('-', '_')
                ->replace('.', '_')
                ->prepend($this->rolePrefix)
                ->upper()
                ->toString();
        }

        if ($this->rolesBag->has($roleName)) {
            return $this->rolesBag->get($roleName, null);
        }
        return null;
    }

    public function toRole(ScopeEntityInterface $scope): ?Role
    {
        return $this->identifierToRole($scope->getIdentifier());
    }

    /**
     * @param array<ScopeEntityInterface> $scopes
     * @return array<Role>
     */
    public function toRoles(array $scopes): array
    {
        return array_map(function (Scope $scope) {
            return $this->toRole($scope);
        }, $scopes);
    }

    /**
     * @param array<Role|string> $roles
     * @return array<ScopeEntityInterface>
     */
    public function toScopes(array $roles): array
    {
        return array_map(function ($role) {
            return $this->toScope($role);
        }, $roles);
    }
}
