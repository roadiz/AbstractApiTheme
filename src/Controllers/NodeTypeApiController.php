<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Themes\AbstractApiTheme\AbstractApiThemeApp;
use Themes\AbstractApiTheme\OptionsResolver\ApiRequestOptionsResolver;

class NodeTypeApiController extends AbstractApiThemeApp
{
    protected function getListingSerializationGroups(): array
    {
        return [
            'nodes_sources_base',
            'tag_base',
            'nodes_sources_default',
            'document_display',
            'thumbnail',
            'document_thumbnails',
            'urls',
            'meta',
        ];
    }

    /**
     * @return SerializationContext
     */
    protected function getListingSerializationContext(): SerializationContext
    {
        $context = SerializationContext::create()
            ->setAttribute('translation', $this->getTranslation())
            ->enableMaxDepthChecks();
        if (count($this->getListingSerializationGroups()) > 0) {
            $context->setGroups($this->getListingSerializationGroups());
        }

        return $context;
    }

    protected function getListingChildrenSerializationGroups(): array
    {
        return [
            'nodes_sources_base',
            'nodes_source_children',
            'document_display',
            'thumbnail',
            'document_thumbnails',
            'tag_base',
            'nodes_sources_default',
            'urls',
            'meta',
        ];
    }

    protected function getDetailSerializationGroups(): array
    {
        return [
            'walker', // rezozero tree-walker
            'children', // rezozero tree-walker
            'nodes_sources',
            'document_display',
            'thumbnail',
            'document_thumbnails',
            'tag_base',
            'urls',
        ];
    }

    /**
     * @return SerializationContext
     */
    protected function getDetailSerializationContext(): SerializationContext
    {
        $context = $this->getListingSerializationContext();
        if (count($this->getDetailSerializationGroups()) > 0) {
            $context->setGroups($this->getDetailSerializationGroups());
        }

        return $context;
    }

    /**
     * @param Request $request
     * @param int     $nodeTypeId
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function getListingAction(Request $request, int $nodeTypeId)
    {
        /** @var NodeType|null $nodeType */
        $nodeType = $this->get('em')->find(NodeType::class, $nodeTypeId);
        if (null === $nodeType) {
            throw $this->createNotFoundException();
        }
        /** @var ApiRequestOptionsResolver $apiOptionsResolver */
        $apiOptionsResolver = $this->get(ApiRequestOptionsResolver::class);
        $options = $apiOptionsResolver->resolve($request->query->all(), $nodeType);

        /** @var Translation|null $translation */
        $translation = $this->get('em')->getRepository(Translation::class)->findOneByLocale($options['_locale']);
        if (null === $translation) {
            throw $this->createNotFoundException();
        }

        $defaultCriteria = [
            'translation' => $translation,
        ];
        if ($nodeType->isPublishable()) {
            $defaultCriteria['publishedAt'] = ['<=', new \DateTime()];
        }

        $criteria = array_merge(
            $defaultCriteria,
            $apiOptionsResolver->getCriteriaFromOptions($options)
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
        $response = new JsonResponse(
            $serializer->serialize(
                $entityListManager,
                'json',
                $this->getListingSerializationContext()
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );

        /** @var int $cacheTtl */
        $cacheTtl = $this->get('api.cache.ttl');
        if ($cacheTtl > 0) {
            $this->makeResponseCachable($request, $response, $cacheTtl);
        }

        return $response;
    }

    /**
     * @param Request $request
     * @param int     $nodeTypeId
     * @param int     $id
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function getDetailAction(Request $request, int $nodeTypeId, int $id)
    {
        /** @var NodeType|null $nodeType */
        $nodeType = $this->get('em')->find(NodeType::class, $nodeTypeId);
        /** @var ApiRequestOptionsResolver $apiOptionsResolver */
        $apiOptionsResolver = $this->get(ApiRequestOptionsResolver::class);
        $options = $apiOptionsResolver->resolve($request->query->all(), $nodeType);

        if (null === $nodeType) {
            throw $this->createNotFoundException();
        }

        /** @var Translation|null $translation */
        $translation = $this->get('em')->getRepository(Translation::class)->findOneByLocale($options['_locale']);
        if (null === $translation) {
            throw $this->createNotFoundException();
        }

        $criteria = [
            'node.nodeType' => $nodeType,
            'node.id' => $id,
            'translation' => $translation,
        ];

        if ($nodeType->isPublishable()) {
            $criteria['publishedAt'] = ['<=', new \DateTime()];
        }

        $nodeSource = $this->get('nodeSourceApi')->getOneBy($criteria);

        if (null === $nodeSource) {
            throw $this->createNotFoundException();
        }

        /** @var SerializerInterface $serializer */
        $serializer = $this->get('serializer');
        $response = new JsonResponse(
            $serializer->serialize(
                $nodeSource,
                'json',
                $this->getDetailSerializationContext()
            ),
            JsonResponse::HTTP_OK,
            [],
            true
        );

        /** @var int $cacheTtl */
        $cacheTtl = $this->get('api.cache.ttl');
        if ($cacheTtl > 0) {
            $this->makeResponseCachable($request, $response, $cacheTtl);
        }

        return $response;
    }
}
