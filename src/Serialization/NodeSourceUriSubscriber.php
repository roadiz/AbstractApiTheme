<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\Node;
use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NodeSourceUriSubscriber implements EventSubscriberInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private UrlGeneratorInterface $urlGenerator;
    /**
     * @var int
     */
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

    public function onPostSerialize(ObjectEvent $event): void
    {
        $nodeSource = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if ($context->hasAttribute('groups') &&
            in_array('urls', $context->getAttribute('groups'))) {
            if ($nodeSource instanceof NodesSources &&
                null !== $nodeSource->getNode() &&
                null !== $nodeSource->getNode()->getNodeType() &&
                $visitor instanceof SerializationVisitorInterface &&
                $nodeSource->getNode()->getStatus() <= Node::PUBLISHED &&
                $nodeSource->getNode()->getNodeType()->isReachable()
            ) {
                $visitor->visitProperty(
                    new StaticPropertyMetadata('string', 'url', []),
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
}
