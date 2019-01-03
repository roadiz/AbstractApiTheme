<?php
/**
 * AbstractApiTheme - ApplicationToken.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-01-03
 */
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Security\Authentication\Token;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Themes\AbstractApiTheme\Entity\Application;

class ApplicationToken extends AbstractToken
{
    /**
     * @var Application|null
     */
    private $application = null;

    /**
     * @inheritDoc
     */
    public function __construct(array $roles = [])
    {
        parent::__construct($roles);

        $this->setAuthenticated(sizeof($roles) > 0);
    }

    public function getCredentials()
    {
        return $this->application;
    }

    /**
     * @return Application|null
     */
    public function getApplication(): ?Application
    {
        return $this->application;
    }

    /**
     * @param Application|null $application
     *
     * @return ApplicationToken
     */
    public function setApplication(?Application $application): ApplicationToken
    {
        $this->application = $application;

        return $this;
    }
}