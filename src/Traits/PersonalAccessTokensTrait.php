<?php

namespace Jonston\SanctumBundle\Traits;

use Jonston\SanctumBundle\Entity\PersonalAccessToken;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Jonston\SanctumBundle\Entity\TokenizableUser;

trait PersonalAccessTokensTrait
{
    protected Collection $personalAccessTokens;

    public function initializePersonalAccessTokens(): void
    {
        $this->personalAccessTokens = new ArrayCollection();
    }

    public function getPersonalAccessTokens(): Collection
    {
        return $this->personalAccessTokens;
    }

    public function addPersonalAccessToken(PersonalAccessToken $token): self
    {
        /** @var TokenizableUser $this */
        if ( ! $this->personalAccessTokens->contains($token)) {
            $this->personalAccessTokens->add($token);
            $token->setUser($this);
        }
        return $this;
    }

    public function removePersonalAccessToken(PersonalAccessToken $token): self
    {
        if ($this->personalAccessTokens->removeElement($token)) {
            if ($token->getUser() === $this) {
                $token->setUser(null);
            }
        }
        return $this;
    }
}