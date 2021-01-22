<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CachableApiResponseSubscriber implements EventSubscriberInterface
{
    /**
     * @var bool
     */
    private $cachable = false;
    /**
     * @var int
     */
    private $minutes = 0;
    /**
     * @var bool
     */
    private $allowClientCache;

    /**
     * @param int $minutes
     * @param bool $cachable
     * @param bool $allowClientCache
     */
    public function __construct(int $minutes, bool $cachable = true, bool $allowClientCache = false)
    {
        $this->minutes = $minutes;
        $this->cachable = $cachable;
        $this->allowClientCache = $allowClientCache;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -1001],
        ];
    }

    /**
     * @param ResponseEvent $event
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        header_remove('Vary');
        header_remove('vary');
        $response = $event->getResponse();
        $response->headers->remove('vary');
        $response->setVary(implode(', ', [
            'Accept-Encoding',
            'Accept',
            'Authorization',
            'x-requested-with',
            'Access-Control-Allow-Origin',
            'x-api-key',
            'Referer',
            'Origin'
        ]));

        if ($event->getRequest()->isXmlHttpRequest()) {
            $response->headers->add([
                'X-Partial' => true
            ]);
        }

        /*
         * Following directives only apply on cacheable responses.
         */
        if ($this->cachable === false || $this->minutes <= 0) {
            return;
        }

        header_remove('Cache-Control');
        $response->headers->remove('cache-control');
        $response->setPublic();
        $response->setSharedMaxAge(60 * $this->minutes);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        if ($this->allowClientCache) {
            $response->setMaxAge(60 * $this->minutes);
        }
    }
}
