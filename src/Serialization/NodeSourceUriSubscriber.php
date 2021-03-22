<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Exclusion\DisjunctExclusionStrategy;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NodeSourceUriSubscriber extends AbstractReachableNodesSourcesPostSerializationSubscriber
{
    private UrlGeneratorInterface $urlGenerator;
    private int $referenceType;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param int $referenceType
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->referenceType = $referenceType;
        $this->propertyMetadata = new StaticPropertyMetadata(
            'string',
            'url',
            '',
            ['urls']
        );
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $nodeSource = $event->getObject();
        /** @var SerializationVisitorInterface $visitor */
        $visitor = $event->getVisitor();

        if ($this->supports($event, $this->propertyMetadata)) {
            $visitor->visitProperty(
                $this->propertyMetadata,
                $this->urlGenerator->generate(
                    RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                    [
                        RouteObjectInterface::ROUTE_OBJECT => $nodeSource
                    ],
                    $this->referenceType
                )
            );
        }
    }
}
