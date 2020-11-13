<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OAuth2\Repository;

use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use RZ\Roadiz\Core\Repositories\EntityRepository;
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
     * @return EntityRepository
     */
    protected function getRepository()
    {
        return $this->entityManager->getRepository(Application::class);
    }

    /**
     * @inheritDoc
     */
    public function getClientEntity($clientIdentifier)
    {
        return $this->getRepository()->findOneBy([
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

        if ($client instanceof Application && hash_equals($client->getSecret(), (string) $clientSecret)) {
            return true;
        }

        return false;
    }
}
