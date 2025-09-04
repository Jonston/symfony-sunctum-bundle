<?php

namespace Jonston\SanctumBundle\Tests\Unit\Entity;

use DateTimeImmutable;
use Jonston\SanctumBundle\Entity\PersonalAccessToken;
use Jonston\SanctumBundle\Entity\TokenizableUser;
use PHPUnit\Framework\TestCase;

class TestUser extends TokenizableUser
{
    protected ?int $id;

    public function __construct(int $id = 123)
    {
        parent::__construct();
        $this->id = $id;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    public function getRoles(): array { return []; }
    public function eraseCredentials(): void {}
    public function getPassword() {}
}

class TokenizableUserTest extends TestCase
{
    public function testAddAndRemoveAccessToken(): void
    {
        $user = new TestUser(1);
        $token = new PersonalAccessToken();
        $token->setName('test-token');
        $token->setUser($user);

        $user->addAccessToken($token);
        $this->assertCount(1, $user->getAccessTokens());
        $this->assertSame($token, $user->getAccessTokens()->first());

        $user->removeAccessToken($token);
        $this->assertCount(0, $user->getAccessTokens());
    }

    public function testGetActiveTokens(): void
    {
        $user = new TestUser(2);

        $activeToken = new PersonalAccessToken();
        $activeToken->setName('active');
        $activeToken->setUser($user);

        $expiredToken = new PersonalAccessToken();
        $expiredToken->setName('expired');
        $expiredToken->setUser($user);
        $expiredToken->setExpiresAt(new DateTimeImmutable('-1 day'));

        $user->addAccessToken($activeToken);
        $user->addAccessToken($expiredToken);

        $activeTokens = $user->getActiveTokens();
        $this->assertCount(1, $activeTokens);
        $this->assertSame($activeToken, $activeTokens->first());
    }

    public function testGetTokenByName(): void
    {
        $user = new TestUser(3);
        $token = new PersonalAccessToken();
        $token->setName('findme');
        $token->setUser($user);
        $user->addAccessToken($token);

        $this->assertSame($token, $user->getTokenByName('findme'));
        $this->assertNull($user->getTokenByName('notfound'));
    }

    public function testRemoveExpiredTokens(): void
    {
        $user = new TestUser(4);

        $expiredToken = new PersonalAccessToken();
        $expiredToken->setName('expired');
        $expiredToken->setExpiresAt(new DateTimeImmutable('-2 days'));
        $expiredToken->setUser($user);

        $activeToken = new PersonalAccessToken();
        $activeToken->setName('active');
        $activeToken->setExpiresAt(new DateTimeImmutable('+2 days'));
        $activeToken->setUser($user);

        $user->addAccessToken($expiredToken);
        $user->addAccessToken($activeToken);

        $user->removeExpiredTokens();
        $this->assertCount(1, $user->getAccessTokens());
        $this->assertSame($activeToken, $user->getAccessTokens()->first());
    }

    public function testRevokeAllTokens(): void
    {
        $user = new TestUser(5);

        $token1 = new PersonalAccessToken();
        $token1->setName('token1');
        $token1->setUser($user);

        $token2 = new PersonalAccessToken();
        $token2->setName('token2');
        $token2->setUser($user);

        $user->addAccessToken($token1);
        $user->addAccessToken($token2);
        $this->assertCount(2, $user->getAccessTokens());

        $user->revokeAllTokens();
        $this->assertCount(0, $user->getAccessTokens());
    }

    public function testRevokeTokenByName(): void
    {
        $user = new TestUser(6);
        $token = new PersonalAccessToken();
        $token->setName('revokeme');
        $token->setUser($user);
        $user->addAccessToken($token);

        $this->assertTrue($user->revokeToken('revokeme'));
        $this->assertCount(0, $user->getAccessTokens());
        $this->assertFalse($user->revokeToken('revokeme'));
    }
}
