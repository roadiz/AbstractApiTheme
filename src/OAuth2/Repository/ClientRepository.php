<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OAuth2\Repository;

use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Themes\AbstractApiTheme\Entity\Application;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     * @return object|ClientEntityInterface|Application|null
     */
    public function getClientEntity($clientIdentifier)
    {
        return $this->entityManager
            ->getRepository(Application::class)
            ->findOneBy([
                'apiKey' => trim($clientIdentifier),
                'enabled' => true
            ]);
    }

    /**
     * @inheritDoc
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        /** @var ClientEntityInterface|null $client */
        $client = $this->getClientEntity($clientIdentifier);

        if (null === $client) {
            return false;
        }

        if (!$client->isConfidential()) {
            return true;
        }

        if ($client instanceof Application &&
            hash_equals($client->getSecret() ?? '', (string) $clientSecret ?? '') &&
            $this->isGrantSupported($client, $grantType)
        ) {
            return true;
        }

        return false;
    }

    private function isGrantSupported(Application $client, ?string $grant): bool
    {
        if (null === $grant) {
            return true;
        }

        return \in_array($grant, $client->getGrantTypes());
    }
}
