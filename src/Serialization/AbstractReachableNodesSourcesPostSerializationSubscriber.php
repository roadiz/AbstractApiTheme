<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use Doctrine\Common\Collections\ArrayCollection;
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

    protected function once(): bool
    {
        return true;
    }

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
     * @return class-string<NodesSources>
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
        $alreadyCalled = false;

        if ($context->hasAttribute('locks')) {
            /** @var ArrayCollection $locks */
            $locks = $context->getAttribute('locks');
            $alreadyCalled = $this->once() && $locks->contains(static::class);
        }

        return !$alreadyCalled &&
            !$exclusionStrategy->shouldSkipProperty($propertyMetadata, $context) &&
            $visitor instanceof SerializationVisitorInterface &&
            $nodeSource instanceof $supportedType &&
            null !== $nodeSource->getNode() &&
            $nodeSource->getNode()->getStatus() <= Node::PUBLISHED &&
            null !== $nodeSource->getNode()->getNodeType() &&
            $nodeSource->getNode()->getNodeType()->isReachable();
    }

    abstract public function onPostSerialize(ObjectEvent $event): void;
}
