<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Routing;

use RZ\Roadiz\Core\Routing\ResourceInfo;
use RZ\Roadiz\Core\Routing\PathResolverInterface;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

final class ChainedPathResolver implements PathResolverInterface
{
    /**
     * @var array<PathResolverInterface>
     */
    private array $resolvers;

    /**
     * @param PathResolverInterface[] $resolvers
     */
    public function __construct(array $resolvers)
    {
        $this->resolvers = $resolvers;
    }

    /**
     * @inheritDoc
     */
    public function resolvePath(
        string $path,
        array $supportedFormatExtensions = ['html'],
        bool $allowRootPaths = false
    ): ResourceInfo {
        foreach ($this->resolvers as $pathResolver) {
            if ($pathResolver instanceof PathResolverInterface) {
                try {
                    return $pathResolver->resolvePath($path, $supportedFormatExtensions, $allowRootPaths);
                } catch (ResourceNotFoundException $exception) {
                    // pass to next resolver
                }
            }
        }

        throw new ResourceNotFoundException(sprintf('No resolver was able to resolve path %s', $path));
    }
}
