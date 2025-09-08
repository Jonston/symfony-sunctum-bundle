<?php

namespace Jonston\SanctumBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'personal_access_tokens')]
#[ORM\Index(columns: ['token'], name: 'personal_access_tokens_token_index')]
class PersonalAccessToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 64, unique: true)]
    private ?string $token = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $lastUsedAt = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $tokenableType = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string|int|null $tokenableId = null;

    private ?string $plainTextToken = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
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

    public function getPlainTextToken(): ?string
    {
        return $this->plainTextToken;
    }

    public function setPlainTextToken(string $plainTextToken): self
    {
        $this->plainTextToken = $plainTextToken;
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

    public function setLastUsedAt(?DateTimeImmutable $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }

    public function getTokenableType(): ?string
    {
        return $this->tokenableType;
    }

    public function setTokenableType(string $type): self
    {
        $this->tokenableType = $type;
        return $this;
    }

    public function getTokenableId(): string|int|null
    {
        return $this->tokenableId;
    }

    public function setTokenableId(string|int $id): self
    {
        $this->tokenableId = $id;
        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt !== null && $this->expiresAt <= new DateTimeImmutable();
    }

    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    public function updateLastUsedAt(): self
    {
        $this->lastUsedAt = new DateTimeImmutable();
        return $this;
    }
}