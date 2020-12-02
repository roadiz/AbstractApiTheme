<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OAuth2\Repository;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Themes\AbstractApiTheme\Converter\ScopeConverter;
use Themes\AbstractApiTheme\Entity\Application;
use Themes\AbstractApiTheme\Event\RoleResolveEvent;

class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * @var ScopeConverter
     */
    protected $scopeConverter;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @param ScopeConverter $scopeConverter
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(ScopeConverter $scopeConverter, EventDispatcherInterface $dispatcher)
    {
        $this->scopeConverter = $scopeConverter;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @inheritDoc
     */
    public function getScopeEntityByIdentifier($identifier)
    {
        $role = $this->scopeConverter->identifierToRole($identifier);
        return $role ? $this->scopeConverter->toScope($role) : null;
    }

    /**
     * @inheritDoc
     */
    public function finalizeScopes(array $scopes, $grantType, ClientEntityInterface $clientEntity, $userIdentifier = null)
    {
        if ($clientEntity instanceof Application) {
            return $this->scopeConverter->toScopes(
                $this->finalizeRoles($scopes, $grantType, $clientEntity, $userIdentifier)
            );
        }
        return [];
    }

    /**
     * @param array $scopes
     * @param $grantType
     * @param ClientEntityInterface $clientEntity
     * @param null $userIdentifier
     * @return array<Role|string>
     * @throws OAuthServerException
     */
    public function finalizeRoles(array $scopes, $grantType, ClientEntityInterface $clientEntity, $userIdentifier = null)
    {
        if ($clientEntity instanceof Application) {
            $finalizedRoles = $this->setupRoles($clientEntity, $this->scopeConverter->toRoles($scopes));

            $event = $this->dispatcher->dispatch(
                new RoleResolveEvent(
                    $finalizedRoles,
                    $grantType,
                    $clientEntity,
                    $userIdentifier
                )
            );

            return $event->getRoles();
        }
        return [];
    }

    /**
     * @param Application $application
     * @param Role[] $requestedRoles
     *
     * @return array<Role|string>
     * @throws OAuthServerException
     */
    private function setupRoles(Application $application, array $requestedRoles): array
    {
        $applicationRoles = $application->getRoles();

        if (empty($applicationRoles)) {
            return $requestedRoles;
        }

        if (empty($requestedRoles)) {
            return $applicationRoles;
        }

        $finalizedRoles = [];
        $applicationRolesAsStrings = array_map('strval', $applicationRoles);

        foreach ($requestedRoles as $requestedRole) {
            $requestedRoleAsString = (string) $requestedRole;
            if (!\in_array($requestedRoleAsString, $applicationRolesAsStrings, true)) {
                throw OAuthServerException::invalidScope(
                    $this->scopeConverter->toScope($requestedRole)->getIdentifier()
                );
            }

            $finalizedRoles[] = $requestedRoleAsString;
        }

        return $finalizedRoles;
    }
}
