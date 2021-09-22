<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Breadcrumbs;

use RZ\Roadiz\Core\Entities\NodesSources;

interface BreadcrumbsFactoryInterface
{
    public function create(?NodesSources $nodesSources): ?BreadcrumbsInterface;
}
