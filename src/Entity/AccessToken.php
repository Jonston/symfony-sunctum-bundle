<?php

namespace Jonston\SanctumBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Jonston\SanctumBundle\Contract\HasAccessTokensInterface;

#[ORM\Entity]
#[ORM\Table(name: 'personal_access_tokens')]
#[ORM\Index(columns: ['token'], name: 'personal_access_tokens_token_index')]
class AccessToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private ?string $token = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $lastUsedAt = null;

    #[ORM\ManyToOne(targetEntity: HasAccessTokensInterface::class)]
    private ?HasAccessTokensInterface $owner = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;
        return $this;
    }

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getExpiresAt(): ?DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?DateTimeImmutable $expiresAt): self
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getLastUsedAt(): ?DateTimeImmutable
    {
        return $this->lastUsedAt;
    }

    public function getOwner(): ?HasAccessTokensInterface
    {
        return $this->owner;
    }

    public function setOwner(HasAccessTokensInterface $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function isValid(): bool
    {
        if ($this->expiresAt === null) {
            return true;
        }
        return $this->expiresAt > new DateTimeImmutable();
    }

    public function updateLastUsedAt(): self
    {
        $this->lastUsedAt = new DateTimeImmutable();
        return $this;
    }
}