<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OptionsResolver;

use RZ\Roadiz\Contracts\NodeType\NodeTypeInterface;

interface NodeTypeApiRequestOptionResolverInterface extends ApiRequestOptionResolverInterface
{
    public function resolve(array $params, ?NodeTypeInterface $nodeType): array;
}
