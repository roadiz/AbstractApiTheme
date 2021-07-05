<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CachableApiResponseSubscriber implements EventSubscriberInterface
{
    private bool $cachable = false;
    private int $minutes = 0;
    private bool $allowClientCache;

    const VARY_ON_ORIGIN_ATTRIBUTE='response.vary_on_origin_attr';
    const VARY_ON_ACCEPT_LANGUAGE_ATTRIBUTE='response.vary_on_accept_language_attr';
    const CONTENT_LANGUAGE_ATTRIBUTE='response.content_language_attr';

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
        $varyingHeaders = [
            'Accept-Encoding',
            'Accept',
            'Authorization',
            'x-api-key'
        ];
        if ($event->getRequest()->attributes->getBoolean(self::VARY_ON_ORIGIN_ATTRIBUTE)) {
            $varyingHeaders[] = 'Origin';
        }
        if ($event->getRequest()->attributes->getBoolean(self::VARY_ON_ACCEPT_LANGUAGE_ATTRIBUTE)) {
            $varyingHeaders[] = 'Accept-Language';
        }
        if ($event->getRequest()->attributes->has(self::CONTENT_LANGUAGE_ATTRIBUTE)) {
            $response->headers->set(
                'content-language',
                $event->getRequest()->attributes->getAlpha(self::CONTENT_LANGUAGE_ATTRIBUTE)
            );
        }
        $response->setVary(implode(', ', $varyingHeaders));

        /*
         * Following directives only apply on cacheable responses.
         */
        if ($this->cachable === false || $this->minutes <= 0) {
            return;
        }

        header_remove('Cache-Control');
        $response->headers->remove('cache-control');
        $response->setTtl(60 * $this->minutes);
        $response->headers->addCacheControlDirective('must-revalidate', true);
        /*
         * Allows cache to serve expired content max 30sec while fetching fresh from backend
         */
        $response->headers->addCacheControlDirective('stale-while-revalidate', 30);
        if ($this->allowClientCache) {
            $response->setMaxAge(60 * $this->minutes);
        }
    }
}
