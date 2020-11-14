<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OAuth2\Repository;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use RZ\Roadiz\Core\Entities\Role;
use Themes\AbstractApiTheme\Converter\ScopeConverter;
use Themes\AbstractApiTheme\Entity\Application;

class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * @var ScopeConverter
     */
    protected $scopeConverter;

    /**
     * ScopeRepository constructor.
     * @param ScopeConverter $scopeConverter
     */
    public function __construct(ScopeConverter $scopeConverter)
    {
        $this->scopeConverter = $scopeConverter;
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
            $finalizedRoles = $this->setupRoles($clientEntity, $this->scopeConverter->toRoles($scopes));
            return $this->scopeConverter->toScopes($finalizedRoles);
        }
        return [];
    }

    /**
     * @param Application $application
     * @param Role[] $requestedRoles
     *
     * @return string[]
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
