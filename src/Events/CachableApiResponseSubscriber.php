<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CachableApiResponseSubscriber implements EventSubscriberInterface
{
    /** @var bool  */
    private $cachable = false;
    /** @var int  */
    private $minutes = 0;

    /**
     * @param int  $minutes
     * @param bool $cachable
     */
    public function __construct(int $minutes, bool $cachable = true)
    {
        $this->minutes = $minutes;
        $this->cachable = $cachable;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -1001],
        ];
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }
        if ($this->cachable === false || $this->minutes <= 0) {
            return;
        }
        header_remove('Cache-Control');
        header_remove('Vary');
        $response = $event->getResponse();
        $response->headers->remove('cache-control');
        $response->headers->remove('vary');
        $response->setPublic();
        $response->setMaxAge(60 * $this->minutes);
        $response->setSharedMaxAge(60 * $this->minutes);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        $response->headers->add([
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET',
        ]);
        $response->setVary('Accept-Encoding, X-Partial, x-requested-with, Access-Control-Allow-Origin, x-api-key, Referer');

        if ($event->getRequest()->isXmlHttpRequest()) {
            $response->headers->add([
                'X-Partial' => true
            ]);
        }
    }
}
