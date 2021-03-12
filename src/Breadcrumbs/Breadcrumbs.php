<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Breadcrumbs;

use JMS\Serializer\Annotation as JMS;
use RZ\Roadiz\Core\Entities\NodesSources;

final class Breadcrumbs
{
    /**
     * @var array<NodesSources>
     * @JMS\Groups({"breadcrumbs"})
     * @JMS\Type("array<RZ\Roadiz\Core\Entities\NodesSources>")
     * @JMS\MaxDepth(1)
     * @JMS\SkipWhenEmpty
     */
    private array $items;

    /**
     * @param array $items
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
