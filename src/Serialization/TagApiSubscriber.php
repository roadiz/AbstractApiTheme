<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Tag;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Themes\AbstractApiTheme\Cache\CacheTagsCollection;

final class TagApiSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_serialize',
            'method' => 'onPostSerialize',
            'class' => Tag::class,
            'priority' => -1000, // optional priority
        ]];
    }

    /**
     * @param ObjectEvent $event
     * @return void
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        /** @var Tag $tag */
        $tag = $event->getObject();
        $visitor = $event->getVisitor();
        if ($visitor instanceof SerializationVisitorInterface) {
            /*
             * Add cache-tags to serialization context.
             */
            if ($event->getContext()->hasAttribute('cache-tags') &&
                $event->getContext()->getAttribute('cache-tags') instanceof CacheTagsCollection) {
                /** @var CacheTagsCollection $cacheTags */
                $cacheTags = $event->getContext()->getAttribute('cache-tags');
                $cacheTag = 'tag_'.$tag->getId();
                if (!$cacheTags->contains($cacheTag)) {
                    $cacheTags->add($cacheTag);
                }
            }
            $visitor->visitProperty(
                new StaticPropertyMetadata('string', '@type', []),
                'Tag'
            );
        }
    }
}
