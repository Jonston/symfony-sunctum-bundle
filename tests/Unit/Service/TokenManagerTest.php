<?php

namespace Jonston\SanctumBundle\Tests\Unit\Service;

use Random\RandomException;
use Jonston\SanctumBundle\Entity\PersonalAccessToken;
use Jonston\SanctumBundle\Contract\TokenableInterface;
use Jonston\SanctumBundle\Repository\PersonalAccessTokenRepository;
use Jonston\SanctumBundle\Service\TokenService;
use PHPUnit\Framework\TestCase;

class TokenManagerTest extends TestCase
{
    private PersonalAccessTokenRepository $repository;
    private TokenService $tokenService;
    private TokenableInterface $tokenable;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PersonalAccessTokenRepository::class);
        $this->tokenService = new TokenService($this->repository);

        $this->tokenable = $this->createMock(TokenableInterface::class);
        $this->tokenable->method('getTokenableId')->willReturn('123');
        $this->tokenable->method('getTokenableType')->willReturn('App\\Entity\\User');
    }

    /**
     * @throws RandomException
     */
    public function testCreateToken(): void
    {
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(PersonalAccessToken::class));

        $result = $this->tokenService->createToken($this->tokenable, 'Test Token');

        $this->assertInstanceOf(PersonalAccessToken::class, $result);
        $this->assertEquals('Test Token', $result->getName());
        $this->assertEquals('123', $result->getTokenableId());
        $this->assertEquals('App\\Entity\\User', $result->getTokenableType());
        $this->assertNotNull($result->getToken());
        $this->assertInstanceOf(\DateTimeImmutable::class, $result->getCreatedAt());
    }

    public function testFindValidToken(): void
    {
        $tokenHash = 'test-token-hash';
        $hashedToken = hash('sha256', $tokenHash);

        $token = new PersonalAccessToken();
        $token->setName('Test Token');
        $token->setExpiresAt(null); // не истёк

        $this->repository->expects($this->once())
            ->method('findByToken')
            ->with($hashedToken)
            ->willReturn($token);

        $result = $this->tokenService->findValidToken($tokenHash);

        $this->assertSame($token, $result);
    }

    public function testFindValidTokenReturnsNullForExpiredToken(): void
    {
        $tokenHash = 'test-token-hash';
        $hashedToken = hash('sha256', $tokenHash);

        $token = new PersonalAccessToken();
        $token->setExpiresAt(new \DateTimeImmutable('-1 day')); // истёк

        $this->repository->expects($this->once())
            ->method('findByToken')
            ->with($hashedToken)
            ->willReturn($token);

        $result = $this->tokenService->findValidToken($tokenHash);

        $this->assertNull($result);
    }

    public function testFindValidTokenReturnsNullWhenTokenNotFound(): void
    {
        $tokenHash = 'test-token-hash';
        $hashedToken = hash('sha256', $tokenHash);

        $this->repository->expects($this->once())
            ->method('findByToken')
            ->with($hashedToken)
            ->willReturn(null);

        $result = $this->tokenService->findValidToken($tokenHash);

        $this->assertNull($result);
    }

    public function testGetTokenable(): void
    {
        $token = new PersonalAccessToken();
        $token->setTokenableType('App\\Entity\\User');
        $token->setTokenableId('123');

        $user = $this->createMock(TokenableInterface::class);

        $this->repository->expects($this->once())
            ->method('findTokenable')
            ->with('App\\Entity\\User', '123')
            ->willReturn($user);

        $result = $this->tokenService->getTokenable($token);

        $this->assertSame($user, $result);
    }

    public function testGetTokenableReturnsNullForNonTokenableObject(): void
    {
        $token = new PersonalAccessToken();
        $token->setTokenableType('App\\Entity\\User');
        $token->setTokenableId('123');

        $nonTokenableObject = new \stdClass();

        $this->repository->expects($this->once())
            ->method('findTokenable')
            ->with('App\\Entity\\User', '123')
            ->willReturn($nonTokenableObject);

        $result = $this->tokenService->getTokenable($token);

        $this->assertNull($result);
    }

    public function testGetTokensFor(): void
    {
        $tokens = [
            new PersonalAccessToken(),
            new PersonalAccessToken(),
        ];

        $this->repository->expects($this->once())
            ->method('findByTokenable')
            ->with('App\\Entity\\User', '123')
            ->willReturn($tokens);

        $result = $this->tokenService->getTokensFor($this->tokenable);

        $this->assertEquals($tokens, $result);
    }

    public function testRevokeToken(): void
    {
        $token = new PersonalAccessToken();

        $this->repository->expects($this->once())
            ->method('remove')
            ->with($token);

        $this->tokenService->revokeToken($token);
    }

    public function testUpdateLastUsed(): void
    {
        $tokens = [
            new PersonalAccessToken(),
            new PersonalAccessToken(),
        ];

        $this->repository->expects($this->once())
            ->method('findByTokenable')
            ->with('App\\Entity\\User', '123')
            ->willReturn($tokens);

        $this->repository->expects($this->exactly(2))
            ->method('save')
            ->with($this->isInstanceOf(PersonalAccessToken::class));

        $this->tokenService->updateLastUsed($this->tokenable);

        // Проверяем, что lastUsedAt был обновлен
        foreach ($tokens as $token) {
            $this->assertInstanceOf(\DateTimeImmutable::class, $token->getLastUsedAt());
        }
    }

    /**
     * @throws RandomException
     */
    public function testGeneratePlainToken(): void
    {
        $token1 = $this->tokenService->generatePlainToken();
        $token2 = $this->tokenService->generatePlainToken();

        $this->assertIsString($token1);
        $this->assertIsString($token2);
        $this->assertNotEquals($token1, $token2);
        $this->assertEquals(64, strlen($token1)); // 32 bytes = 64 hex chars
        $this->assertEquals(64, strlen($token2));
    }
}
