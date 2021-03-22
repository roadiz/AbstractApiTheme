<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Exclusion\DisjunctExclusionStrategy;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;

abstract class AbstractReachableNodesSourcesPostSerializationSubscriber implements EventSubscriberInterface
{
    protected StaticPropertyMetadata $propertyMetadata;

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_serialize',
            'method' => 'onPostSerialize',
        ]];
    }

    /**
     * @return class-string
     */
    protected function getSupportedType(): string
    {
        return NodesSources::class;
    }

    protected function supports(ObjectEvent $event, PropertyMetadata $propertyMetadata): bool
    {
        $nodeSource = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();
        $exclusionStrategy = $context->getExclusionStrategy() ?? new DisjunctExclusionStrategy();
        $supportedType = $this->getSupportedType();

        return !$exclusionStrategy->shouldSkipProperty($propertyMetadata, $context) &&
            $nodeSource instanceof $supportedType &&
            null !== $nodeSource->getNode() &&
            $nodeSource->getNode()->getStatus() <= Node::PUBLISHED &&
            $visitor instanceof SerializationVisitorInterface &&
            null !== $nodeSource->getNode()->getNodeType() &&
            $nodeSource->getNode()->getNodeType()->isReachable();
    }

    abstract public function onPostSerialize(ObjectEvent $event): void;
}
