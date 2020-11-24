<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Entity;

use RZ\Roadiz\Core\AbstractEntities\AbstractEntity;

class RefreshToken extends AbstractEntity
{
    /**
     * @var string
     */
    private $identifier;

    /**
     * @var \DateTimeInterface
     */
    private $expiry;

    /**
     * @var bool
     */
    private $revoked = false;
}
