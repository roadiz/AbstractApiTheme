<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Security\Authentication\Provider;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Themes\AbstractApiTheme\Security\Authentication\Token\ApplicationToken;

class AuthenticationProvider implements AuthenticationProviderInterface
{
    private ApplicationProviderInterface $applicationProvider;

    public function __construct(ApplicationProviderInterface $applicationProvider)
    {
        $this->applicationProvider = $applicationProvider;
    }

    /**
     * @param ApplicationProviderInterface $provider
     * @param TokenInterface        $token
     *
     * @return false|ApplicationToken
     * @throws AuthenticationException
     */
    protected function doAuth($provider, TokenInterface $token)
    {
        if (!$provider instanceof ApplicationProviderInterface) {
            return false;
        }
        if ($token instanceof ApplicationToken) {
            if (false === $token->getCredentials()) {
                throw new AuthenticationException("Token credentials are invalid.");
            }
            $application = $provider->loadApplicationByApiKey($token->getCredentials());
            if (null !== $application) {
                if (!$application->isEnabled()) {
                    throw new AuthenticationException('The API Key is disabled.');
                }
                if (!$application->isAccountNonExpired()) {
                    throw new AuthenticationException('The API Key has expired.');
                }
                if (!$application->isAccountNonLocked()) {
                    throw new AuthenticationException('The API Key is locked.');
                }
                if ($application->getRefererRegex() !== "") {
                    if (preg_match('#'.$application->getRefererRegex().'#', $token->getReferer()) === 0) {
                        throw new AuthenticationException('Origin "'.$token->getReferer().'" is not allowed.');
                    }
                }

                $authenticatedToken = new ApplicationToken($application->getRoles());
                $authenticatedToken->setUser($application);
                return $authenticatedToken;
            }
        }
        throw new AuthenticationException("The API Key authentication failed.");
    }

    /**
     * @inheritDoc
     */
    public function authenticate(TokenInterface $token)
    {
        if ($this->applicationProvider instanceof ChainUserProvider) {
            foreach ($this->applicationProvider->getProviders() as $provider) {
                $result = $this->doAuth($provider, $token);
                if ($result !== false) {
                    return $result;
                }
            }
        } else {
            $result = $this->doAuth($this->applicationProvider, $token);
            if ($result !== false) {
                return $result;
            }
        }

        $exception = new AuthenticationException(
            'No authentication provider were able to authenticate token.',
            Response::HTTP_UNAUTHORIZED
        );
        $exception->setToken($token);
        throw $exception;
    }

    /**
     * @inheritDoc
     */
    public function supports(TokenInterface $token)
    {
        return ($token instanceof ApplicationToken);
    }
}
