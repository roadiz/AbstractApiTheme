<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Subscriber;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class LinkedApiResponseSubscriber implements EventSubscriberInterface
{
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

        // <http://headless.test/docs.jsonld>; rel="http://www.w3.org/ns/hydra/core#apiDocumentation"
        $event->getResponse()->headers->set(
            'Link',
            'rel="http://www.w3.org/ns/hydra/core#apiDocumentation'
        );
    }
}
