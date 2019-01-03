<?php
/**
 * AbstractApiTheme - ApplicationListener.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-01-03
 */
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Security\Firewall;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Themes\AbstractApiTheme\Entity\Application;
use Themes\AbstractApiTheme\Extractor\ApplicationExtractorInterface;
use Themes\AbstractApiTheme\Security\Authentication\Token\ApplicationToken;

class ApplicationListener implements ListenerInterface
{
    /**
     * @var AuthenticationManagerInterface
     */
    private $authenticationManager;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var ApplicationExtractorInterface
     */
    private $applicationExtractor;

    /**
     *
     *
     * @param AuthenticationManagerInterface $authenticationManager
     * @param TokenStorageInterface          $tokenStorage
     * @param ApplicationExtractorInterface  $applicationExtractor
     */
    public function __construct(
        AuthenticationManagerInterface $authenticationManager,
        TokenStorageInterface $tokenStorage,
        ApplicationExtractorInterface $applicationExtractor
    ) {
        $this->authenticationManager = $authenticationManager;
        $this->tokenStorage = $tokenStorage;
        $this->applicationExtractor = $applicationExtractor;
    }

    /**
     * @param GetResponseEvent $event
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (false === $this->applicationExtractor->hasApiKey($request)) {
            $response = new Response();
            $response->setStatusCode(Response::HTTP_UNAUTHORIZED);
            $event->setResponse($response);
            return;
        }

        /** @var Application $application */
        $application = $this->applicationExtractor->extractApplication($request);

        if (null !== $application) {
            $token = new ApplicationToken();
            $token->setApplication($application);

            try {
                $authToken = $this->authenticationManager->authenticate($token);
                $this->tokenStorage->setToken($authToken);
                return;
            } catch (AuthenticationException $failed) {
                $token = $this->tokenStorage->getToken();
                if ($token instanceof ApplicationToken && $token->getCredentials() === $application->getApiKey()) {
                    $this->tokenStorage->setToken(null);
                }
                $message = $failed->getMessage();
            }
        } else {
            $message = 'Api key is not valid.';
        }

        $response = new Response();
        $response->setContent($message);
        $response->setStatusCode(Response::HTTP_FORBIDDEN);
        $event->setResponse($response);
    }
}