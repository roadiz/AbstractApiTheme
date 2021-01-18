<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Controllers;

use JMS\Serializer\SerializationContext;
use RZ\Roadiz\Core\Entities\NodeType;
use Themes\AbstractApiTheme\AbstractApiThemeApp;

abstract class AbstractNodeTypeApiController extends AbstractApiThemeApp
{
    abstract protected function getSerializationGroups(): array;

    abstract protected function denyAccessUnlessNodeTypeGranted(NodeType $nodeType): void;

    /**
     * @param int $nodeTypeId
     * @return NodeType
     */
    protected function getNodeTypeOrDeny(int $nodeTypeId): NodeType
    {
        /** @var NodeType|null $nodeType */
        $nodeType = $this->get('em')->find(NodeType::class, $nodeTypeId);
        if (null === $nodeType) {
            throw $this->createNotFoundException();
        }
        $this->denyAccessUnlessNodeTypeGranted($nodeType);
        return $nodeType;
    }

    /**
     * @return SerializationContext
     */
    protected function getSerializationContext(): SerializationContext
    {
        $context = SerializationContext::create()
            ->setAttribute('translation', $this->getTranslation())
            ->enableMaxDepthChecks();
        if (count($this->getSerializationGroups()) > 0) {
            $context->setGroups($this->getSerializationGroups());
        }

        return $context;
    }
}
