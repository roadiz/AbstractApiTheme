<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OAuth2\Repository;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

/**
 * @package Themes\AbstractApiTheme\OAuth2\Repository
 * @see https://github.com/trikoder/oauth2-bundle/blob/v3.x/League/Repository/RefreshTokenRepository.php
 */
final class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * @inheritDoc
     */
    public function getNewRefreshToken()
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
    }

    /**
     * @inheritDoc
     */
    public function revokeRefreshToken($tokenId)
    {
        throw new \BadMethodCallException('Refresh token persistence is not implemented');
    }

    /**
     * @inheritDoc
     */
    public function isRefreshTokenRevoked($tokenId)
    {
        throw new \BadMethodCallException('Refresh token persistence is not implemented');
    }
}
