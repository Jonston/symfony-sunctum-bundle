<?php

namespace Jonston\SanctumBundle\Tests\Entity;

use DateTimeImmutable;
use Jonston\SanctumBundle\Entity\PersonalAccessToken;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class PersonalAccessTokenTest extends TestCase
{
    public function testTokenCreation(): void
    {
        $token = new PersonalAccessToken();

        $this->assertInstanceOf(DateTimeImmutable::class, $token->getCreatedAt());
        $this->assertNotEmpty($token->getToken());
        $this->assertNull($token->getName());
        $this->assertNull($token->getExpiresAt());
        $this->assertNull($token->getLastUsedAt());
    }

    public function testSettersAndGetters(): void
    {
        $token = new PersonalAccessToken();
        $now = new DateTimeImmutable();

        $token->setName('Test Token');
        $token->setUserId(123);
        $token->setExpiresAt($now);
        $token->setLastUsedAt($now);

        $this->assertEquals('Test Token', $token->getName());
        $this->assertEquals(123, $token->getUserId());
        $this->assertEquals($now, $token->getExpiresAt());
        $this->assertEquals($now, $token->getLastUsedAt());
    }

    public function testSetTokenHashesValue(): void
    {
        $token = new PersonalAccessToken();
        $plainToken = 'plain-text-token';

        $token->setToken($plainToken);

        $this->assertEquals(hash('sha256', $plainToken), $token->getToken());
        $this->assertNotEquals($plainToken, $token->getToken());
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

    public function testCreateForUser(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('123');

        $expiresAt = new DateTimeImmutable('+30 days');
        $token = PersonalAccessToken::createForUser($user, 'Mobile App', $expiresAt);

        $this->assertEquals('Mobile App', $token->getName());
        $this->assertEquals(123, $token->getUserId());
        $this->assertEquals($expiresAt, $token->getExpiresAt());
        $this->assertInstanceOf(DateTimeImmutable::class, $token->getCreatedAt());
    }

    public function testCreateForUserWithoutExpiration(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('456');

        $token = PersonalAccessToken::createForUser($user, 'Web App');

        $this->assertEquals('Web App', $token->getName());
        $this->assertEquals(456, $token->getUserId());
        $this->assertNull($token->getExpiresAt());
    }
}