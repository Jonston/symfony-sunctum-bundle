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

    public function findByUser(string $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->setParameter('user', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findActiveByUser(string $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.user = :user')
            ->andWhere('t.expiresAt IS NULL OR t.expiresAt > :now')
            ->setParameter('user', $userId)
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