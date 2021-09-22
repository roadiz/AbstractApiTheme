<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OptionsResolver;

use Doctrine\Persistence\ObjectManager;
use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;
use RZ\Roadiz\Core\Entities\NodeType;

trait NodeTypeAwareOptionResolverTrait
{
    abstract protected function getEntityManager(): ObjectManager;

    /**
     * @param int|string|array<int|string> $nodeTypes
     * @return NodeTypeInterface|array<NodeTypeInterface>|null
     */
    protected function normalizeNodeTypes($nodeTypes)
    {
        if (is_array($nodeTypes)) {
            return array_values(array_filter(array_map([$this, 'normalizeSingleNodeType'], $nodeTypes)));
        } else {
            return $this->normalizeSingleNodeType($nodeTypes);
        }
    }

    /**
     * @param int|string $nodeType
     * @return NodeTypeInterface|null
     */
    protected function normalizeSingleNodeType($nodeType): ?NodeTypeInterface
    {
        if (is_integer($nodeType)) {
            return $this->getEntityManager()->find(NodeType::class, (int) $nodeType);
        } elseif (is_string($nodeType)) {
            return $this->getEntityManager()
                ->getRepository(NodeType::class)
                ->findOneByName($nodeType);
        }
        return null;
    }
}
