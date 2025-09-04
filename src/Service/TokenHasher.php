<?php

namespace Jonston\SanctumBundle\Service;

use Exception;

class TokenHasher
{
    /**
     * Генерирует случайный токен
     *
     * @throws Exception
     */
    public function generatePlainToken(): string
    {
        return bin2hex(random_bytes(40));
    }

    /**
     * Хеширует токен
     */
    public function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    /**
     * Проверяет соответствие токена его хешу
     */
    public function verifyToken(string $plainToken, string $hashedToken): bool
    {
        return hash_equals($hashedToken, $this->hashToken($plainToken));
    }
}
