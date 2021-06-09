<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Themes\AbstractApiTheme\Subscriber\LinkedApiResponseSubscriber;

trait LocalizedController
{
    abstract protected function getUrlGenerator(): UrlGeneratorInterface;
    abstract protected function getEntityManager(): EntityManagerInterface;
    abstract protected function getTranslationRepository(): TranslationRepository;

    protected function getTranslationForLocale(string $locale): Translation
    {
        /** @var Translation|null $translation */
        $translation = $this->getTranslationRepository()->findOneBy([
            'locale' => $locale
        ]);
        if (null === $translation) {
            throw new NotFoundHttpException('No translation for locale ' . $locale);
        }
        return $translation;
    }

    /**
     * @param Request $request
     * @param NodesSources|mixed|null $resource
     */
    protected function injectAlternateHrefLangLinks(Request $request, $resource = null): void
    {
        if ($request->attributes->has('_route')) {
            $availableLocales = $this->getTranslationRepository()
                ->getAvailableLocales();
            if (count($availableLocales) > 1 && !$request->query->has('path')) {
                $links = [];
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

        if ($resource instanceof NodesSources &&
            null !== $resource->getNode() &&
            null !== $resource->getNode()->getNodeType() &&
            $resource->isReachable()) {
            $node = $resource->getNode();
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
