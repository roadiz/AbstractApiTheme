<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OAuth2;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use RZ\Roadiz\JWT\JwtConfigurationFactory;

final class OAuth2JwtConfigurationFactory implements JwtConfigurationFactory
{
    /**
     * @var string
     */
    private $privateKeyPath;
    /**
     * @var string
     */
    private $publicKeyPath;
    /**
     * @var string|null
     */
    private $passphrase;

    /**
     * @param string $privateKeyPath
     * @param string $publicKeyPath
     * @param string|null $passphrase
     */
    public function __construct(string $privateKeyPath, string $publicKeyPath, ?string $passphrase)
    {
        $this->privateKeyPath = $privateKeyPath;
        $this->publicKeyPath = $publicKeyPath;
        $this->passphrase = $passphrase;
    }

    public function create(): Configuration
    {
        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            LocalFileReference::file($this->privateKeyPath, $this->passphrase ?? ''),
            LocalFileReference::file($this->publicKeyPath)
        );

        $configuration->setValidationConstraints(...[
            new LooseValidAt(SystemClock::fromSystemTimezone()),
            new Constraint\SignedWith($configuration->signer(), $configuration->signingKey())
        ]);
        return $configuration;
    }
}
