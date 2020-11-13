<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

class TokenSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_serialize',
            'method' => 'onPostSerialize',
        ]];
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $object = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if ($visitor instanceof SerializationVisitorInterface &&
            $object instanceof AbstractToken &&
            $context->hasAttribute('groups')) {
            if (in_array('user', $context->getAttribute('groups')) &&
                $object->getUser() instanceof UserInterface) {
                $visitor->visitProperty(
                    new StaticPropertyMetadata(UserInterface::class, 'user', []),
                    $object->getUser()
                );
            }
            if (in_array('roles', $context->getAttribute('groups'))) {
                $visitor->visitProperty(
                    new StaticPropertyMetadata('array', 'roles', []),
                    $object->getRoleNames()
                );
            }
        }
    }
}
