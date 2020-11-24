<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Event;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Psr\Http\Message\ResponseInterface;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @package Themes\AbstractApiTheme\Event
 * @see https://github.com/trikoder/oauth2-bundle/blob/v3.x/Event/AuthorizationRequestResolveEvent.php
 */
final class AuthorizationRequestResolveEvent extends Event
{
    public const AUTHORIZATION_APPROVED = true;
    public const AUTHORIZATION_DENIED = false;

    /**
     * @var AuthorizationRequest
     */
    private $authorizationRequest;

    /**
     * @var Role[]
     */
    private $roles;

    /**
     * @var ClientEntityInterface
     */
    private $client;

    /**
     * @var bool
     */
    private $authorizationResolution = self::AUTHORIZATION_DENIED;

    /**
     * @var ResponseInterface|null
     */
    private $response;

    /**
     * @var UserInterface|null
     */
    private $user;

    /**
     * @param AuthorizationRequest $authorizationRequest
     * @param Role[] $roles
     * @param ClientEntityInterface $client
     */
    public function __construct(
        AuthorizationRequest $authorizationRequest,
        array $roles,
        ClientEntityInterface $client
    ) {
        $this->authorizationRequest = $authorizationRequest;
        $this->roles = $roles;
        $this->client = $client;
    }

    public function getAuthorizationResolution(): bool
    {
        return $this->authorizationResolution;
    }

    public function resolveAuthorization(bool $authorizationResolution): self
    {
        $this->authorizationResolution = $authorizationResolution;
        $this->response = null;
        $this->stopPropagation();

        return $this;
    }

    public function hasResponse(): bool
    {
        return $this->response instanceof ResponseInterface;
    }

    public function getResponse(): ResponseInterface
    {
        if (!$this->hasResponse()) {
            throw new \LogicException('There is no response. You should call "hasResponse" to check if the response exists.');
        }

        return $this->response;
    }

    public function setResponse(ResponseInterface $response): self
    {
        $this->response = $response;
        $this->stopPropagation();

        return $this;
    }

    public function getGrantTypeId(): string
    {
        return $this->authorizationRequest->getGrantTypeId();
    }

    public function getClient(): ClientEntityInterface
    {
        return $this->client;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Role[]
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    public function isAuthorizationApproved(): bool
    {
        return $this->authorizationRequest->isAuthorizationApproved();
    }

    public function getRedirectUri(): ?string
    {
        return $this->authorizationRequest->getRedirectUri();
    }

    public function getState(): ?string
    {
        return $this->authorizationRequest->getState();
    }

    public function getCodeChallenge(): string
    {
        return $this->authorizationRequest->getCodeChallenge();
    }

    public function getCodeChallengeMethod(): string
    {
        return $this->authorizationRequest->getCodeChallengeMethod();
    }
}
