<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use Themes\AbstractApiTheme\Breadcrumbs\Breadcrumbs;
use Themes\AbstractApiTheme\Breadcrumbs\BreadcrumbsFactoryInterface;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Exclusion\DisjunctExclusionStrategy;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\NodesSources;

final class BreadcrumbsSubscriber implements EventSubscriberInterface
{
    private BreadcrumbsFactoryInterface $breadcrumbsFactory;
    private StaticPropertyMetadata $propertyMetadata;

    /**
     * @param BreadcrumbsFactoryInterface $breadcrumbsFactory
     */
    public function __construct(BreadcrumbsFactoryInterface $breadcrumbsFactory)
    {
        $this->breadcrumbsFactory = $breadcrumbsFactory;
        $this->propertyMetadata = new StaticPropertyMetadata(
            Breadcrumbs::class,
            'breadcrumbs',
            [],
            ['breadcrumbs'] // This is groups to allow this property to be serialized
        );
        $this->propertyMetadata->skipWhenEmpty = true;
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

    private function supports(ObjectEvent $event, PropertyMetadata $propertyMetadata): bool
    {
        $context = $event->getContext();
        $exclusionStrategy = $context->getExclusionStrategy() ?? new DisjunctExclusionStrategy();
        return $event->getObject() instanceof NodesSources &&
            !$exclusionStrategy->shouldSkipProperty($propertyMetadata, $context);
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $nodeSource = $event->getObject();
        /** @var SerializationContext $context */
        $context = $event->getContext();
        /** @var SerializationVisitorInterface $visitor */
        $visitor = $context->getVisitor();

        if ($this->supports($event, $this->propertyMetadata)) {
            $visitor->visitProperty(
                $this->propertyMetadata,
                $this->breadcrumbsFactory->create($nodeSource)
            );
        }
    }
}
