<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\src\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NodeSourceApiSubscriber implements EventSubscriberInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * NodeSourceApiSubscriber constructor.
     *
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }


    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_serialize',
            'method' => 'onPostSerialize',
            'priority' => -1000, // optional priority
        ]];
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        $nodeSource = $event->getObject();
        $visitor = $event->getVisitor();
        $context = $event->getContext();

        if ($visitor instanceof SerializationVisitorInterface &&
            $nodeSource instanceof NodesSources) {
            $visitor->visitProperty(
                new StaticPropertyMetadata('string', '@id', []),
                $this->urlGenerator->generate(
                    'get_single_'.mb_strtolower($nodeSource->getNode()->getNodeType()->getName()),
                    [
                        'id' => $nodeSource->getNode()->getId()
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            );
        }
    }
}
