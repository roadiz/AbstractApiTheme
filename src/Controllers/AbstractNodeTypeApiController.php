<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializationContext;
use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
use RZ\Roadiz\Core\Entities\NodeType;
use RZ\Roadiz\Core\Entities\Translation;
use Themes\AbstractApiTheme\AbstractApiThemeApp;
use Themes\AbstractApiTheme\Serialization\SerializationContextFactoryInterface;

abstract class AbstractNodeTypeApiController extends AbstractApiThemeApp
{
    abstract protected function getSerializationGroups(): array;

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
     * @return SerializationContext
     */
    protected function getSerializationContext(): SerializationContext
    {
        $context = $this->get(SerializationContextFactoryInterface::class)->create()
            ->setAttribute('translation', $this->getTranslation());
        if (count($this->getSerializationGroups()) > 0) {
            $context->setGroups($this->getSerializationGroups());
        }

        return $context;
    }
}
