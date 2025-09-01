<?php

namespace Jonston\SanctumBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Jonston\SanctumBundle\Entity\PersonalAccessToken;

/**
 * Repository для работы с токенами в базе данных
 * Это "менеджер" который умеет искать, сохранять и удалять токены
 */
class PersonalAccessTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        // Говорим Doctrine что мы работаем с сущностью PersonalAccessToken
        parent::__construct($registry, PersonalAccessToken::class);
    }

    /**
     * Найти токен по хешу (это главный метод для аутентификации)
     *
     * @param string $tokenHash - хеш токена из заголовка Authorization
     * @return PersonalAccessToken|null - токен или null если не найден
     */
    public function findByToken(string $tokenHash): ?PersonalAccessToken
    {
        return $this->findOneBy([
            'token' => $tokenHash
        ]);
    }

    /**
     * Найти все токены пользователя
     *
     * @param int $userId - ID пользователя
     * @return PersonalAccessToken[] - массив токенов
     */
    public function findByUser(int $userId): array
    {
        return $this->findBy([
            'userId' => $userId
        ]);
    }

    /**
     * Найти активные (не истекшие) токены пользователя
     */
    public function findActiveByUser(int $userId): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.userId = :userId')
            ->andWhere('t.expiresAt IS NULL OR t.expiresAt > :now')
            ->setParameter('userId', $userId)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getResult();
    }

    /**
     * Сохранить токен в БД
     */
    public function save(PersonalAccessToken $token): void
    {
        $this->getEntityManager()->persist($token);
        $this->getEntityManager()->flush();
    }

    /**
     * Удалить токен из БД
     */
    public function remove(PersonalAccessToken $token): void
    {
        $this->getEntityManager()->remove($token);
        $this->getEntityManager()->flush();
    }

    /**
     * Удалить все истекшие токены (для очистки)
     */
    public function removeExpired(): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt IS NOT NULL AND t.expiresAt <= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->execute();
    }
}