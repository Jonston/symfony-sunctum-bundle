<?php

namespace Jonston\SanctumBundle\Tests\Unit\Service;

use Exception;
use Jonston\SanctumBundle\Service\TokenHasher;
use PHPUnit\Framework\TestCase;

class TokenHasherTest extends TestCase
{
    private TokenHasher $tokenHasher;

    protected function setUp(): void
    {
        $this->tokenHasher = new TokenHasher();
    }

    /**
     * @throws Exception
     */
    public function testGeneratePlainToken(): void
    {
        $token = $this->tokenHasher->generatePlainToken();

        $this->assertIsString($token);
        $this->assertEquals(80, strlen($token)); // 40 байт * 2 (hex)
    }

    /**
     * @throws Exception
     */
    public function testGeneratedTokensAreUnique(): void
    {
        $token1 = $this->tokenHasher->generatePlainToken();
        $token2 = $this->tokenHasher->generatePlainToken();

        $this->assertNotEquals($token1, $token2);
    }

    public function testHashToken(): void
    {
        $plainToken = 'test-token';
        $hashedToken = $this->tokenHasher->hashToken($plainToken);

        $this->assertIsString($hashedToken);
        $this->assertEquals(64, strlen($hashedToken)); // SHA256 hex
        $this->assertEquals(hash('sha256', $plainToken), $hashedToken);
    }

    public function testHashTokenConsistency(): void
    {
        $plainToken = 'test-token';
        $hash1 = $this->tokenHasher->hashToken($plainToken);
        $hash2 = $this->tokenHasher->hashToken($plainToken);

        $this->assertEquals($hash1, $hash2);
    }

    public function testVerifyTokenWithValidToken(): void
    {
        $plainToken = 'test-token';
        $hashedToken = $this->tokenHasher->hashToken($plainToken);

        $result = $this->tokenHasher->verifyToken($plainToken, $hashedToken);

        $this->assertTrue($result);
    }

    public function testVerifyTokenWithInvalidToken(): void
    {
        $plainToken = 'test-token';
        $wrongToken = 'wrong-token';
        $hashedToken = $this->tokenHasher->hashToken($plainToken);

        $result = $this->tokenHasher->verifyToken($wrongToken, $hashedToken);

        $this->assertFalse($result);
    }

    public function testVerifyTokenWithInvalidHash(): void
    {
        $plainToken = 'test-token';
        $invalidHash = 'invalid-hash';

        $result = $this->tokenHasher->verifyToken($plainToken, $invalidHash);

        $this->assertFalse($result);
    }
}
