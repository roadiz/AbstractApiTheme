<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OAuth2\Repository;

use Doctrine\Persistence\ManagerRegistry;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Themes\AbstractApiTheme\Entity\Application;

class ClientRepository implements ClientRepositoryInterface
{
    protected ManagerRegistry $managerRegistry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @inheritDoc
     * @return object|ClientEntityInterface|Application|null
     */
    public function getClientEntity($clientIdentifier)
    {
        return $this->managerRegistry
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
