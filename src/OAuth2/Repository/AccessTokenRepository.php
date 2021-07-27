<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OAuth2\Repository;

use Doctrine\Persistence\ManagerRegistry;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Themes\AbstractApiTheme\Model\AccessToken;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    protected ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        $accessToken = new AccessToken();
        $accessToken->setClient($clientEntity);
        $accessToken->setUserIdentifier($userIdentifier);
        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }
        return $accessToken;
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        // Access token persistence is not implemented
    }

    /**
     * @inheritDoc
     * @return void
     */
    public function revokeAccessToken($tokenId)
    {
        throw new \BadMethodCallException('Access token persistence is not implemented');
    }

    /**
     * @inheritDoc
     * @return bool
     */
    public function isAccessTokenRevoked($tokenId)
    {
        return false;
    }
}
