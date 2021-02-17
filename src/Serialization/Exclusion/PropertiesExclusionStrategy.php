<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Serialization\Exclusion;

use JMS\Serializer\Context;
use JMS\Serializer\Exclusion\ExclusionStrategyInterface;
use JMS\Serializer\Metadata\ClassMetadata;
use JMS\Serializer\Metadata\PropertyMetadata;

final class PropertiesExclusionStrategy implements ExclusionStrategyInterface
{
    private array $fields = [];
    private array $ignoredClasses = [];

    public function __construct(array $fields, array $ignoredClasses = [])
    {
        $this->fields = $fields;
        $this->ignoredClasses = $ignoredClasses;
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipClass(ClassMetadata $metadata, Context $context): bool
    {
        if (empty($this->ignoredClasses)) {
            return false;
        }
        return in_array($metadata->name, $this->ignoredClasses);
    }

    /**
     * {@inheritDoc}
     */
    public function shouldSkipProperty(PropertyMetadata $property, Context $context): bool
    {
        if (empty($this->fields)) {
            return false;
        }
        $name = $property->serializedName ?: $property->name;
        return !in_array($name, $this->fields);
    }
}
