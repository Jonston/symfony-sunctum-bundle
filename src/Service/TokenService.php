<?php

namespace Jonston\SanctumBundle\Service;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Jonston\SanctumBundle\Contract\HasAccessTokensInterface;
use Jonston\SanctumBundle\Entity\AccessToken;
use Jonston\SanctumBundle\Repository\AccessTokenRepository;
use Random\RandomException;

readonly class TokenService
{
    public function __construct(
        private AccessTokenRepository $tokenRepository,
        private EntityManagerInterface $entityManager,
        private int $tokenLength = 40,
        private ?int $defaultExpirationHours = null
    ) {}

    /**
     * @throws Exception
     */
    public function createToken(
        HasAccessTokensInterface $owner,
        ?DateTimeImmutable $expiresAt = null
    ): array
    {
        $plainTextToken = $this->generatePlainTextToken();
        $hashedToken = $this->hashToken($plainTextToken);

        $accessToken = new AccessToken();
        $accessToken->setOwner($owner);
        $accessToken->setToken($hashedToken);

        if ($expiresAt !== null) {
            $accessToken->setExpiresAt($expiresAt);
        } elseif ($this->defaultExpirationHours !== null) {
            $accessToken->setExpiresAt(
                (new DateTimeImmutable())->modify('+' . $this->defaultExpirationHours . ' hours')
            );
        }

        $this->entityManager->persist($accessToken);
        $this->entityManager->flush();

        return [
            'accessToken' => $accessToken,
            'plainTextToken' => $plainTextToken,
        ];
    }

    public function findValidToken(string $token): ?AccessToken
    {
        $hashedToken = $this->hashToken($token);
        /** @var AccessToken|null $accessToken */
        $accessToken = $this->tokenRepository->findOneBy(['token' => $hashedToken]);

        if ($accessToken === null || !$accessToken->isValid()) {
            return null;
        }

        return $accessToken;
    }

    public function getTokenOwner(AccessToken $accessToken): HasAccessTokensInterface
    {
        return $accessToken->getOwner();
    }

    public function updateLastUsed(AccessToken $accessToken): void
    {
        $accessToken->updateLastUsedAt();
        $this->entityManager->persist($accessToken);
        $this->entityManager->flush();
    }

    public function revokeToken(HasAccessTokensInterface $owner, AccessToken $accessToken): void
    {
        $owner->removeAccessToken($accessToken);
        $this->entityManager->remove($accessToken);
        $this->entityManager->flush();
    }

    public function revokeAllTokens(HasAccessTokensInterface $tokenOwner): void
    {
        foreach ($tokenOwner->getAccessTokens() as $token) {
            $tokenOwner->removeAccessToken($token);
            $this->entityManager->remove($token);
        }
        $this->entityManager->flush();
    }

    /**
     * @throws RandomException
     */
    private function generatePlainTextToken(): string
    {
        return bin2hex(random_bytes($this->tokenLength / 2));
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    public function purgeExpiredTokens(): int
    {
        return $this->tokenRepository->removeExpiredTokens();
    }
}