<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Subscriber;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class LinkedApiResponseSubscriber implements EventSubscriberInterface
{
    const LINKED_RESOURCES_ATTRIBUTE = 'linked_resources';

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -1002],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($event->getResponse()->headers->has('Link')) {
            return;
        }

        $links = [
//            'rel="http://www.w3.org/ns/hydra/core#apiDocumentation"'
        ];
        if ($event->getRequest()->attributes->has(static::LINKED_RESOURCES_ATTRIBUTE)) {
            $links = array_merge($links, $event->getRequest()->attributes->get(static::LINKED_RESOURCES_ATTRIBUTE));
        }

        $event->getRequest()->attributes->remove(static::LINKED_RESOURCES_ATTRIBUTE);

        if (count($links) > 0) {
            // <http://headless.test/docs.jsonld>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"
            $event->getResponse()->headers->set(
                'Link',
                implode(', ', $links)
            );
        }
    }
}
