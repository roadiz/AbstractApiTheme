<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Event;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use RZ\Roadiz\Core\Entities\Role;

class AuthorizationRequestResolveEventFactory
{
    /**
     * @var ScopeRepositoryInterface
     */
    private $scopeRepository;

    /**
     * @var ClientRepositoryInterface
     */
    private $clientRepository;

    public function __construct(ScopeRepositoryInterface $scopeRepository, ClientRepositoryInterface $clientRepository)
    {
        $this->scopeRepository = $scopeRepository;
        $this->clientRepository = $clientRepository;
    }

    public function fromAuthorizationRequest(AuthorizationRequest $authorizationRequest): AuthorizationRequestResolveEvent
    {
        /** @var Role[] $roles */
        $roles = $this->scopeRepository->finalizeRoles(
            $authorizationRequest->getScopes(),
            $authorizationRequest->getGrantTypeId(),
            $authorizationRequest->getClient(),
            $authorizationRequest->getUser() ? $authorizationRequest->getUser()->getIdentifier() : null
        );
        $client = $this->clientRepository->getClientEntity($authorizationRequest->getClient()->getIdentifier());

        if (null === $client) {
            throw new \RuntimeException(sprintf(
                'No client found for the given identifier \'%s\'.',
                $authorizationRequest->getClient()->getIdentifier()
            ));
        }

        return new AuthorizationRequestResolveEvent($authorizationRequest, $roles, $client);
    }
}
