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
