<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OAuth2;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Token;

final class JwtRequest
{
    /**
     * @var array<string>
     */
    protected $scopes;

    /**
     * @var string
     */
    protected $audience;

    /**
     * @var \DateTime
     */
    protected $notValidBefore;

    /**
     * @var \DateTime
     */
    protected $expireAt;

    /**
     * @var Configuration
     */
    private $jwtConfiguration;

    /**
     * @var null|Token
     */
    private $token = null;

    /**
     * @param Configuration $jwtConfiguration
     * @return JwtRequest
     */
    public function setJwtConfiguration(Configuration $jwtConfiguration): JwtRequest
    {
        $this->jwtConfiguration = $jwtConfiguration;
        return $this;
    }

    /**
     * @return array
     */
    public function getScopes(): array
    {
        return $this->scopes;
    }

    /**
     * @param array $scopes
     * @return JwtRequest
     */
    public function setScopes(array $scopes): JwtRequest
    {
        $this->scopes = $scopes;
        return $this;
    }

    /**
     * @return string
     */
    public function getAudience(): string
    {
        return $this->audience;
    }

    /**
     * @param string $audience
     * @return JwtRequest
     */
    public function setAudience(string $audience): JwtRequest
    {
        $this->audience = $audience;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getNotValidBefore(): \DateTime
    {
        return $this->notValidBefore;
    }

    /**
     * @param \DateTime $notValidBefore
     * @return JwtRequest
     */
    public function setNotValidBefore(\DateTime $notValidBefore): JwtRequest
    {
        $this->notValidBefore = $notValidBefore;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getExpireAt(): \DateTime
    {
        return $this->expireAt;
    }

    /**
     * @param \DateTime $expireAt
     * @return JwtRequest
     */
    public function setExpireAt(\DateTime $expireAt): JwtRequest
    {
        $this->expireAt = $expireAt;
        return $this;
    }

    /**
     * Generate a JWT from the access token
     *
     * @return Token
     */
    public function getToken()
    {
        if (null === $this->token) {
            $this->token = $this->jwtConfiguration->builder()
                ->permittedFor($this->getAudience())
                ->identifiedBy(uniqid($this->getAudience()))
                ->relatedTo('')
                ->issuedAt((new \DateTimeImmutable())->setTimestamp(\time()))
                ->canOnlyBeUsedAfter(\DateTimeImmutable::createFromMutable($this->getNotValidBefore()))
                ->expiresAt(\DateTimeImmutable::createFromMutable($this->getExpireAt()))
                ->withClaim('scopes', $this->getScopes())
                ->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey());
        }
        return $this->token;
    }

    /**
     * Generate a string representation from the access token
     */
    public function __toString()
    {
        return $this->getToken()->toString();
    }

    /**
     * @return array
     */
    public function __serialize(): array
    {
        return [$this->scopes, $this->audience, $this->notValidBefore, $this->expireAt];
    }

    /**
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        [$this->scopes, $this->audience, $this->notValidBefore, $this->expireAt] = $data;
    }
}
