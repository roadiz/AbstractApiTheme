<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Event;

use RZ\Roadiz\Core\Entities\Role;
use Symfony\Contracts\EventDispatcher\Event;
use Themes\AbstractApiTheme\Entity\Application;

class RoleResolveEvent extends Event
{
    /**
     * @var array<Role|string>
     */
    protected $roles;
    /**
     * @var string
     */
    protected $grantType;
    /**
     * @var Application
     */
    protected $client;
    /**
     * @var string|null
     */
    protected $userIdentifier;

    /**
     * @param array $roles
     * @param string $grantType
     * @param Application $client
     * @param string|null $userIdentifier
     */
    public function __construct(array $roles, string $grantType, Application $client, ?string $userIdentifier)
    {
        $this->roles = $roles;
        $this->grantType = $grantType;
        $this->client = $client;
        $this->userIdentifier = $userIdentifier;
    }

    /**
     * @return array<Role|string>
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * @param array<Role|string> $roles
     * @return RoleResolveEvent
     */
    public function setRoles(array $roles): RoleResolveEvent
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @return string
     */
    public function getGrantType(): string
    {
        return $this->grantType;
    }

    /**
     * @return Application
     */
    public function getClient(): Application
    {
        return $this->client;
    }

    /**
     * @return string|null
     */
    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }
}
