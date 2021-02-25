<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Exclusion\DisjunctExclusionStrategy;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Handlers\NodesSourcesHandler;

final class SeoDataSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private string $siteName;
    /**
     * @var string
     */
    private string $siteDescription;
    /**
     * @var string
     */
    private string $format;

    /**
     * @param string $siteName
     */
    public function __construct(string $siteName, string $siteDescription, string $format = '%s – %s')
    {
        $this->siteName = $siteName;
        $this->siteDescription = $siteDescription;
        $this->format = $format;
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
            $titleProperty = new StaticPropertyMetadata(
                'string',
                'metaTitle',
                '',
                ['nodes_sources_base']
            );
            if (!$exclusionStrategy->shouldSkipProperty($titleProperty, $context) &&
                $nodeSource->getMetaTitle() === '') {
                $visitor->visitProperty(
                    $titleProperty,
                    sprintf($this->format, $nodeSource->getTitle(), $this->siteName)
                );
            }

            $descriptionProperty = new StaticPropertyMetadata(
                'string',
                'metaDescription',
                '',
                ['nodes_sources_base']
            );
            if (!$exclusionStrategy->shouldSkipProperty($descriptionProperty, $context) &&
                $nodeSource->getMetaDescription() === '' && $this->siteDescription !== '') {
                $visitor->visitProperty(
                    $descriptionProperty,
                    sprintf($this->format, $nodeSource->getTitle(), $this->siteDescription)
                );
            }
        }
    }
}
