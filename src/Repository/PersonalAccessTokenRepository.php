<?php

namespace Jonston\SanctumBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Jonston\SanctumBundle\Entity\PersonalAccessToken;

class PersonalAccessTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalAccessToken::class);
    }

    public function findByToken(string $tokenHash): ?PersonalAccessToken
    {
        /** @var PersonalAccessToken|null $result */
        $result = $this->findOneBy([
            'token' => $tokenHash
        ]);

        return $result;
    }

    public function findByTokenable(string $tokenableType, string|int $tokenableId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.tokenableType = :type')
            ->andWhere('t.tokenableId = :id')
            ->setParameter('type', $tokenableType)
            ->setParameter('id', $tokenableId)
            ->getQuery()
            ->getResult();
    }

    public function findActiveByTokenable(string $tokenableType, string|int $tokenableId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.tokenableType = :type')
            ->andWhere('t.tokenableId = :id')
            ->andWhere('t.expiresAt IS NULL OR t.expiresAt > :now')
            ->setParameter('type', $tokenableType)
            ->setParameter('id', $tokenableId)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    // Оставляю старые методы для обратной совместимости, но они deprecated

    /**
     * @deprecated Use findByTokenable instead
     */
    public function findByUser(string $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.tokenableId = :id')
            ->setParameter('id', $userId)
            ->getQuery()
            ->getResult();
    }

    /**
     * @deprecated Use findActiveByTokenable instead
     */
    public function findActiveByUser(string $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.tokenableId = :id')
            ->andWhere('t.expiresAt IS NULL OR t.expiresAt > :now')
            ->setParameter('id', $userId)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    public function save(PersonalAccessToken $token): void
    {
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
    }
}