<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NodeSourceApiSubscriber implements EventSubscriberInterface
{
    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;
    /**
     * @var int
     */
    private $referenceType;

    /**
     * @param UrlGeneratorInterface $urlGenerator
     * @param int $referenceType
     */
    public function __construct(
        UrlGeneratorInterface $urlGenerator,
        int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL
    ) {
        $this->urlGenerator = $urlGenerator;
        $this->referenceType = $referenceType;
    }

    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_serialize',
            'method' => 'onPostSerialize',
            'priority' => -1000, // optional priority
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

        if ($visitor instanceof SerializationVisitorInterface &&
            $nodeSource instanceof NodesSources &&
            null !== $nodeSource->getNode()) {
            $className = get_class($nodeSource);
            $visitor->visitProperty(
                new StaticPropertyMetadata('string', '@type', []),
                str_replace(
                    [
                        'GeneratedNodeSources\\NS',
                        'RZ\\Roadiz\\Core\\Entities\\',
                        'Proxies\\__CG__\\'
                    ],
                    ['', '', '', ''],
                    $className
                )
            );
            try {
                $visitor->visitProperty(
                    new StaticPropertyMetadata('string', '@id', []),
                    $this->urlGenerator->generate(
                        'get_single_'.mb_strtolower($nodeSource->getNodeTypeName()),
                        [
                            'id' => $nodeSource->getNode()->getId()
                        ],
                        $this->referenceType
                    )
                );
            } catch (RouteNotFoundException $e) {
            }
        }
    }
}
