<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Model;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\ScopeTrait;

final class Scope implements ScopeEntityInterface
{
    use ScopeTrait;
    use EntityTrait;

    /**
     * @param string $identifier
     */
    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->getIdentifier();
    }
}
