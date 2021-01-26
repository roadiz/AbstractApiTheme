<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization;

use JMS\Serializer\SerializationContext;
use Themes\AbstractApiTheme\Cache\CacheTagsCollection;

final class SerializationContextFactory implements SerializationContextFactoryInterface
{
    private bool $useCacheTags = false;

    /**
     * @param bool $useCacheTags
     */
    public function __construct(bool $useCacheTags)
    {
        $this->useCacheTags = $useCacheTags;
    }

    /**
     * @return SerializationContext
     */
    public function create(): SerializationContext
    {
        $context = SerializationContext::create()
            ->enableMaxDepthChecks();
        if ($this->useCacheTags) {
            $context->setAttribute('cache-tags', new CacheTagsCollection());
        }
        return $context;
    }
}