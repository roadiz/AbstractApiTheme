<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Subscriber;

use Nyholm\Psr7\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Themes\AbstractApiTheme\Event\AuthorizationRequestResolveEvent;
use Twig\Environment;

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
     * @var Environment|null
     */
    protected $twig;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param RequestStack $requestStack
     * @param Environment|null $twig
     */
    public function __construct(
        TokenStorageInterface $tokenStorage,
        RequestStack $requestStack,
        ?Environment $twig = null
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->requestStack = $requestStack;
        $this->twig = $twig;
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
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function onAuthorizationRequest(AuthorizationRequestResolveEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();

        $token = $this->tokenStorage->getToken();
        if (null !== $token && $token->getUser() instanceof UserInterface) {
            $event->setUser($token->getUser());
        }

        // only handle post requests for logged-in users:
        // get requests will be intercepted and shown the login form
        // other verbs we will handle as an authorization denied
        // and this implementation ensures a user is set at this point already
        if ($request->getMethod() !== 'POST' && null === $event->getUser()) {
            $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_DENIED);
            return;
        }

        if (null === $this->twig) {
            $event->resolveAuthorization(AuthorizationRequestResolveEvent::AUTHORIZATION_APPROVED);
            return;
        }

        if (!$request->request->has('action')) {
            // 1. successful login, goes to grant page
            $content = $this->twig->render('security/grant.html.twig', [
                'roles' => $event->getRoles(),
                'client' => $event->getClient(),
                'grant' => static::AUTHORIZATION_GRANT,
                // very simple way to ensure user gets to this point in the
                // flow when granting or denying is to pre-add their credentials
                'email' => $request->request->get('email'),
                'password' => $request->request->get('password'),
            ]);

            $response = new Response(200, [], $content);
            $event->setResponse($response);
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
