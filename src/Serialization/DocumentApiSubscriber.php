<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\Document;
use RZ\Roadiz\Core\Models\DocumentInterface;
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
        /** @var DocumentInterface $document */
        $document = $event->getObject();
        $visitor = $event->getVisitor();
        if ($visitor instanceof SerializationVisitorInterface) {
            /*
             * Add cache-tags to serialization context.
             */
            if ($event->getContext()->hasAttribute('cache-tags') &&
                $event->getContext()->getAttribute('cache-tags') instanceof CacheTagsCollection) {
                /** @var CacheTagsCollection $cacheTags */
                $cacheTags = $event->getContext()->getAttribute('cache-tags');
                $cacheTags->addDocument($document);
            }
            $visitor->visitProperty(
                new StaticPropertyMetadata('string', '@type', []),
                'Document'
            );


            if (in_array('urls', $event->getContext()->getAttribute('groups'))) {
                $visitor->visitProperty(
                    new StaticPropertyMetadata('string', 'url', []),
                    $this->packages->getUrl(
                        $document->getRelativePath() ?? '',
                        \RZ\Roadiz\Utils\Asset\Packages::DOCUMENTS
                    )
                );
            }

            if (in_array('thumbnail', $event->getContext()->getAttribute('groups')) &&
                $document instanceof HasThumbnailInterface) {
                $visitor->visitProperty(
                    new StaticPropertyMetadata(Document::class, 'thumbnail', []),
                    $document->getThumbnails()->first() ?: null
                );
            }
        }
    }
}
