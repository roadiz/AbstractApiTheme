<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Security\Authentication\Token;

use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Themes\AbstractApiTheme\Entity\Application;

class ApplicationToken extends AbstractToken
{
    /**
     * @var string
     * @Serializer\Groups({"user"})
     */
    protected string $referer = '';

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
