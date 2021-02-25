<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OAuth2;

use Lcobucci\JWT\Configuration;

final class JwtRequestFactory
{
    /**
     * @var Configuration
     */
    private Configuration $jwtConfiguration;

    /**
     * @param Configuration $jwtConfiguration
     */
    public function __construct(Configuration $jwtConfiguration)
    {
        $this->jwtConfiguration = $jwtConfiguration;
    }

    /**
     * @param string $audience
     * @param array $scopes
     * @return JwtRequest
     */
    public function createJwtRequest(string $audience, array $scopes = [])
    {
        $jwtRequest = new JwtRequest();
        $jwtRequest->setJwtConfiguration($this->jwtConfiguration);
        $jwtRequest->setNotValidBefore(new \DateTime('now'));
        $jwtRequest->setExpireAt(new \DateTime('now + 3 hour'));
        $jwtRequest->setAudience($audience);
        $jwtRequest->setScopes($scopes);
        return $jwtRequest;
    }
}
