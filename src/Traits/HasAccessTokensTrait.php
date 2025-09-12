<?php

namespace Jonston\SanctumBundle\Traits;

use Doctrine\Common\Collections\Collection;
use Jonston\SanctumBundle\Entity\AccessToken;

/**
 * @property Collection<int, AccessToken> $accessTokens
 */
trait HasAccessTokensTrait
{
    abstract public function getId(): int|string;

    public function hasAccessToken(AccessToken $token): bool
    {
        return $this->getAccessTokens()->contains($token);
    }

    public function getAccessTokens(): Collection
    {
        return $this->accessTokens;
    }

    public function addAccessToken(AccessToken $token): void
    {
        if ( ! $this->hasAccessToken($token)) {
            $this->getAccessTokens()->add($token);
        }
    }

    public function removeAccessToken(AccessToken $token): void
    {
        $this->getAccessTokens()->removeElement($token);
    }

}