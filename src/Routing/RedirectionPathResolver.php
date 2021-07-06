<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Routing;

use Doctrine\ORM\EntityManagerInterface;
use RZ\Roadiz\Core\Entities\Redirection;
use RZ\Roadiz\Core\Routing\PathResolverInterface;
use RZ\Roadiz\Core\Routing\ResourceInfo;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

final class RedirectionPathResolver implements PathResolverInterface
{
    private EntityManagerInterface $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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
        $redirection = $this->entityManager->getRepository(Redirection::class)->findOneByQuery($path);

        if (null === $redirection) {
            throw new ResourceNotFoundException('No redirection matches path');
        }

        return (new ResourceInfo())->setResource($redirection);
    }
}
