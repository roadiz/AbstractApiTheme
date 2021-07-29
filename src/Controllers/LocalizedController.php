<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Themes\AbstractApiTheme\Subscriber\CachableApiResponseSubscriber;
use Themes\AbstractApiTheme\Subscriber\LinkedApiResponseSubscriber;

trait LocalizedController
{
    abstract protected function getUrlGenerator(): UrlGeneratorInterface;
    abstract protected function getEntityManager(): ObjectManager;
    abstract protected function getTranslationRepository(): TranslationRepository;
    abstract protected function getDefaultLocale(): string;

    protected function getTranslationFromLocaleOrRequest(Request $request, ?string $locale): Translation
    {
        /*
         * If no _locale query param is defined check Accept-Language header
         */
        if (null === $locale) {
            $locale = $request->getPreferredLanguage($this->getTranslationRepository()->getAllLocales());
            $request->attributes->set(CachableApiResponseSubscriber::VARY_ON_ACCEPT_LANGUAGE_ATTRIBUTE, true);
        }
        /*
         * Then fallback to default CMS locale
         */
        if (null === $locale) {
            $locale = $this->getDefaultLocale();
        }
        /** @var Translation|null $translation */
        $translation = $this->getTranslationRepository()->findOneAvailableByLocaleOrOverrideLocale($locale);
        if (null === $translation) {
            throw new NotFoundHttpException('No translation for locale ' . $locale);
        }
        $request->attributes->set(CachableApiResponseSubscriber::CONTENT_LANGUAGE_ATTRIBUTE, $translation->getLocale());
        return $translation;
    }

    /**
     * @param Request $request
     */
    protected function injectAlternateHrefLangLinks(Request $request): void
    {
        if ($request->attributes->has('_route')) {
            $availableLocales = $this->getTranslationRepository()
                ->getAvailableLocales();
            if (count($availableLocales) > 1 && !$request->query->has('path')) {
                /** @var array<string> $links */
                $links = $request->attributes->get(LinkedApiResponseSubscriber::LINKED_RESOURCES_ATTRIBUTE, []);
                foreach ($availableLocales as $availableLocale) {
                    $linksParams = [
                        sprintf('<%s>', $this->getUrlGenerator()->generate(
                            $request->attributes->get('_route'),
                            array_merge(
                                $request->query->all(),
                                $request->attributes->get('_route_params'),
                                [
                                    '_locale' => $availableLocale
                                ]
                            ),
                            UrlGeneratorInterface::ABSOLUTE_URL
                        )),
                        'rel="alternate"',
                        'hreflang="'.$availableLocale.'"',
                        'type="application/json"'
                    ];
                    $links[] = implode('; ', $linksParams);
                }
                $request->attributes->set(LinkedApiResponseSubscriber::LINKED_RESOURCES_ATTRIBUTE, $links);
            }
        }
    }

    /**
     * @param Request $request
     * @param NodesSources $nodeSource
     */
    protected function injectNodeSourceAlternatePaths(Request $request, NodesSources $nodeSource): void
    {
        if (null !== $nodeSource->getNode() &&
            null !== $nodeSource->getNode()->getNodeType() &&
            $nodeSource->isReachable()) {
            $node = $nodeSource->getNode();
            $this->getEntityManager()->refresh($node);
            /** @var array<string> $links */
            $links = $request->attributes->get(LinkedApiResponseSubscriber::LINKED_RESOURCES_ATTRIBUTE, []);
            /** @var NodesSources $nodeSource */
            foreach ($node->getNodeSources() as $nodeSource) {
                $linksParams = [
                    sprintf('<%s>', $this->getUrlGenerator()->generate(
                        RouteObjectInterface::OBJECT_BASED_ROUTE_NAME,
                        [
                            RouteObjectInterface::ROUTE_OBJECT => $nodeSource,
                            '_format' => 'html'
                        ],
                        UrlGeneratorInterface::ABSOLUTE_PATH
                    )),
                    'rel="alternate"',
                    'hreflang="'.$nodeSource->getTranslation()->getPreferredLocale().'"',
                    'type="text/html"'
                ];
                $links[] = implode('; ', $linksParams);
            }
            $request->attributes->set(LinkedApiResponseSubscriber::LINKED_RESOURCES_ATTRIBUTE, $links);
        }
    }
}
