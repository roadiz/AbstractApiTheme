<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Extractor;

use Doctrine\ORM\EntityRepository;
use Pimple\Container;
use RZ\Roadiz\Core\ContainerAwareInterface;
use RZ\Roadiz\Core\ContainerAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Themes\AbstractApiTheme\Entity\Application;
use Themes\AbstractApiTheme\Security\Authentication\Provider\ApplicationProviderInterface;

/**
 * Extract a valid Application from Request.
 *
 * Can be extracted from query `api_key` param
 * or `x-api-key` header param.
 *
 * @package Themes\AbstractApiTheme\Extractor
 */
class ApplicationExtractor implements ApplicationExtractorInterface, ApplicationProviderInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var Container
     */
    protected $container;
    /**
     * @var string
     */
    protected $applicationClass;

    /**
     * ApplicationExtractor constructor.
     *
     * @param Container $container
     * @param string    $applicationClass
     */
    public function __construct(Container $container, string $applicationClass)
    {
        $this->container = $container;
        $this->applicationClass = $applicationClass;
    }

    /**
     * @param Request $request
     *
     * @return string|null
     */
    protected function getApiKey(Request $request): ?string
    {
        $apiKey = null;

        if ($request->query->has('api_key')) {
            $apiKey = $request->query->get('api_key', null);
        } elseif ($request->headers->has('x-api-key')) {
            $apiKey = $request->headers->get('x-api-key', null);
        }

        return $apiKey;
    }

    /**
     * @inheritDoc
     */
    public function hasApiKey(Request $request): bool
    {
        return $this->getApiKey($request) !== null;
    }

    protected function getRepository(): EntityRepository
    {
        return $this->get('em')->getRepository($this->applicationClass);
    }

    /**
     * @inheritDoc
     */
    public function extractApplication(Request $request): ?Application
    {
        $apiKey = $this->getApiKey($request);
        if (null !== $apiKey) {
            return $this->getRepository()->findOneByApiKey($apiKey);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function loadApplicationByApiKey(string $apiKey): ?Application
    {
        return $this->getRepository()->findOneByApiKey($apiKey);
    }
}
