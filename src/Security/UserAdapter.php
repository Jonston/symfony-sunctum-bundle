<?php

namespace Jonston\SanctumBundle\Security;

use Jonston\SanctumBundle\Contract\HasAccessTokensInterface;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class UserAdapter implements UserInterface
{
    private HasAccessTokensInterface $tokenOwner;

    public function __construct(HasAccessTokensInterface $tokenOwner)
    {
        $this->tokenOwner = $tokenOwner;
    }

    public function getTokenOwner(): HasAccessTokensInterface
    {
        return $this->tokenOwner;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->tokenOwner->getId();
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];

        if (method_exists($this->tokenOwner, 'getRoles')) {

            $ownerRoles = $this->tokenOwner->getRoles();

            if (is_array($ownerRoles)) {
                $roles = array_merge($roles, $ownerRoles);
            }
        }

        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // No sensitive data to erase
    }
}
