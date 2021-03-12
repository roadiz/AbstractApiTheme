<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Breadcrumbs;

use JMS\Serializer\Annotation as Serializer;
use RZ\Roadiz\Core\Entities\NodesSources;

final class Breadcrumbs
{
    /**
     * @var array<NodesSources>
     * @Serializer\Groups({"breadcrumbs"})
     * @Serializer\Type("array<RZ\Roadiz\Core\Entities\NodesSources>")
     * @Serializer\MaxDepth(1)
     * @Serializer\SkipWhenEmpty
     */
    private array $items;

    /**
     * @param array<NodesSources> $items
     */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * @return NodesSources[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
