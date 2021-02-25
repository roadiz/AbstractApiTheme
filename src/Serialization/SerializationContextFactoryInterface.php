<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\SerializationContext;

interface SerializationContextFactoryInterface
{
    public function create(): SerializationContext;
}
