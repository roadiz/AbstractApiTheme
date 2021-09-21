<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Extractor;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
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
class ApplicationExtractor implements ApplicationExtractorInterface, ApplicationProviderInterface
{
    /**
     * @var class-string
     */
    protected string $applicationClass;
    private ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param class-string $applicationClass
     */
    public function __construct(ManagerRegistry $managerRegistry, string $applicationClass)
    {
        $this->applicationClass = $applicationClass;
        $this->managerRegistry = $managerRegistry;
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

    protected function getRepository(): ObjectRepository
    {
        return $this->managerRegistry->getRepository($this->applicationClass);
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
