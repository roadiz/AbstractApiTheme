<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use RZ\Roadiz\Core\Repositories\TranslationRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Themes\AbstractApiTheme\AbstractApiThemeApp;
use Themes\AbstractApiTheme\Cache\CacheTagsCollection;
use Themes\AbstractApiTheme\Serialization\Exclusion\PropertiesExclusionStrategy;
use Themes\AbstractApiTheme\Serialization\SerializationContextFactoryInterface;

abstract class AbstractNodeTypeApiController extends AbstractApiThemeApp
{
    use LocalizedController;
    protected array $serializationGroups;

    /**
     * @param array|null $serializationGroups
     */
    public function __construct(?array $serializationGroups = null)
    {
        if (null !== $serializationGroups) {
            $this->serializationGroups = $serializationGroups;
        } else {
            $this->serializationGroups = $this->getDefaultSerializationGroups();
        }
    }

    protected function getSerializationGroups(): array
    {
        return $this->serializationGroups;
    }

    protected function getDefaultSerializationGroups(): array
    {
        return [
            'nodes_sources_base',
            'document_display',
            'thumbnail',
            'tag_base',
            'nodes_sources_default',
            'urls',
            'meta',
        ];
    }

    abstract protected function denyAccessUnlessNodeTypeGranted(NodeTypeInterface $nodeType): void;

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->get('em');
    }

    /**
     * @return TranslationRepository
     */
    protected function getTranslationRepository(): TranslationRepository
    {
        /** @type TranslationRepository  */
        return $this->getEntityManager()->getRepository(Translation::class);
    }

    /**
     * @return UrlGeneratorInterface
     */
    protected function getUrlGenerator(): UrlGeneratorInterface
    {
        return $this->get('urlGenerator');
    }

    /**
     * @param int $nodeTypeId
     * @return NodeTypeInterface
     */
    protected function getNodeTypeOrDeny(int $nodeTypeId): NodeTypeInterface
    {
        /** @var NodeTypeInterface|null $nodeType */
        $nodeType = $this->get('em')->find(NodeType::class, $nodeTypeId);
        if (null === $nodeType) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessNodeTypeGranted($nodeType);
        return $nodeType;
    }

    /**
     * @param string $locale
     * @return Translation
     */
    protected function getTranslationOrNotFound(string $locale): Translation
    {
        $this->translation = $this->get('em')->getRepository(Translation::class)->findOneByLocale($locale);
        if (null === $this->translation) {
            throw $this->createNotFoundException();
        }
        return $this->translation;
    }

    /**
     * @param array $options
     * @return SerializationContext
     */
    protected function getSerializationContext(array &$options = []): SerializationContext
    {
        /** @var SerializationContext $context */
        $context = $this->get(SerializationContextFactoryInterface::class)->create()
            ->enableMaxDepthChecks()
            ->setAttribute('translation', $this->getTranslation());
        if (count($this->getSerializationGroups()) > 0) {
            $context->setGroups($this->getSerializationGroups());
        }
        if (isset($options['properties']) && count($options['properties']) > 0) {
            $context->addExclusionStrategy(new PropertiesExclusionStrategy(
                $options['properties'],
                $this->get('api.exclusion_strategy.skip_classes')
            ));
        }

        return $context;
    }

    /**
     * @param string $jsonData
     * @param SerializationContext $context
     * @param Request $request
     * @param int $ttl
     * @return Response
     */
    protected function getJsonResponse(
        string $jsonData,
        SerializationContext $context,
        Request $request,
        int $ttl = 0
    ): Response {
        $response = new JsonResponse(
            $jsonData,
            JsonResponse::HTTP_OK,
            [],
            true
        );

        if ($context->hasAttribute('cache-tags') &&
            $context->getAttribute('cache-tags') instanceof CacheTagsCollection) {
            /** @var CacheTagsCollection $cacheTags */
            $cacheTags = $context->getAttribute('cache-tags');
            if ($cacheTags->count() > 0) {
                $response->headers->add([
                    'X-Cache-Tags' => implode(', ', $cacheTags->toArray())
                ]);
            }
        }

        $this->injectAlternateHrefLangLinks($request);
        $response->setEtag(md5($response->getContent() ?: ''));
        /*
         * Returns a 304 if request Etag matches response's
         */
        if ($response->isNotModified($request)) {
            return $response;
        }

        return $this->makeResponseCachable($request, $response, $ttl);
    }
}
