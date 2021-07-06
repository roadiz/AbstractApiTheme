<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Routing;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Bags\Settings;
use RZ\Roadiz\Core\Entities\NodesSources;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use RZ\Roadiz\Core\Routing\PathResolverInterface;
use RZ\Roadiz\Core\Routing\ResourceInfo;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Themes\AbstractApiTheme\Subscriber\CachableApiResponseSubscriber;

/**
 * Special PathResolverInterface only to resolve root path for home page according to Accept-Language.
 *
 * Only if force_locale setting is true
 *
 * @package Themes\AbstractApiTheme\Routing
 */
final class RootPathResolver implements PathResolverInterface
{
    private RequestStack $requestStack;
    private EntityManagerInterface $entityManager;
    private Settings $settingsBag;

    /**
     * @param RequestStack $requestStack
     * @param EntityManagerInterface $entityManager
     * @param Settings $settingsBag
     */
    public function __construct(RequestStack $requestStack, EntityManagerInterface $entityManager, Settings $settingsBag)
    {
        $this->requestStack = $requestStack;
        $this->settingsBag = $settingsBag;
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     */
    public function resolvePath(
        string $path,
        array $supportedFormatExtensions = ['html'],
        bool $allowRootPaths = false
    ): ResourceInfo {
        $request = $this->requestStack->getMasterRequest();

        if (!$this->settingsBag->getBoolean('force_locale') ||
            null === $request ||
            $path !== '/' ||
            $allowRootPaths === false
        ) {
            throw new ResourceNotFoundException();
        }
        /** @var TranslationRepository $translationRepository */
        $translationRepository = $this->entityManager->getRepository(Translation::class);
        /*
         * If no _locale query param is defined check Accept-Language header
         */
        $locale = $request->getPreferredLanguage(
            // Available locales should be sort for default locale to be in first position.
            $translationRepository->getAvailableLocales()
        );
        $request->attributes->set(CachableApiResponseSubscriber::VARY_ON_ACCEPT_LANGUAGE_ATTRIBUTE, true);
        /*
         * Then fallback to default CMS locale
         */
        if (null === $locale) {
            /** @var Translation|null $translation */
            $translation = $translationRepository->findDefault();
        } else {
            /** @var Translation|null $translation */
            $translation = $translationRepository->findOneAvailableByLocaleOrOverrideLocale($locale);
        }
        if (null === $translation) {
            throw new ResourceNotFoundException('No translation for locale ' . $locale);
        }

        $request->attributes->set(CachableApiResponseSubscriber::CONTENT_LANGUAGE_ATTRIBUTE, $translation->getLocale());

        /** @var NodesSources|null $nodeSource */
        $nodeSource = $this->entityManager->getRepository(NodesSources::class)->findOneBy([
            'node.home' => true,
            'translation' => $translation
        ]);

        if (null === $nodeSource) {
            throw new ResourceNotFoundException('No home source for locale ' . $locale);
        }

        $resource = new ResourceInfo();
        $resource->setLocale($translation->getLocale());
        $resource->setTranslation($translation);
        $resource->setResource($nodeSource);

        return $resource;
    }
}
