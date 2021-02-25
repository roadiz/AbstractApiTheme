<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Exclusion\DisjunctExclusionStrategy;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;
use RZ\Roadiz\Core\Entities\Translation;

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
            'priority' => 1000,
        ]];
    }

    /**
     * @param ObjectEvent $event
     * @return void
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $object = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();
        $exclusionStrategy = $event->getContext()->getExclusionStrategy() ?? new DisjunctExclusionStrategy();
        /** @var Translation|null $translation */
        $translation = $context->hasAttribute('translation') ? $context->getAttribute('translation') : null;

        if ($visitor instanceof SerializationVisitorInterface) {
            if (null !== $translation && $translation instanceof Translation) {
                /** @var TagTranslation|false $tagTranslation */
                $tagTranslation = $object->getTranslatedTagsByTranslation($translation)->first();
                if (false !== $tagTranslation) {
                    $nameProperty = new StaticPropertyMetadata(
                        'string',
                        'name',
                        '',
                        ['tag_base', 'tag']
                    );
                    if (!$exclusionStrategy->shouldSkipProperty($nameProperty, $context)) {
                        $visitor->visitProperty(
                            $nameProperty,
                            $tagTranslation->getName()
                        );
                    }

                    $descriptionProperty = new StaticPropertyMetadata(
                        'string',
                        'description',
                        '',
                        ['tag_base', 'tag']
                    );
                    if (!$exclusionStrategy->shouldSkipProperty($descriptionProperty, $context)) {
                        $visitor->visitProperty(
                            $descriptionProperty,
                            $tagTranslation->getDescription()
                        );
                    }

                    $documentsProperty = new StaticPropertyMetadata(
                        'array',
                        'documents',
                        [],
                        ['tag_base', 'tag']
                    );
                    if (!$exclusionStrategy->shouldSkipProperty($documentsProperty, $context)) {
                        $visitor->visitProperty(
                            $documentsProperty,
                            $tagTranslation->getDocuments()
                        );
                    }
                }
            }
        }
    }
}
