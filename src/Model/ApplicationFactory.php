<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Model;

use RZ\Roadiz\Core\AbstractEntities\PersistableInterface;

class ApplicationFactory
{
    /**
     * @var class-string
     */
    private string $applicationClass;
    private string $baseRole;
    private string $appNamespace;

    /**
     * @param class-string $applicationClass
     * @param string $baseRole
     * @param string $appNamespace
     */
    public function __construct(string $applicationClass, string $baseRole, string $appNamespace)
    {
        $this->applicationClass = $applicationClass;
        $this->baseRole = $baseRole;
        $this->appNamespace = $appNamespace;
    }

    /**
     * @return PersistableInterface
     */
    public function __invoke(): PersistableInterface
    {
        $class = $this->applicationClass;
        return new $class($this->baseRole, $this->appNamespace);
    }
}
