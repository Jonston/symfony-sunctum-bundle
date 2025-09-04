<?php

namespace Jonston\SanctumBundle\Tests\Unit\Service;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Jonston\SanctumBundle\Entity\PersonalAccessToken;
use Jonston\SanctumBundle\Entity\TokenizableUser;
use Jonston\SanctumBundle\Repository\PersonalAccessTokenRepository;
use Jonston\SanctumBundle\Service\TokenHasher;
use Jonston\SanctumBundle\Service\TokenManager;
use PHPUnit\Framework\TestCase;

class TokenManagerTest extends TestCase
{
    private PersonalAccessTokenRepository $repository;
    private TokenManager $tokenManager;
    private TokenizableUser $user;
    private EntityManagerInterface $em;
    private TokenHasher $tokenHasher;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(PersonalAccessTokenRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->tokenHasher = $this->createMock(TokenHasher::class);
        $this->tokenManager = new TokenManager($this->repository, $this->em, $this->tokenHasher);

        $this->user = $this->createMock(TokenizableUser::class);
        $this->user->method('getUserIdentifier')->willReturn('123');
    }

    /**
     * @throws Exception
     */
    public function testCreateToken(): void
    {
        $plainToken = 'plain-text-token';
        $hashedToken = 'hashed-token';

        $this->tokenHasher->expects($this->once())
            ->method('generatePlainToken')
            ->willReturn($plainToken);

        $this->tokenHasher->expects($this->once())
            ->method('hashToken')
            ->with($plainToken)
            ->willReturn($hashedToken);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PersonalAccessToken::class));

        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->tokenManager->createToken($this->user, 'Test Token');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('entity', $result);
        $this->assertEquals($plainToken, $result['token']);
        $this->assertInstanceOf(PersonalAccessToken::class, $result['entity']);
        $this->assertEquals('Test Token', $result['entity']->getName());
        $this->assertEquals($this->user, $result['entity']->getUser());
    }

    /**
     * @throws Exception
     */
    public function testCreateTokenWithExpiration(): void
    {
        $expiresAt = new DateTimeImmutable('+30 days');
        $plainToken = 'plain-text-token';
        $hashedToken = 'hashed-token';

        $this->tokenHasher->method('generatePlainToken')->willReturn($plainToken);
        $this->tokenHasher->method('hashToken')->willReturn($hashedToken);

        $this->em->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(PersonalAccessToken::class));

        $this->em->expects($this->once())
            ->method('flush');

        $result = $this->tokenManager->createToken($this->user, 'Expiring Token', $expiresAt);

        $this->assertEquals($expiresAt, $result['entity']->getExpiresAt());
    }

    public function testVerifyToken(): void
    {
        $plainToken = 'plain-token';
        $hashedToken = 'hashed-token';

        $this->tokenHasher->expects($this->once())
            ->method('verifyToken')
            ->with($plainToken, $hashedToken)
            ->willReturn(true);

        $result = $this->tokenManager->verifyToken($plainToken, $hashedToken);

        $this->assertTrue($result);
    }

    public function testHashToken(): void
    {
        $plainToken = 'plain-token';
        $expectedHash = 'expected-hash';

        $this->tokenHasher->expects($this->once())
            ->method('hashToken')
            ->with($plainToken)
            ->willReturn($expectedHash);

        $result = $this->tokenManager->hashToken($plainToken);

        $this->assertEquals($expectedHash, $result);
    }

    public function testRevokeToken(): void
    {
        $token = new PersonalAccessToken();

        $this->em->expects($this->once())
            ->method('remove')
            ->with($token);
        $this->em->expects($this->once())
            ->method('flush');

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
            ->with('123')
            ->willReturn($tokens);

        $calls = [];
        $this->em->expects($this->exactly(2))
            ->method('remove')
            ->willReturnCallback(function ($token) use (&$calls) {
                $calls[] = $token;
            });
        $this->em->expects($this->exactly(2))
            ->method('flush');

        $this->tokenManager->revokeAllTokensForUser($this->user);

        $this->assertSame($tokens, $calls);
    }

    public function testGetUserTokens(): void
    {
        $tokens = [
            new PersonalAccessToken(),
            new PersonalAccessToken(),
        ];

        $this->repository->expects($this->once())
            ->method('findActiveByUser')
            ->with('123')
            ->willReturn($tokens);

        $result = $this->tokenManager->getUserTokens($this->user);

        $this->assertEquals($tokens, $result);
    }

    public function testCleanupExpiredTokens(): void
    {
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $query = $this->createMock(\Doctrine\ORM\AbstractQuery::class);
        $this->em->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($qb);
        $qb->expects($this->once())
            ->method('delete')
            ->with(PersonalAccessToken::class, 't')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('where')
            ->with('t.expiresAt IS NOT NULL AND t.expiresAt <= :now')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('now', $this->isInstanceOf(DateTimeImmutable::class))
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('getQuery')
            ->willReturn($query);
        $query->expects($this->once())
            ->method('execute')
            ->willReturn(5);

        $result = $this->tokenManager->cleanupExpiredTokens();

        $this->assertEquals(5, $result);
    }
}
