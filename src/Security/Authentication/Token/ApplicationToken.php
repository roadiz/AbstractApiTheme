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
     * @var string
     */
    protected $referer = '';

    /**
     * @inheritDoc
     */
    public function getCredentials()
    {
        $user = $this->getUser();
        if ($user instanceof Application) {
            return $user->getApiKey();
        }
        return false;
    }

    /**
     * @return string
     */
    public function getReferer(): string
    {
        return $this->referer;
    }

    /**
     * @param string $referer
     *
     * @return ApplicationToken
     */
    public function setReferer(string $referer): ApplicationToken
    {
        $this->referer = $referer;

        return $this;
    }
}