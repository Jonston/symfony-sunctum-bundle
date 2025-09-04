<?php

namespace Jonston\SanctumBundle\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\MappedSuperclass]
abstract class TokenizableUser implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    protected ?int $id = null;

    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: PersonalAccessToken::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    protected Collection $accessTokens;

    public function __construct()
    {
        $this->accessTokens = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getAccessTokens(): Collection
    {
        return $this->accessTokens;
    }

    public function addAccessToken(PersonalAccessToken $accessToken): static
    {
        if ( ! $this->accessTokens->contains($accessToken)) {
            $this->accessTokens->add($accessToken);
            $accessToken->setUser($this);
        }

        return $this;
    }

    public function removeAccessToken(PersonalAccessToken $accessToken): static
    {
        if ($this->accessTokens->removeElement($accessToken)) {
            if ($accessToken->getUser() === $this) {
                $accessToken->setUser(null);
            }
        }

        return $this;
    }


    public function getActiveTokens(): Collection
    {
        return $this->accessTokens->filter(fn(PersonalAccessToken $token) => $token->isValid());
    }

    public function getTokenByName(string $name): ?PersonalAccessToken
    {
        foreach ($this->accessTokens as $token) {
            if ($token->getName() === $name) {
                return $token;
            }
        }

        return null;
    }

    public function removeExpiredTokens(): static
    {
        $expiredTokens = $this->accessTokens->filter(
            fn(PersonalAccessToken $token) => $token->isExpired()
        );

        foreach ($expiredTokens as $token) {
            $this->removeAccessToken($token);
        }

        return $this;
    }

    public function revokeAllTokens(): static
    {
        $this->accessTokens->clear();
        return $this;
    }

    public function revokeToken(string $name): bool
    {
        $token = $this->getTokenByName($name);

        if ($token) {
            $this->removeAccessToken($token);
            return true;
        }

        return false;
    }
}