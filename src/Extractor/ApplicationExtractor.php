<?php
/**
 * AbstractApiTheme - ApplicationExtractor.php
 *
 * Initial version by: ambroisemaupate
 * Initial version created on: 2019-01-03
 */
declare(strict_types=1);

namespace Themes\AbstractApiTheme\Extractor;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserInterface;
use Themes\AbstractApiTheme\Entity\Application;
use Themes\AbstractApiTheme\Security\Authentication\Provider\ApplicationProviderInterface;

class ApplicationExtractor implements ApplicationExtractorInterface, ApplicationProviderInterface
{
    /**
     * @var EntityRepository
     */
    private $applicationRepository;

    /**
     * ApplicationExtractor constructor.
     *
     * @param EntityRepository $applicationRepository
     */
    public function __construct(EntityRepository $applicationRepository)
    {
        $this->applicationRepository = $applicationRepository;
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

    /**
     * @inheritDoc
     */
    public function extractApplication(Request $request): ?Application
    {
        $apiKey = $this->getApiKey($request);
        if (null !== $apiKey) {
            return $this->applicationRepository->findOneByApiKey($apiKey);
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function loadApplicationByApiKey(string $apiKey): ?Application
    {
        return $this->applicationRepository->findOneByApiKey($apiKey);
    }
}