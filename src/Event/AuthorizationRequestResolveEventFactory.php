<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Event;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Themes\AbstractApiTheme\Converter\ScopeConverter;

class AuthorizationRequestResolveEventFactory
{
    /**
     * @var ScopeConverter
     */
    private $scopeConverter;

    /**
     * @var ClientRepositoryInterface
     */
    private $clientRepository;

    public function __construct(ScopeConverter $scopeConverter, ClientRepositoryInterface $clientRepository)
    {
        $this->scopeConverter = $scopeConverter;
        $this->clientRepository = $clientRepository;
    }

    public function fromAuthorizationRequest(AuthorizationRequest $authorizationRequest): AuthorizationRequestResolveEvent
    {
        $roles = $this->scopeConverter->toRoles($authorizationRequest->getScopes());

        $client = $this->clientRepository->getClientEntity($authorizationRequest->getClient()->getIdentifier());

        if (null === $client) {
            throw new \RuntimeException(sprintf('No client found for the given identifier \'%s\'.', $authorizationRequest->getClient()->getIdentifier()));
        }

        return new AuthorizationRequestResolveEvent($authorizationRequest, $roles, $client);
    }
}
