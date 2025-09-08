<?php

namespace Jonston\SanctumBundle\Security;

use Jonston\SanctumBundle\Contract\TokenableInterface;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class UserAdapter implements UserInterface
{
    public function __construct(
        private TokenableInterface $tokenable
    ) {}

    public function getUserIdentifier(): string
    {
        return (string) $this->tokenable->getTokenableId();
    }

    public function getRoles(): array
    {
        return ['ROLE_API_USER'];
    }

    public function eraseCredentials(): void
    {
        // Ничего не делаем, токен не содержит чувствительных данных
    }

    public function getTokenable(): TokenableInterface
    {
        return $this->tokenable;
    }
}