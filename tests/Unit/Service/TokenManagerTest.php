<?php

namespace Jonston\SanctumBundle\Tests\Unit\Service;

use Jonston\SanctumBundle\Entity\PersonalAccessToken;
use Jonston\SanctumBundle\Repository\PersonalAccessTokenRepository;
use Jonston\SanctumBundle\Service\TokenManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

class TokenManagerTest extends TestCase
{
    private PersonalAccessTokenRepository $repository;
    private TokenManager $tokenManager;
    private UserInterface $user;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PersonalAccessTokenRepository::class);
        $this->tokenManager = new TokenManager($this->repository);

        $this->user = $this->createMock(UserInterface::class);
        $this->user->method('getUserIdentifier')->willReturn('123');
    }

    public function testCreateToken(): void
    {
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(PersonalAccessToken::class));

        $result = $this->tokenManager->createToken($this->user, 'Test Token');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('entity', $result);
        $this->assertIsString($result['token']);
        $this->assertInstanceOf(PersonalAccessToken::class, $result['entity']);
        $this->assertEquals('Test Token', $result['entity']->getName());
        $this->assertEquals(123, $result['entity']->getUserId());
    }

    public function testCreateTokenWithExpiration(): void
    {
        $expiresAt = new \DateTimeImmutable('+30 days');

        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(PersonalAccessToken::class));

        $result = $this->tokenManager->createToken($this->user, 'Expiring Token', $expiresAt);

        $this->assertEquals($expiresAt, $result['entity']->getExpiresAt());
    }

    public function testRevokeToken(): void
    {
        $token = new PersonalAccessToken();

        $this->repository->expects($this->once())
            ->method('remove')
            ->with($token);

        $this->tokenManager->revokeToken($token);
    }

    public function testRevokeAllTokensForUser(): void
    {
        $tokens = [
            new PersonalAccessToken(),
            new PersonalAccessToken(),
        ];

        $this->repository->expects($this->once())
            ->method('findByUser')
            ->with(123)
            ->willReturn($tokens);

        $this->repository->expects($this->exactly(2))
            ->method('remove')
            ->with($this->callback(function ($token) use ($tokens) {
                return in_array($token, $tokens, true);
            }));

        $this->tokenManager->revokeAllTokensForUser($this->user);
    }

    public function testGetUserTokens(): void
    {
        $tokens = [
            new PersonalAccessToken(),
            new PersonalAccessToken(),
        ];

        $this->repository->expects($this->once())
            ->method('findActiveByUser')
            ->with(123)
            ->willReturn($tokens);

        $result = $this->tokenManager->getUserTokens($this->user);

        $this->assertEquals($tokens, $result);
    }

    public function testCleanupExpiredTokens(): void
    {
        $this->repository->expects($this->once())
            ->method('removeExpired')
            ->willReturn(5);

        $result = $this->tokenManager->cleanupExpiredTokens();

        $this->assertEquals(5, $result);
    }
}