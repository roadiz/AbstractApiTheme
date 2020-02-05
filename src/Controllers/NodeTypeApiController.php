<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\src\Controllers;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Themes\AbstractApiTheme\AbstractApiThemeApp;

class NodeTypeApiController extends AbstractApiThemeApp
{
    /**
     * @return array
     */
    protected function getMetaOptions(): array
    {
        return [
            'itemsPerPage' => 15,
            'page' => 1,
            '_locale' => $this->get('defaultTranslation')->getLocale(),
            'search' => null,
            'api_key' => null,
            'order' => null,
        ];
    }
    /**
     * @param array $options
     *
     * @return array
     */
    protected function resolveOptions(array $options): array
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(array_merge($this->getMetaOptions(), [
            'title' => null,
        ]));
        $resolver->setAllowedTypes('search', ['string', 'null']);
        $resolver->setAllowedTypes('title', ['string', 'null']);
        $resolver->setAllowedTypes('api_key', ['string', 'null']);
        $resolver->setAllowedTypes('order', ['array', 'null']);

        return $resolver->resolve($options);
    }

    /**
     * @param array $options
     *
     * @return array
     */
    protected function normalizeQueryParams(array $options): array
    {
        foreach ($options as $key => $value) {
            if ($key === 'node_parent') {
                $options['node.parent'] = $value;
                unset($options['node_parent']);
            } elseif ($key === 'node_visible') {
                $options['node.visible'] = $value;
                unset($options['node_visible']);
            } elseif ($key === 'node_nodeType_reachable') {
                $options['node.nodeType.reachable'] = $value;
                unset($options['node_nodeType_reachable']);
            }
        }
        return $options;
    }

    /**
     * @param Request $request
     * @param int     $nodeTypeId
     *
     * @return JsonResponse
     */
    public function getListingAction(Request $request, int $nodeTypeId)
    {
        /** @var NodeType|null $nodeType */
        $nodeType = $this->get('em')->find(NodeType::class, $nodeTypeId);
        $options = $this->resolveOptions($this->normalizeQueryParams($request->query->all()));

        /** @var Translation|null $translation */
        $translation = $this->get('em')->getRepository(Translation::class)->findOneByLocale($options['_locale']);
        if (null === $translation) {
            throw $this->createNotFoundException();
        }

        if (null === $nodeType) {
            throw $this->createNotFoundException();
        }
        $criteria = array_merge(
            [
                'translation' => $translation
            ],
            array_filter(array_filter($options), function ($key) {
                return !array_key_exists($key, $this->getMetaOptions());
            }, ARRAY_FILTER_USE_KEY)
        );

        $entityListManager = $this->createEntityListManager(
            $nodeType->getSourceEntityFullQualifiedClassName(),
            $criteria,
            null !== $options['order'] ? $options['order'] : []
        );
        $entityListManager->setItemPerPage($options['itemsPerPage']);
        $entityListManager->setPage($options['page']);
        $entityListManager->handle();

        /** @var SerializerInterface $serializer */
        $serializer = $this->get('serializer');
        return new JsonResponse(
            $serializer->serialize(
                $entityListManager,
                'json',
                SerializationContext::create()->setGroups(['nodes_sources_base', 'nodes_sources_default', 'urls', 'meta'])
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }

    /**
     * @param Request $request
     * @param int     $nodeTypeId
     * @param int     $id
     *
     * @return JsonResponse
     */
    public function getDetailAction(Request $request, int $nodeTypeId, int $id)
    {
        /** @var NodeType|null $nodeType */
        $nodeType = $this->get('em')->find(NodeType::class, $nodeTypeId);
        $options = $this->resolveOptions($this->normalizeQueryParams($request->query->all()));

        if (null === $nodeType) {
            throw $this->createNotFoundException();
        }

        /** @var Translation|null $translation */
        $translation = $this->get('em')->getRepository(Translation::class)->findOneByLocale($options['_locale']);
        if (null === $translation) {
            throw $this->createNotFoundException();
        }

        $nodeSource = $this->get('nodeSourceApi')->getOneBy([
            'node.nodeType' => $nodeType,
            'node.id' => $id,
            'translation' => $translation
        ]);

        if (null === $nodeSource) {
            throw $this->createNotFoundException();
        }

        /** @var SerializerInterface $serializer */
        $serializer = $this->get('serializer');
        return new JsonResponse(
            $serializer->serialize(
                $nodeSource,
                'json',
                SerializationContext::create()->setGroups(['nodes_sources', 'urls'])
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );
    }
}
