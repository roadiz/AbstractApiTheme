<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;

abstract class AbstractDataUriDeserializeSubscriber implements EventSubscriberInterface
{
    /**
     * @return string[][]
     */
    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_deserialize',
            'method' => 'onPostDeserialize'
        ]];
    }

    /**
     * @param ObjectEvent $event
     * @return void
     */
    abstract public function onPostDeserialize(ObjectEvent $event);
}
