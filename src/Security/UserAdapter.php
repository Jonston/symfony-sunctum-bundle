<?php

namespace Jonston\SanctumBundle\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Jonston\SanctumBundle\Contract\TokenableInterface;

class UserAdapter implements UserInterface
{
    public function __construct(
        private readonly TokenableInterface $tokenable
    ) {}

    public function getUserIdentifier(): string
    {
        return (string) $this->tokenable->getTokenableId();
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
        // Нечего очищать
    }

    public function getTokenable(): TokenableInterface
    {
        return $this->tokenable;
    }
}
