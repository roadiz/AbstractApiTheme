<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Entity;

use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

/**
 * An AbstractEntity with datetime fields to keep track of time with your items.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\Table(name="api_auth_codes", indexes={
 *     @ORM\Index(columns={"expiry"}),
 *     @ORM\Index(columns={"user_identifier"})
 * })
 */
class AuthorizationCode extends AbstractEntity
{
    /**
     * @var string
     * @ORM\Column(type="string", name="identifier", unique=true, nullable=false)
     */
    private $identifier = '';

    /**
     * @var \DateTimeInterface|null
     * @ORM\Column(name="expiry", type="datetime", nullable=true)
     */
    private $expiry = null;

    /**
     * @var string|null
     * @ORM\Column(name="user_identifier", type="string", unique=false, nullable=false)
     */
    private $userIdentifier = null;

    /**
     * @var ClientEntityInterface|null
     * @ORM\ManyToOne(targetEntity="Themes\AbstractApiTheme\Entity\Application")
     * @ORM\JoinColumn(fieldName="client_id", referencedColumnName="id", onDelete="CASCADE")
     */
    private $client = null;

    /**
     * @var ScopeEntityInterface[]
     * @ORM\Column(name="scopes", type="json", nullable=true)
     */
    private $scopes = [];

    /**
     * @var bool
     * @ORM\Column(name="revoked", type="boolean", nullable=false)
     */
    private $revoked = false;

    public function __toString(): string
    {
        return $this->getIdentifier();
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return AuthorizationCode
     */
    public function setIdentifier(string $identifier): AuthorizationCode
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return \DateTimeInterface|null
     */
    public function getExpiry(): ?\DateTimeInterface
    {
        return $this->expiry;
    }

    /**
     * @param \DateTimeInterface $expiry
     * @return AuthorizationCode
     */
    public function setExpiry(\DateTimeInterface $expiry): AuthorizationCode
    {
        $this->expiry = $expiry;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    /**
     * @param string|null $userIdentifier
     * @return AuthorizationCode
     */
    public function setUserIdentifier(?string $userIdentifier): AuthorizationCode
    {
        $this->userIdentifier = $userIdentifier;
        return $this;
    }

    /**
     * @return ClientEntityInterface|null
     */
    public function getClient(): ?ClientEntityInterface
    {
        return $this->client;
    }

    /**
     * @param ClientEntityInterface|null $client
     * @return AuthorizationCode
     */
    public function setClient(?ClientEntityInterface $client): AuthorizationCode
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @return ScopeEntityInterface[]
     */
    public function getScopes(): array
    {
        return $this->scopes ?? [];
    }

    /**
     * @param ScopeEntityInterface[] $scopes
     * @return AuthorizationCode
     */
    public function setScopes(array $scopes): AuthorizationCode
    {
        $this->scopes = $scopes;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    /**
     * @return AuthorizationCode
     */
    public function revoke(): AuthorizationCode
    {
        return $this->setRevoked(true);
    }

    /**
     * @param bool $revoked
     * @return AuthorizationCode
     */
    public function setRevoked(bool $revoked): AuthorizationCode
    {
        $this->revoked = $revoked;
        return $this;
    }
}
