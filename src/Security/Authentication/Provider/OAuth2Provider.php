<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Security\Authentication\Provider;

use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use RuntimeException;
use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Themes\AbstractApiTheme\Security\Authentication\Token\OAuth2Token;
use Themes\AbstractApiTheme\Security\Authentication\Token\OAuth2TokenFactory;

final class OAuth2Provider implements AuthenticationProviderInterface
{
    private UserProviderInterface $userProvider;
    private ResourceServer $resourceServer;
    private OAuth2TokenFactory $oauth2TokenFactory;
    private string $providerKey;

    public function __construct(
        UserProviderInterface $userProvider,
        ResourceServer $resourceServer,
        OAuth2TokenFactory $oauth2TokenFactory,
        string $providerKey
    ) {
        $this->userProvider = $userProvider;
        $this->resourceServer = $resourceServer;
        $this->oauth2TokenFactory = $oauth2TokenFactory;
        $this->providerKey = $providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function authenticate(TokenInterface $token)
    {
        if (!$this->supports($token)) {
            throw new RuntimeException(sprintf('This authentication provider can only handle tokes of type \'%s\'.', OAuth2Token::class));
        }

        try {
            $request = $this->resourceServer->validateAuthenticatedRequest(
                $token->getAttribute('server_request')
            );
        } catch (OAuthServerException $e) {
            throw new AuthenticationException('The resource server rejected the request.', 0, $e);
        }

        $user = $this->getAuthenticatedUser(
            $request->getAttribute('oauth_user_id')
        );

        $token = $this->oauth2TokenFactory->createOAuth2Token($request, $user, $this->providerKey);
        $token->setAuthenticated(true);

        return $token;
    }

    /**
     * {@inheritdoc}
     */
    public function supports(TokenInterface $token)
    {
        return $token instanceof OAuth2Token && $this->providerKey === $token->getProviderKey();
    }

    private function getAuthenticatedUser(string $userIdentifier): ?UserInterface
    {
        if ('' === $userIdentifier) {
            /*
             * If the identifier is an empty string, that means that the
             * access token isn't bound to a user defined in the system.
             */
            return null;
        }

        return $this->userProvider->loadUserByUsername($userIdentifier);
    }
}
