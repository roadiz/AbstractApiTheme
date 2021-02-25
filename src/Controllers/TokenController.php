<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Themes\AbstractApiTheme\AbstractApiThemeApp;

class TokenController extends AbstractApiThemeApp
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

        $response = $httpFoundationFactory->createResponse($this->psrDefaultAction(
            $psrFactory->createRequest($request),
            $psrFactory->createResponse(new Response())
        ));

        return $this->makeResponseCachable($request, $response, $this->get('api.cache.ttl'));
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
        /** @var AuthorizationServer $server */
        $server = $this->get(AuthorizationServer::class);

        try {
            return $server->respondToAccessTokenRequest(
                $serverRequest,
                $serverResponse
            );
        } catch (OAuthServerException $e) {
            return $e->generateHttpResponse($serverResponse);
        }
    }
}
