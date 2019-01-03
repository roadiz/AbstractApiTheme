<?php
/**
 * AbstractApiTheme - ApplicationProvider.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-01-03
 */
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\ChainUserProvider;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Themes\AbstractApiTheme\Security\Authentication\Token\ApplicationToken;

class ApplicationProvider implements AuthenticationProviderInterface
{
    /**
     * @var UserProviderInterface
     */
    private $userProvider;

    public function __construct(UserProviderInterface $userProvider)
    {
        $this->userProvider = $userProvider;
    }

    /**
     * @param UserProviderInterface $provider
     * @param TokenInterface        $token
     *
     * @return bool|ApplicationToken
     * @throws AuthenticationException
     */
    protected function doAuth($provider, TokenInterface $token)
    {
        if (!$provider instanceof ApplicationProviderInterface) {
            return false;
        }
        /** @var UserInterface $user */
        $user = $provider->loadApplicationByApiKey($token->getCredentials());
        if ($user) {
            $authenticatedToken = new ApplicationToken($user->getRoles());
            $authenticatedToken->setUser($user);
            return $authenticatedToken;
        }
        throw new AuthenticationException("The API Key authentication failed.");
    }

    /**
     * @inheritDoc
     */
    public function authenticate(TokenInterface $token)
    {
        if($this->userProvider instanceof ChainUserProvider) {
            foreach ($this->userProvider->getProviders() as $provider) {
                $result = $this->doAuth($provider, $token);
                if ($result !== false) {
                    return $result;
                }
            }
        } else {
            $result = $this->doAuth($this->userProvider, $token);
            if ($result !== false) {
                return $result;
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof ApplicationToken;
    }
}