<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Subscriber;

use Nyholm\Psr7\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Controller\ControllerReference;
use Symfony\Component\HttpKernel\Fragment\InlineFragmentRenderer;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Themes\AbstractApiTheme\Controllers\GrantPermissionController;
use Themes\AbstractApiTheme\Event\AuthorizationRequestResolveEvent;

class AuthorizationRequestSubscriber implements EventSubscriberInterface
{
    const AUTHORIZATION_GRANT = 'authorization_code';

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var InlineFragmentRenderer
     */
    private InlineFragmentRenderer $renderer;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param RequestStack $requestStack
     * @param InlineFragmentRenderer $renderer
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        InlineFragmentRenderer $renderer
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
        $this->renderer = $renderer;
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

    /**
     * @param AuthorizationRequestResolveEvent $event
     * @return void
     * @throws \Exception
     */
    public function onAuthorizationRequest(AuthorizationRequestResolveEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();

        $token = $this->tokenStorage->getToken();
        if (null !== $token && $token->getUser() instanceof UserInterface) {
            $event->setUser($token->getUser());
        }

        if (null === $request) {
            $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_DENIED);
            return;
        }

        // only handle post requests for logged-in users:
        // get requests will be intercepted and shown the login form
        // other verbs we will handle as an authorization denied
        // and this implementation ensures a user is set at this point already
        if ($request->getMethod() !== 'POST' && null === $event->getUser()) {
            $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_DENIED);
            return;
        }

        if (!$request->request->has('action')) {
            $response = $this->renderer->render(new ControllerReference(
                GrantPermissionController::class . '::defaultAction',
                [
                    'roles' => $event->getRoles(),
                    'client' => $event->getClient(),
                    'grant' => static::AUTHORIZATION_GRANT,
                ]
            ), $request);
            $event->setResponse(new Response($response->getStatusCode(), [], (string) $response->getContent()));
        } else {
            // 2. grant operation, either grants or denies
            if ($request->request->get('action') === static::AUTHORIZATION_GRANT) {
                $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
            } else {
                $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_DENIED);
            }
        }
    }
}
