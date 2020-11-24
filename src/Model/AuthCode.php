<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Model;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\Traits\AuthCodeTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

/**
 * @package Themes\AbstractApiTheme\Model
 * @see https://github.com/trikoder/oauth2-bundle/blob/v3.x/League/Entity/AuthCode.php
 */
final class AuthCode implements AuthCodeEntityInterface
{
    use EntityTrait;
    use AuthCodeTrait;
    use TokenEntityTrait;
}
