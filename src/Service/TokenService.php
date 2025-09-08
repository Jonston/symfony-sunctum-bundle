<?php

namespace Jonston\SanctumBundle\Service;

use DateTimeImmutable;
use Jonston\SanctumBundle\Entity\PersonalAccessToken;
use Jonston\SanctumBundle\Contract\TokenableInterface;
use Jonston\SanctumBundle\Repository\PersonalAccessTokenRepository;

readonly class TokenService
{
    public function __construct(
        private PersonalAccessTokenRepository $tokenRepository,
        private TokenHasher $tokenHasher
    ) {}

    /**
     * @throws \Exception
     */
    public function createToken(
        TokenableInterface $tokenable,
        string $name,
    ): PersonalAccessToken
    {
        $plainToken = $this->tokenHasher->generatePlainToken();
        $hashedToken = $this->tokenHasher->hashToken($plainToken);

        $token = new PersonalAccessToken();
        $token->setTokenableId($tokenable->getTokenableId());
        $token->setTokenableType($tokenable->getTokenableType());
        $token->setName($name);
        $token->setToken($hashedToken);
        $token->setCreatedAt(new DateTimeImmutable());

        $this->tokenRepository->save($token);

        return $token;
    }

    public function findValidToken(string $plainToken): ?PersonalAccessToken
    {
        $hashedToken = $this->tokenHasher->hashToken($plainToken);
        $token = $this->tokenRepository->findByToken($hashedToken);
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
            $token->setLastUsedAt(new DateTimeImmutable());
            $this->tokenRepository->save($token);
        }
    }
}