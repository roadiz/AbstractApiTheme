<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializationContext;
use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Themes\AbstractApiTheme\AbstractApiThemeApp;
use Themes\AbstractApiTheme\Serialization\Exclusion\PropertiesExclusionStrategy;
use Themes\AbstractApiTheme\Serialization\SerializationContextFactoryInterface;
use Themes\AbstractApiTheme\Subscriber\LinkedApiResponseSubscriber;

abstract class AbstractNodeTypeApiController extends AbstractApiThemeApp
{
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
     * @param Request $request
     * @param mixed|null $resource
     */
    protected function injectAlternateHrefLangLinks(Request $request, $resource = null): void
    {
        if ($request->attributes->has('_route')) {
            $availableLocales = $this->get('em')->getRepository(Translation::class)->getAvailableLocales();
            if (count($availableLocales) > 1 && !$request->query->has('path')) {
                $links = [];
                foreach ($availableLocales as $availableLocale) {
                    $linksParams = [
                        sprintf('<%s>', $this->generateUrl(
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
}
