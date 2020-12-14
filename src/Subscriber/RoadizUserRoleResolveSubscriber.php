<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Subscriber;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Themes\AbstractApiTheme\Event\RoleResolveEvent;

class RoadizUserRoleResolveSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            RoleResolveEvent::class => 'onRoleResolve'
        ];
    }

    /**
     * @param RoleResolveEvent $event
     * @return void
     */
    public function onRoleResolve(RoleResolveEvent $event)
    {
        if ($event->getUserIdentifier() !== null) {
            /** @var User|null $user */
            $user = $this->entityManager->getRepository(User::class)->findOneBy([
                'username' => $event->getUserIdentifier()
            ]);
            if (null !== $user && !$user->isSuperAdmin()) {
                $resolvedRoles = [];
                /*
                 * need to check if client is requesting roles that user does not have
                 */
                foreach ($event->getRoles() as $requestedRole) {
                    if ($user->hasRole((string) $requestedRole)) {
                        $resolvedRoles[] = $requestedRole;
                    }
                }

                $event->setRoles($resolvedRoles);
            }
        }
    }
}
