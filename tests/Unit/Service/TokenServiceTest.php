<?php

namespace Jonston\SanctumBundle\Tests\Unit\Service;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Jonston\SanctumBundle\Contract\HasAccessTokensInterface;
use Jonston\SanctumBundle\Entity\AccessToken;
use Jonston\SanctumBundle\Repository\AccessTokenRepository;
use Jonston\SanctumBundle\Service\TokenService;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class TokenServiceTest extends TestCase
{
    private TokenService $service;
    private AccessTokenRepository|MockObject $repo;
    private EntityManagerInterface|MockObject $em;
    private HasAccessTokensInterface|MockObject $owner;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(AccessTokenRepository::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->owner = $this->createMock(HasAccessTokensInterface::class);
        $this->service = new TokenService($this->repo, $this->em, 40, 24);
    }

    /**
     * @throws Exception
     */
    public function testCreateToken(): void
    {
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');
        $result = $this->service->createToken($this->owner);
        $this->assertIsArray($result);
        $this->assertArrayHasKey('accessToken', $result);
        $this->assertArrayHasKey('plainTextToken', $result);
        $this->assertInstanceOf(AccessToken::class, $result['accessToken']);
        $this->assertIsString($result['plainTextToken']);
        $this->assertEquals(40, strlen($result['plainTextToken']));
    }

    public function testFindValidTokenReturnsNullIfNotFoundOrInvalid(): void
    {
        $this->repo->method('findOneBy')->willReturn(null);
        $this->assertNull($this->service->findValidToken('any'));
        $token = $this->createMock(AccessToken::class);
        $token->method('isValid')->willReturn(false);
        $this->repo->method('findOneBy')->willReturn($token);
        $this->assertNull($this->service->findValidToken('any'));
    }

    public function testFindValidTokenReturnsTokenIfValid(): void
    {
        $token = $this->createMock(AccessToken::class);
        $token->method('isValid')->willReturn(true);
        $this->repo->method('findOneBy')->willReturn($token);
        $this->assertSame($token, $this->service->findValidToken('any'));
    }

    public function testUpdateLastUsed(): void
    {
        $token = $this->createMock(AccessToken::class);
        $token->expects($this->once())->method('updateLastUsedAt');
        $this->em->expects($this->once())->method('persist')->with($token);
        $this->em->expects($this->once())->method('flush');
        $this->service->updateLastUsed($token);
    }

    public function testRevokeToken(): void
    {
        $token = $this->createMock(AccessToken::class);
        $this->owner->expects($this->once())->method('removeAccessToken')->with($token);
        $this->em->expects($this->once())->method('remove')->with($token);
        $this->em->expects($this->once())->method('flush');
        $this->service->revokeToken($this->owner, $token);
    }

    public function testRevokeAllTokens(): void
    {
        $token1 = $this->createMock(AccessToken::class);
        $token2 = $this->createMock(AccessToken::class);
        $tokens = new \Doctrine\Common\Collections\ArrayCollection([$token1, $token2]);
        $this->owner->method('getAccessTokens')->willReturn($tokens);
        $this->owner->expects($this->exactly(2))->method('removeAccessToken');
        $this->em->expects($this->exactly(2))->method('remove');
        $this->em->expects($this->once())->method('flush');
        $this->service->revokeAllTokens($this->owner);
    }

    public function testPurgeExpiredTokens(): void
    {
        $this->repo->expects($this->once())->method('removeExpiredTokens')->willReturn(3);
        $this->assertEquals(3, $this->service->purgeExpiredTokens());
    }
}
