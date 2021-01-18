<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use Doctrine\ORM\Tools\Pagination\Paginator;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use JMS\Serializer\Visitor\SerializationVisitorInterface;
use RZ\Roadiz\Core\ListManagers\EntityListManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

final class EntityListManagerSubscriber implements EventSubscriberInterface
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @param RequestStack $requestStack
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents()
    {
        return [[
            'event' => 'serializer.post_serialize',
            'method' => 'onPostSerialize',
        ]];
    }

    /**
     * @param ObjectEvent $event
     * @throws \Exception
     * @return void
     */
    public function onPostSerialize(ObjectEvent $event)
    {
        $entityListManager = $event->getObject();
        $visitor = $event->getVisitor();

        if ($visitor instanceof SerializationVisitorInterface &&
                $entityListManager instanceof EntityListManagerInterface) {
            $entities = $entityListManager->getEntities();
            $request = $this->requestStack->getCurrentRequest();
            if (null === $request) {
                return;
            }
            if ($entities instanceof Paginator) {
                $visitor->visitProperty(
                    new StaticPropertyMetadata('Iterator', 'hydra:member', []),
                    $entities->getIterator()
                );
            } elseif (is_array($entities)) {
                $visitor->visitProperty(
                    new StaticPropertyMetadata('array', 'hydra:member', []),
                    $entities
                );
            }

            $visitor->visitProperty(
                new StaticPropertyMetadata('int', 'hydra:totalItems', []),
                $entityListManager->getItemCount()
            );
            $visitor->visitProperty(
                new StaticPropertyMetadata('string', '@id', []),
                $request->getPathInfo()
            );
            $visitor->visitProperty(
                new StaticPropertyMetadata('string', '@type', []),
                'hydra:Collection'
            );

            $assignation = $entityListManager->getAssignation();
            $view = [
                '@id' => $request->getPathInfo() . ($request->query->count() > 0 ? '?' : '') . http_build_query($request->query->all()),
                '@type' => 'hydra:PartialCollectionView'
            ];
            if (isset($assignation['nextPageQuery'])) {
                $view['hydra:next'] = $request->getPathInfo() . '?' . $assignation['nextPageQuery'];
            }
            if (isset($assignation['previousPageQuery'])) {
                $view['hydra:previous'] = $request->getPathInfo() . '?' . $assignation['previousPageQuery'];
            }
            if (isset($assignation['firstPageQuery'])) {
                $view['hydra:first'] = $request->getPathInfo() . '?' . $assignation['firstPageQuery'];
            }
            if (isset($assignation['lastPageQuery'])) {
                $view['hydra:last'] = $request->getPathInfo() . '?' . $assignation['lastPageQuery'];
            }
            $visitor->visitProperty(
                new StaticPropertyMetadata('array', 'hydra:view', []),
                $view
            );
        }
    }
}
