<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Subscriber;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Entities\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Themes\AbstractApiTheme\Event\RoleResolveEvent;

class RoadizUserRoleResolveSubscriber implements EventSubscriberInterface
{
    protected ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
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
            $user = $this->managerRegistry->getRepository(User::class)->findOneBy([
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
