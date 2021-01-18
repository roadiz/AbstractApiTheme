<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\Document;

final class DocumentApiSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_serialize',
            'method' => 'onPostSerialize',
            'class' => Document::class,
            'priority' => -1000, // optional priority
        ]];
    }

    /**
     * @param ObjectEvent $event
     * @return void
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $tag = $event->getObject();
        $visitor = $event->getVisitor();
        if ($visitor instanceof SerializationVisitorInterface) {
            $visitor->visitProperty(
                new StaticPropertyMetadata('string', '@type', []),
                'Document'
            );
        }
    }
}
