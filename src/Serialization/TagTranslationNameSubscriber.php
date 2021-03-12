<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Exclusion\DisjunctExclusionStrategy;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\AbstractEntities\TranslationInterface;
use RZ\Roadiz\Core\Entities\Tag;
use RZ\Roadiz\Core\Entities\TagTranslation;

final class TagTranslationNameSubscriber implements EventSubscriberInterface
{
    private StaticPropertyMetadata $nameProperty;
    private StaticPropertyMetadata $descriptionProperty;
    private StaticPropertyMetadata $documentsProperty;

    public function __construct()
    {
        $this->nameProperty = new StaticPropertyMetadata(
            'string',
            'name',
            '',
            ['tag_base', 'tag']
        );
        $this->nameProperty->skipWhenEmpty = true;
        $this->descriptionProperty = new StaticPropertyMetadata(
            'string',
            'description',
            '',
            ['tag_base', 'tag']
        );
        $this->descriptionProperty->skipWhenEmpty = true;
        $this->documentsProperty = new StaticPropertyMetadata(
            'array',
            'documents',
            [],
            ['tag_base', 'tag']
        );
        $this->documentsProperty->skipWhenEmpty = true;
    }

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
        /** @var TranslationInterface|null $translation */
        $translation = $context->hasAttribute('translation') ? $context->getAttribute('translation') : null;

        if ($visitor instanceof SerializationVisitorInterface) {
            if (null !== $translation && $translation instanceof TranslationInterface) {
                /** @var TagTranslation|false $tagTranslation */
                $tagTranslation = $object->getTranslatedTagsByTranslation($translation)->first();
                if (false !== $tagTranslation) {
                    if (!$exclusionStrategy->shouldSkipProperty($this->nameProperty, $context)) {
                        $visitor->visitProperty(
                            $this->nameProperty,
                            $tagTranslation->getName()
                        );
                    }

                    if (!$exclusionStrategy->shouldSkipProperty($this->descriptionProperty, $context)) {
                        $visitor->visitProperty(
                            $this->descriptionProperty,
                            $tagTranslation->getDescription()
                        );
                    }

                    if (!$exclusionStrategy->shouldSkipProperty($this->documentsProperty, $context)) {
                        $visitor->visitProperty(
                            $this->documentsProperty,
                            $tagTranslation->getDocuments()
                        );
                    }
                }
            }
        }
    }
}
