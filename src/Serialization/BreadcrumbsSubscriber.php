<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use Themes\AbstractApiTheme\Breadcrumbs\BreadcrumbsFactoryInterface;
use Themes\AbstractApiTheme\Breadcrumbs\BreadcrumbsInterface;

final class BreadcrumbsSubscriber extends AbstractReachableNodesSourcesPostSerializationSubscriber
{
    private BreadcrumbsFactoryInterface $breadcrumbsFactory;

    protected function atRoot(): bool
    {
        return true;
    }

    protected static function getPriority(): int
    {
        return -1000;
    }

    /**
     * @param BreadcrumbsFactoryInterface $breadcrumbsFactory
     */
    public function __construct(BreadcrumbsFactoryInterface $breadcrumbsFactory)
    {
        $this->breadcrumbsFactory = $breadcrumbsFactory;
        $this->propertyMetadata = new StaticPropertyMetadata(
            BreadcrumbsInterface::class,
            'breadcrumbs',
            [],
            ['breadcrumbs'] // This is groups to allow this property to be serialized
        );
        $this->propertyMetadata->skipWhenEmpty = true;
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $nodeSource = $event->getObject();
        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();
        /** @var SerializationContext $context */
        $context = $event->getContext();

        if ($this->supports($event, $this->propertyMetadata)) {
            if ($context->hasAttribute('locks')) {
                $context->getAttribute('locks')->add(static::class);
            }
            $visitor->visitProperty(
                $this->propertyMetadata,
                $this->breadcrumbsFactory->create($nodeSource)
            );
        }
    }
}
