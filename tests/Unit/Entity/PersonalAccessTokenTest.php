<?php

namespace Jonston\SanctumBundle\Tests\Unit\Entity;

use DateTimeImmutable;
use Jonston\SanctumBundle\Entity\PersonalAccessToken;
use Jonston\SanctumBundle\Contract\TokenableInterface;
use PHPUnit\Framework\TestCase;

class PersonalAccessTokenTest extends TestCase
{
    public function testTokenCreation(): void
    {
        $token = new PersonalAccessToken();

        $this->assertInstanceOf(DateTimeImmutable::class, $token->getCreatedAt());
        $this->assertNull($token->getToken());
        $this->assertNull($token->getName());
        $this->assertNull($token->getExpiresAt());
        $this->assertNull($token->getLastUsedAt());
        $this->assertNull($token->getTokenableType());
        $this->assertNull($token->getTokenableId());
    }

    public function testSettersAndGetters(): void
    {
        $token = new PersonalAccessToken();
        $now = new DateTimeImmutable();

        $token->setName('Test Token');
        $token->setTokenableType('App\\Entity\\User');
        $token->setTokenableId(123);
        $token->setExpiresAt($now);
        $token->setLastUsedAt($now);
        $token->setToken('hashed-token');

        $this->assertEquals('Test Token', $token->getName());
        $this->assertEquals('App\\Entity\\User', $token->getTokenableType());
        $this->assertEquals(123, $token->getTokenableId());
        $this->assertEquals($now, $token->getExpiresAt());
        $this->assertEquals($now, $token->getLastUsedAt());
        $this->assertEquals('hashed-token', $token->getToken());
    }

    public function testPlainTextToken(): void
    {
        $token = new PersonalAccessToken();
        $plainToken = 'plain-text-token';

        $token->setPlainTextToken($plainToken);

        $this->assertEquals($plainToken, $token->getPlainTextToken());
    }

    public function testIsExpiredWithNoExpiration(): void
    {
        $token = new PersonalAccessToken();

        $this->assertFalse($token->isExpired());
    }

    public function testIsExpiredWithFutureDate(): void
    {
        $token = new PersonalAccessToken();
        $future = new DateTimeImmutable('+1 day');
        $token->setExpiresAt($future);

        $this->assertFalse($token->isExpired());
    }

    public function testIsExpiredWithPastDate(): void
    {
        $token = new PersonalAccessToken();
        $past = new DateTimeImmutable('-1 day');
        $token->setExpiresAt($past);

        $this->assertTrue($token->isExpired());
    }

    public function testUpdateLastUsedAt(): void
    {
        $token = new PersonalAccessToken();
        $token->updateLastUsedAt();

        $this->assertInstanceOf(DateTimeImmutable::class, $token->getLastUsedAt());
    }
}