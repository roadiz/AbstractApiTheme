<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Breadcrumbs;

use RZ\Roadiz\Core\Entities\NodesSources;

final class NaiveBreadcrumbsFactory implements BreadcrumbsFactoryInterface
{
    /**
     * @param NodesSources|null $nodesSources
     * @return Breadcrumbs|null
     */
    public function create(?NodesSources $nodesSources): ?Breadcrumbs
    {
        if (null === $nodesSources ||
            null === $nodesSources->getNode() ||
            null === $nodesSources->getNode()->getNodeType() ||
            !$nodesSources->isReachable()) {
            return null;
        }
        $parents = [];

        while (null !== $nodesSources = $nodesSources->getParent()) {
            if (null !== $nodesSources->getNode() &&
                $nodesSources->getNode()->isVisible()) {
                $parents[] = $nodesSources;
            }
        }

        return new Breadcrumbs(array_reverse($parents));
    }
}
