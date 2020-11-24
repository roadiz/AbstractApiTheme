<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Themes\AbstractApiTheme\Event\AuthorizationRequestResolveEvent;

class AuthorizationRequestSubscriber implements EventSubscriberInterface
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            AuthorizationRequestResolveEvent::class => 'onAuthorizationRequest'
        ];
    }

    public function onAuthorizationRequest(AuthorizationRequestResolveEvent $event)
    {
        $token = $this->tokenStorage->getToken();
        if (null !== $token && $token->getUser() instanceof UserInterface) {
            $event->setUser($token->getUser());
            $event->resolveAuthorization(true);
        }
    }
}
