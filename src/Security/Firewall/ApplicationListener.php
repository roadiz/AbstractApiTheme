<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Security\Firewall;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Themes\AbstractApiTheme\Exception\InvalidApiKeyException;
use Themes\AbstractApiTheme\Extractor\ApplicationExtractorInterface;
use Themes\AbstractApiTheme\Security\Authentication\Token\ApplicationToken;
use Themes\AbstractApiTheme\Subscriber\CachableApiResponseSubscriber;

class ApplicationListener
{
    private AuthenticationManagerInterface $authenticationManager;
    private TokenStorageInterface $tokenStorage;
    private ApplicationExtractorInterface $applicationExtractor;

    /**
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
     * @param RequestEvent $event
     * @return void
     */
    public function __invoke(RequestEvent $event)
    {
        $request = $event->getRequest();

        if (false === $this->applicationExtractor->hasApiKey($request)) {
            return;
        }

        $application = $this->applicationExtractor->extractApplication($request);

        if (null !== $application) {
            $token = new ApplicationToken();
            $token->setUser($application);
            $token->setReferer($request->headers->get('origin', '') ?? '');

            if (!empty($application->getRefererRegex())) {
                $request->attributes->set(CachableApiResponseSubscriber::VARY_ON_ORIGIN_ATTRIBUTE, true);
            }

            try {
                $authToken = $this->authenticationManager->authenticate($token);
                $this->tokenStorage->setToken($authToken);
                $this->onSuccess($authToken);
                return;
            } catch (AuthenticationException $failed) {
                $token = $this->tokenStorage->getToken();
                if ($token instanceof ApplicationToken &&
                    $token->getCredentials() === $application->getApiKey()) {
                    $this->tokenStorage->setToken(null);
                }
                $message = $failed->getMessage();
            }
        } else {
            $message = 'Api key is not valid.';
        }

        throw new InvalidApiKeyException($message);
    }

    /**
     * @param TokenInterface $authToken
     * @return void
     */
    protected function onSuccess(TokenInterface $authToken)
    {
        $authToken->setAuthenticated(true);
    }
}
