<?php

namespace Jonston\SanctumBundle\Service;

use Random\RandomException;
use Jonston\SanctumBundle\Entity\PersonalAccessToken;
use Jonston\SanctumBundle\Contract\TokenableInterface;
use Jonston\SanctumBundle\Repository\PersonalAccessTokenRepository;

readonly class TokenService
{
    public function __construct(
        private PersonalAccessTokenRepository $tokenRepository
    ) {}

    /**
     * @throws RandomException
     */
    public function createToken(
        TokenableInterface $tokenable,
        string $name,
        array $abilities = ['*']
    ): PersonalAccessToken
    {
        $token = new PersonalAccessToken();
        $token->setTokenableId($tokenable->getTokenableId());
        $token->setTokenableType($tokenable->getTokenableType());
        $token->setName($name);
        $token->setToken($this->generateToken());
        $token->setCreatedAt(new \DateTimeImmutable());

        $this->tokenRepository->save($token);

        return $token;
    }

    public function findValidToken(string $tokenHash): ?PersonalAccessToken
    {
        $token = $this->tokenRepository->findByToken(hash('sha256', $tokenHash));
        if ($token && $token->getExpiresAt() === null) {
            return $token;
        }
        return null;
    }

    public function getTokenable(PersonalAccessToken $accessToken): ?TokenableInterface
    {
        $tokenable = $this->tokenRepository->findTokenable(
            $accessToken->getTokenableType(),
            $accessToken->getTokenableId()
        );
        return $tokenable instanceof TokenableInterface ? $tokenable : null;
    }

    public function getTokensFor(TokenableInterface $tokenable): array
    {
        return $this->tokenRepository->findByTokenable(
            $tokenable->getTokenableType(),
            $tokenable->getTokenableId()
        );
    }

    public function revokeToken(PersonalAccessToken $token): void
    {
        $this->tokenRepository->remove($token);
    }

    public function updateLastUsed(TokenableInterface $tokenable): void
    {
        $tokens = $this->getTokensFor($tokenable);
        foreach ($tokens as $token) {
            $token->setLastUsedAt(new \DateTimeImmutable());
            $this->tokenRepository->save($token);
        }
    }

    /**
     * @throws RandomException
     */
    private function generateToken(): string
    {
        $plainToken = bin2hex(random_bytes(32));
        return hash('sha256', $plainToken);
    }

    /**
     * @throws RandomException
     */
    public function generatePlainToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}