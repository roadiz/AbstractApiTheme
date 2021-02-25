<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Exclusion\DisjunctExclusionStrategy;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Models\HasThumbnailInterface;
use Symfony\Component\Asset\Packages;
use Themes\AbstractApiTheme\Cache\CacheTagsCollection;

final class DocumentApiSubscriber implements EventSubscriberInterface
{
    private Packages $packages;

    /**
     * @param Packages $packages
     */
    public function __construct(Packages $packages)
    {
        $this->packages = $packages;
    }

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
        /** @var Document $document */
        $document = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();
        $exclusionStrategy = $event->getContext()->getExclusionStrategy() ?? new DisjunctExclusionStrategy();

        if ($visitor instanceof SerializationVisitorInterface) {
            /*
             * Add cache-tags to serialization context.
             */
            if ($context->hasAttribute('cache-tags') &&
                $context->getAttribute('cache-tags') instanceof CacheTagsCollection) {
                /** @var CacheTagsCollection $cacheTags */
                $cacheTags = $context->getAttribute('cache-tags');
                $cacheTags->addDocument($document);
            }
            $visitor->visitProperty(
                new StaticPropertyMetadata('string', '@type', []),
                'Document'
            );

            $urlProperty = new StaticPropertyMetadata(
                'string',
                'url',
                '',
                ['urls']
            );
            if (in_array('urls', $context->getAttribute('groups')) &&
                !$exclusionStrategy->shouldSkipProperty($urlProperty, $context)) {
                $visitor->visitProperty(
                    $urlProperty,
                    $this->packages->getUrl(
                        $document->getRelativePath() ?? '',
                        \RZ\Roadiz\Utils\Asset\Packages::DOCUMENTS
                    )
                );
            }

            $thumbnailProperty = new StaticPropertyMetadata(
                Document::class,
                'thumbnail',
                null,
                ['thumbnail']
            );
            if (in_array('thumbnail', $context->getAttribute('groups')) &&
                !$exclusionStrategy->shouldSkipProperty($thumbnailProperty, $context) &&
                $document instanceof HasThumbnailInterface) {
                $visitor->visitProperty(
                    $thumbnailProperty,
                    $document->getThumbnails()->first() ?: null
                );
            }
        }
    }
}
