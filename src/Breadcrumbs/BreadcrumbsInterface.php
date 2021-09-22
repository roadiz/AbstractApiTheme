<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Breadcrumbs;

interface BreadcrumbsInterface
{
    public function getItems(): array;
}
