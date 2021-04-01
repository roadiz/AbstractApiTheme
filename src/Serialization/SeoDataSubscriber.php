<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Exclusion\DisjunctExclusionStrategy;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\NodesSources;

final class SeoDataSubscriber implements EventSubscriberInterface
{
    private string $siteName;
    private string $siteDescription;
    private string $format;
    private StaticPropertyMetadata $titleProperty;
    private StaticPropertyMetadata $descriptionProperty;

    /**
     * @param string $siteName
     * @param string $siteDescription
     * @param string $format
     */
    public function __construct(string $siteName, string $siteDescription, string $format = '%s – %s')
    {
        $this->siteName = $siteName;
        $this->siteDescription = $siteDescription;
        $this->format = $format;
        $this->titleProperty = new StaticPropertyMetadata(
            'string',
            'metaTitle',
            '',
            ['nodes_sources_base', 'nodes_sources']
        );
        $this->titleProperty->skipWhenEmpty = true;
        $this->descriptionProperty = new StaticPropertyMetadata(
            'string',
            'metaDescription',
            '',
            ['nodes_sources_base', 'nodes_sources']
        );
        $this->descriptionProperty->skipWhenEmpty = true;
    }

    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_serialize',
            'method' => 'onPostSerialize'
        ]];
    }

    /**
     * @param ObjectEvent $event
     * @return void
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $nodeSource = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();
        $exclusionStrategy = $event->getContext()->getExclusionStrategy() ?? new DisjunctExclusionStrategy();

        if ($this->siteName !== '' &&
            $visitor instanceof SerializationVisitorInterface &&
            $nodeSource instanceof NodesSources &&
            null !== $nodeSource->getNode() &&
            null !== $nodeSource->getNode()->getNodeType() &&
            $nodeSource->getNode()->getNodeType()->isReachable()) {
            if (!$exclusionStrategy->shouldSkipProperty($this->titleProperty, $context) &&
                $nodeSource->getMetaTitle() === '') {
                $visitor->visitProperty(
                    $this->titleProperty,
                    sprintf($this->format, $nodeSource->getTitle(), $this->siteName)
                );
            }

            if (!$exclusionStrategy->shouldSkipProperty($this->descriptionProperty, $context) &&
                $nodeSource->getMetaDescription() === '' &&
                $this->siteDescription !== '') {
                $visitor->visitProperty(
                    $this->descriptionProperty,
                    sprintf($this->format, $nodeSource->getTitle(), $this->siteDescription)
                );
            }
        }
    }
}
