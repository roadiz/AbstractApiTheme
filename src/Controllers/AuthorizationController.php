<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Themes\AbstractApiTheme\AbstractApiThemeApp;
use Themes\AbstractApiTheme\Converter\UserConverterInterface;
use Themes\AbstractApiTheme\Event\AuthorizationRequestResolveEvent;
use Themes\AbstractApiTheme\Event\AuthorizationRequestResolveEventFactory;

class AuthorizationController extends AbstractApiThemeApp
{
    /**
     * @param Request $request
     * @return Response
     */
    public function defaultAction(
        Request $request
    ): Response {
        /** @var HttpMessageFactoryInterface $psrFactory */
        $psrFactory = $this->get(HttpMessageFactoryInterface::class);
        $httpFoundationFactory = new HttpFoundationFactory();

        return $httpFoundationFactory->createResponse($this->psrDefaultAction(
            $psrFactory->createRequest($request),
            $psrFactory->createResponse(new Response())
        ));
    }

    /**
     * @param ServerRequestInterface $serverRequest
     * @param ResponseInterface $serverResponse
     * @return ResponseInterface
     */
    protected function psrDefaultAction(
        ServerRequestInterface $serverRequest,
        ResponseInterface $serverResponse
    ): ResponseInterface {
        try {
            /** @var AuthorizationServer $server */
            $server = $this->get(AuthorizationServer::class);

            /** @var ClientRepositoryInterface $clientRepository */
            $clientRepository = $this->get(ClientRepositoryInterface::class);

            /** @var UserConverterInterface $userConverter */
            $userConverter = $this->get(UserConverterInterface::class);

            /** @var AuthorizationRequestResolveEventFactory $eventFactory */
            $eventFactory = $this->get(AuthorizationRequestResolveEventFactory::class);
            $authRequest = $server->validateAuthorizationRequest($serverRequest);

            if ('plain' === $authRequest->getCodeChallengeMethod()) {
                /** @var ClientEntityInterface $client */
                $client = $clientRepository->getClientEntity($authRequest->getClient()->getIdentifier());
                if (method_exists($client, 'isPlainTextPkceAllowed') &&
                    !$client->isPlainTextPkceAllowed()) {
                    return OAuthServerException::invalidRequest(
                        'code_challenge_method',
                        'Plain code challenge method is not allowed for this client'
                    )->generateHttpResponse($serverResponse);
                }
            }

            /** @var AuthorizationRequestResolveEvent $event */
            $event = $this->dispatchEvent(
                $eventFactory->fromAuthorizationRequest($authRequest)
            );

            $authRequest->setUser($userConverter->toLeague($event->getUser()));

            if ($event->hasResponse()) {
                return $event->getResponse();
            }

            $authRequest->setAuthorizationApproved($event->getAuthorizationResolution());

            return $server->completeAuthorizationRequest($authRequest, $serverResponse);
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse($serverResponse);
        }
    }
}
