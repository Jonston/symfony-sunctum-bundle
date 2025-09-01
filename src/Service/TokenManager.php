<?php

namespace Jonston\SanctumBundle\Service;

use Random\RandomException;
use Symfony\Component\Security\Core\User\UserInterface;
use Jonston\SanctumBundle\Entity\PersonalAccessToken;
use Jonston\SanctumBundle\Repository\PersonalAccessTokenRepository;

readonly class TokenManager
{
    public function __construct(
        private PersonalAccessTokenRepository $tokenRepository
    ) {}

    /**
     * @throws RandomException
     */
    public function createToken(UserInterface $user, string $name, ?\DateTimeImmutable $expiresAt = null): array
    {
        $plainTextToken = bin2hex(random_bytes(40));

        $token = PersonalAccessToken::createForUser($user, $name, $expiresAt);
        $token->setToken($plainTextToken);

        $this->tokenRepository->save($token);

        return [
            'token' => $plainTextToken,
            'entity' => $token
        ];
    }

    public function revokeToken(PersonalAccessToken $token): void
    {
        $this->tokenRepository->remove($token);
    }

    public function revokeAllTokensForUser(UserInterface $user): void
    {
        $tokens = $this->tokenRepository->findByUser((int) $user->getUserIdentifier());

        foreach ($tokens as $token) {
            $this->tokenRepository->remove($token);
        }
    }

    public function getUserTokens(UserInterface $user): array
    {
        return $this->tokenRepository->findActiveByUser((int) $user->getUserIdentifier());
    }

    public function cleanupExpiredTokens(): int
    {
        return $this->tokenRepository->removeExpired();
    }
}