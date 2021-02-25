<?php
declare(strict_types=1);

namespace Themes\AbstractApiTheme\OAuth2\Repository;

use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use RZ\Roadiz\Core\Repositories\EntityRepository;
use Themes\AbstractApiTheme\Converter\ScopeConverter;
use Themes\AbstractApiTheme\Entity\AuthorizationCode;
use Themes\AbstractApiTheme\Exception\OAuth2AuthenticationFailedException;
use Themes\AbstractApiTheme\Model\AuthCode;

final class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var ScopeConverter
     */
    protected $scopeConverter;

    /**
     * @var ClientRepositoryInterface
     */
    protected $clientRepository;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ScopeConverter $scopeConverter
     * @param ClientRepositoryInterface $clientRepository
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ScopeConverter $scopeConverter,
        ClientRepositoryInterface $clientRepository
    ) {
        $this->entityManager = $entityManager;
        $this->scopeConverter = $scopeConverter;
        $this->clientRepository = $clientRepository;
    }

    /**
     * @inheritDoc
     */
    public function getNewAuthCode()
    {
        return new AuthCode();
    }

    /**
     * @return EntityRepository<AuthorizationCode>
     */
    protected function getRepository(): EntityRepository
    {
        return $this->entityManager->getRepository(AuthorizationCode::class);
    }

    /**
     * @param AuthCodeEntityInterface $authCodeEntity
     * @throws UniqueTokenIdentifierConstraintViolationException
     * @return void
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        $authorizationCode = $this->getRepository()->findOneBy([
            'identifier' => $authCodeEntity->getIdentifier()
        ]);

        if (null !== $authorizationCode) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $this->buildAuthorizationCode($authCodeEntity);

        $this->entityManager->flush();
    }

    /**
     * @param string $codeId
     * @return void
     */
    public function revokeAuthCode($codeId)
    {
        /** @var AuthorizationCode|null $authorizationCode */
        $authorizationCode = $this->getRepository()->findOneBy([
            'identifier' => $codeId
        ]);

        if (null === $authorizationCode) {
            return;
        }

        $authorizationCode->revoke();

        $this->entityManager->flush();
    }

    /**
     * @inheritDoc
     */
    public function isAuthCodeRevoked($codeId)
    {
        /** @var AuthorizationCode|null $authorizationCode */
        $authorizationCode = $this->getRepository()->findOneBy([
            'identifier' => $codeId
        ]);

        if (null === $authorizationCode) {
            return true;
        }

        return $authorizationCode->isRevoked();
    }

    /**
     * @param AuthCodeEntityInterface $authCode
     * @return AuthorizationCode
     */
    private function buildAuthorizationCode(AuthCodeEntityInterface $authCode): AuthorizationCode
    {
        /** @var ClientEntityInterface|null $client */
        $client = $this->clientRepository->getClientEntity($authCode->getClient()->getIdentifier());
        if (null !== $client) {
            $authorizationCode = (new AuthorizationCode())
                ->setIdentifier($authCode->getIdentifier())
                ->setClient($client)
                ->setExpiry($authCode->getExpiryDateTime())
                ->setUserIdentifier(
                    null !== $authCode->getUserIdentifier() ?
                    (string) $authCode->getUserIdentifier() :
                    null
                )
                ->setScopes($authCode->getScopes())
            ;
            $this->entityManager->persist($authorizationCode);
            return $authorizationCode;
        }
        throw new OAuth2AuthenticationFailedException('Client does not exist.');
    }
}
