<?php

namespace Jonston\SanctumBundle\Contract;

use Doctrine\Common\Collections\Collection;
use Jonston\SanctumBundle\Entity\AccessToken;

interface HasAccessTokensInterface
{
    public function getId(): int|string;

    public function hasAccessToken(AccessToken $token): bool;

    public function getAccessTokens(): Collection;

    public function addAccessToken(AccessToken $token): void;

    public function removeAccessToken(AccessToken $token): void;
}