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
use Ramsey\Uuid\Uuid;
use RZ\Roadiz\Core\AbstractEntities\AbstractDateTimed;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * An AbstractEntity with datetime fields to keep track of time with your items.
 *
 * @ORM\Entity(repositoryClass="RZ\Roadiz\Core\Repositories\EntityRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="api_applications", indexes={
 *     @ORM\Index(columns={"api_key"}),
 *     @ORM\Index(columns={"created_at"}),
 *     @ORM\Index(columns={"updated_at"})
 * })
 */
class Application extends AbstractDateTimed implements UserInterface, AdvancedUserInterface
{
    /**
     * @var string
     * @ORM\Column(type="string", name="app_name", nullable=false, unique=true)
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
     */
    private $enabled = true;

    /**
     * @var string
     * @ORM\Column(type="string", name="api_key", nullable=false, unique=true)
     */
    private $apiKey = '';

    /**
     * @var array
     * @ORM\Column(type="simple_array", name="roles")
     */
    private $roles = [];

    /**
     * @var string
     * @ORM\Column(type="string", name="referer_regex", nullable=true)
     */
    private $refererRegex;

    /**
     * @param string $baseRole
     * @param string $namespace Namespace to generate an API key with UUID5 instead of UUID4.
     */
    public function __construct(string $baseRole, string $namespace = '')
    {
        $this->namespace = $namespace;
        $this->roles = [$baseRole];
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
     * @inheritDoc
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
     * @return string
     */
    public function getRefererRegex(): ?string
    {
        return $this->refererRegex;
    }

    /**
     * @param string $refererRegex
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
    }
}