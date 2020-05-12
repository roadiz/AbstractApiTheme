<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Exceptions\NoTranslationAvailableException;

final class TagTranslationNameSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_serialize',
            'method' => 'onPostSerialize',
            'class' => Tag::class,
            'priority' => -10,
        ]];
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $object = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();
        /** @var Translation|null $translation */
        $translation = $context->hasAttribute('translation') ? $context->getAttribute('translation') : null;

        if ($visitor instanceof SerializationVisitorInterface) {
            if (null !== $translation && $translation instanceof Translation) {
                /** @var TagTranslation|false $tagTranslation */
                $tagTranslation = $object->getTranslatedTagsByTranslation($translation)->first();
            } else {
                /** @var TagTranslation|false $tagTranslation */
                $tagTranslation = $object->getTranslatedTags()->first();
            }

            if (false !== $tagTranslation) {
                $visitor->visitProperty(
                    new StaticPropertyMetadata('string', 'name', []),
                    $tagTranslation->getName()
                );
            } else {
                $visitor->visitProperty(
                    new StaticPropertyMetadata('string', 'name', []),
                    $object->getTagName()
                );
            }
        }
    }
}
