<?php

namespace Jonston\SanctumBundle\Service;

use DateTimeImmutable;
use Exception;
use Jonston\SanctumBundle\Contract\TokenableInterface;
use Jonston\SanctumBundle\Entity\PersonalAccessToken;
use Jonston\SanctumBundle\Repository\PersonalAccessTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

class TokenManager
{
    public function __construct(
        private readonly PersonalAccessTokenRepository $tokenRepository,
        private readonly EntityManagerInterface $em,
        private readonly TokenHasher $tokenHasher
    ) {}

    /**
     * @throws Exception
     */
    public function createToken(TokenableInterface $tokenable, string $name, ?DateTimeImmutable $expiresAt = null): array
    {
        $plainTextToken = $this->tokenHasher->generatePlainToken();
        $hashedToken = $this->tokenHasher->hashToken($plainTextToken);

        $token = new PersonalAccessToken();
        $token->setName($name);
        $token->setTokenableType($tokenable->getTokenableType());
        $token->setTokenableId($tokenable->getTokenableId());
        $token->setToken($hashedToken);
        $token->setPlainTextToken($plainTextToken);

        if ($expiresAt !== null) {
            $token->setExpiresAt($expiresAt);
        }

        $this->saveToken($token);

        return [
            'token' => $plainTextToken,
            'entity' => $token
        ];
    }

    public function verifyToken(string $plainTextToken, string $hashedToken): bool
    {
        return $this->tokenHasher->verifyToken($plainTextToken, $hashedToken);
    }

    public function hashToken(string $plainTextToken): string
    {
        return $this->tokenHasher->hashToken($plainTextToken);
    }

    public function revokeToken(PersonalAccessToken $token): void
    {
        $this->removeToken($token);
    }

    public function revokeAllTokensForUser(TokenableInterface $tokenable): void
    {
        $tokens = $this->tokenRepository->findByTokenable($tokenable->getTokenableType(), $tokenable->getTokenableId());

        foreach ($tokens as $token) {
            $this->removeToken($token);
        }
    }

    public function getUserTokens(TokenableInterface $tokenable): array
    {
        return $this->tokenRepository->findActiveByTokenable($tokenable->getTokenableType(), $tokenable->getTokenableId());
    }

    public function cleanupExpiredTokens(): int
    {
        $qb = $this->em->createQueryBuilder();
        $qb->delete(PersonalAccessToken::class, 't')
            ->where('t.expiresAt IS NOT NULL AND t.expiresAt <= :now')
            ->setParameter('now', new \DateTimeImmutable());
        return $qb->getQuery()->execute();
    }

    private function saveToken(PersonalAccessToken $token): void
    {
        $this->em->persist($token);
        $this->em->flush();
    }

    private function removeToken(PersonalAccessToken $token): void
    {
        $this->em->remove($token);
        $this->em->flush();
    }
}