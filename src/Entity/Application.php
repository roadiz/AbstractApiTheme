<?php
/**
 * AbstractApiTheme - Application.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-01-03
 */
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as Serializer;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use Ramsey\Uuid\Uuid;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use RZ\Roadiz\Core\Entities\Role;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * An AbstractEntity with datetime fields to keep track of time with your items.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="api_applications", indexes={
 *     @ORM\Index(columns={"api_key"}),
 *     @ORM\Index(columns={"enabled"}),
 *     @ORM\Index(columns={"confidential"}),
 *     @ORM\Index(columns={"created_at"}),
 *     @ORM\Index(columns={"updated_at"})
 * })
 */
class Application extends AbstractDateTimed implements UserInterface, AdvancedUserInterface, ClientEntityInterface
{
    /**
     * @var string
     * @ORM\Column(type="string", name="app_name", nullable=false, unique=true)
     * @Serializer\Groups({"user"})
     */
    private $appName = '';

    /**
     * @var string
     * @ORM\Column(type="string", name="namespace", nullable=false, unique=false)
     */
    private $namespace = '';

    /**
     * @var bool
     * @ORM\Column(type="boolean", nullable=false)
     * @Serializer\Groups({"user"})
     */
    private $enabled = true;

    /**
     * @var string
     * @ORM\Column(type="string", name="api_key", nullable=false, unique=true)
     * @Serializer\Groups({"secret"})
     */
    private $apiKey = '';

    /**
     * @var string|null
     * @ORM\Column(type="string", name="secret", nullable=true, unique=false)
     * @Serializer\Groups({"secret"})
     */
    private $secret = '';

    /**
     * @var array<string>
     * @ORM\Column(type="json", name="roles")
     * @Serializer\Groups({"user"})
     */
    private $roles = [];

    /**
     * @var string|null
     * @ORM\Column(type="string", name="referer_regex", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $refererRegex;

    /**
     * @var string|null
     * @ORM\Column(type="string", name="redirect_url", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $redirectUrl;

    /**
     * @var bool|null
     * @ORM\Column(type="boolean", name="confidential", nullable=true)
     * @Serializer\Groups({"user"})
     */
    private $confidential;

    /**
     * @param string $baseRole
     * @param string $namespace Namespace to generate an API key with UUID5 instead of UUID4.
     */
    public function __construct(string $baseRole, string $namespace = '')
    {
        $this->namespace = $namespace;
        $this->roles = [$baseRole];
        $this->confidential = false;
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * Re generate a new api-key for current application.
     */
    public function regenerateApiKey(): void
    {
        $uuid = Uuid::uuid4();
        $this->apiKey = $uuid->toString();
    }

    /**
     * @return string
     */
    public function getAppName(): string
    {
        return $this->appName;
    }

    /**
     * Get the client's identifier.
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->getApiKey();
    }

    /**
     * Get the client's name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->getAppName();
    }

    /**
     * @param string|null $redirectUrl
     * @return Application
     */
    public function setRedirectUrl(?string $redirectUrl): Application
    {
        $this->redirectUrl = $redirectUrl;
        return $this;
    }

    /**
     * Returns the registered redirect URI (as a string).
     *
     * Alternatively return an indexed array of redirect URIs.
     *
     * @return string|string[]|null
     */
    public function getRedirectUri()
    {
        return $this->redirectUrl;
    }

    /**
     * Returns true if the client is confidential.
     *
     * @return bool
     */
    public function isConfidential()
    {
        return $this->confidential ?? false;
    }

    /**
     * @param bool $confidential
     * @return $this
     */
    public function setConfidential(bool $confidential): Application
    {
        $this->confidential = $confidential;

        return $this;
    }

    /**
     * @param string|null $secret
     * @return Application
     */
    public function setSecret(?string $secret): Application
    {
        $this->secret = $secret;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSecret(): ?string
    {
        return $this->secret;
    }

    /**
     * @param string $appName
     *
     * @return Application
     */
    public function setAppName(string $appName): Application
    {
        $this->appName = $appName;

        return $this;
    }

    /**
     * @param array $roles
     * @return Application
     */
    public function setRoles(array $roles): Application
    {
        $this->roles = $roles;
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @inheritDoc
     */
    public function getPassword()
    {
        return $this->getApiKey();
    }

    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        return $this->namespace;
    }

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        return $this->getAppName();
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
        return;
    }

    /**
     * @inheritDoc
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * @inheritDoc
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return Application
     */
    public function setEnabled(bool $enabled): Application
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isAllowedPreview(): bool
    {
        return in_array(Role::ROLE_BACKEND_USER, $this->roles);
    }

    /**
     * @param bool $allowedPreview
     *
     * @return $this
     */
    public function setAllowedPreview(bool $allowedPreview)
    {
        if ($allowedPreview) {
            $this->roles[] = Role::ROLE_BACKEND_USER;
        } elseif (($key = array_search(Role::ROLE_BACKEND_USER, $this->roles)) !== false) {
            unset($this->roles[$key]);
        }
        $this->roles = array_filter(array_unique($this->roles));
        return $this;
    }

    /**
     * @return string
     */
    public function getRefererRegex(): ?string
    {
        return $this->refererRegex;
    }

    /**
     * @param string|null $refererRegex
     *
     * @return Application
     */
    public function setRefererRegex(?string $refererRegex): Application
    {
        $this->refererRegex = $refererRegex;

        return $this;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        parent::prePersist();

        $this->regenerateApiKey();
        $this->regenerateSecret();
    }

    public function regenerateSecret()
    {
        $this->setSecret(hash('sha512', random_bytes(32)));
    }
}
