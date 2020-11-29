<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Model;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;

final class AccessToken implements AccessTokenEntityInterface
{
    use AccessTokenTrait;
    use EntityTrait;
    use TokenEntityTrait;

    /**
     * Initialise the JWT Configuration.
     * @see https://github.com/thephpleague/oauth2-server/issues/1163
     */
    public function initJwtConfiguration()
    {
        $privateKeyPassPhrase = $this->privateKey->getPassPhrase();

        $verificationKey = empty($privateKeyPassPhrase) ?
            InMemory::plainText('') :
            InMemory::plainText($this->privateKey->getPassPhrase());

        $this->jwtConfiguration = Configuration::forAsymmetricSigner(
            new Sha256(),
            // Need to pass passphrase too!
            LocalFileReference::file($this->privateKey->getKeyPath(), $this->privateKey->getPassPhrase()),
            $verificationKey
        );
    }
}
