<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\Entities\NodesSources;

final class ChildrenApiSubscriber implements EventSubscriberInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * ChildrenApiSubscriber constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
            $nodeSource instanceof NodesSources &&
            null !== $nodeSource->getNode() &&
            $context->hasAttribute('groups') &&
            $context->hasAttribute('childrenCriteria') &&
            $context->hasAttribute('maxChildrenCount') &&
            in_array('nodes_source_children', $context->getAttribute('groups'))) {
            $children = $this->entityManager
                ->getRepository(NodesSources::class)
                ->findBy(array_merge($context->getAttribute('childrenCriteria'), [
                    'node.parent' => $nodeSource->getNode()->getId()
                ]), [
                    'node.position' => 'ASC'
                ], (int) $context->getAttribute('maxChildrenCount'));
            $visitor->visitProperty(
                new StaticPropertyMetadata('array', 'children', []),
                $children
            );
        }
    }
}
