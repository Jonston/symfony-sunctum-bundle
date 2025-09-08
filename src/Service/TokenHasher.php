<?php

namespace Jonston\SanctumBundle\Service;

use Exception;

class TokenHasher
{
    /**
     * @throws Exception
     */
    public function generatePlainToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    public function verifyToken(string $plainToken, string $hashedToken): bool
    {
        return hash_equals($hashedToken, $this->hashToken($plainToken));
    }
}
