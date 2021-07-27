<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Routing;

use Doctrine\Persistence\ManagerRegistry;
use RZ\Roadiz\Core\Entities\Redirection;
use RZ\Roadiz\Core\Routing\PathResolverInterface;
use RZ\Roadiz\Core\Routing\ResourceInfo;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

final class RedirectionPathResolver implements PathResolverInterface
{
    private ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     */
    public function resolvePath(
        string $path,
        array $supportedFormatExtensions = ['html'],
        bool $allowRootPaths = false
    ): ResourceInfo {
        /** @var Redirection|null $redirection */
        $redirection = $this->managerRegistry->getRepository(Redirection::class)->findOneByQuery($path);

        if (null === $redirection) {
            throw new ResourceNotFoundException('No redirection matches path');
        }

        return (new ResourceInfo())->setResource($redirection);
    }
}
