<?php

namespace Jonston\SanctumBundle\Tests\Unit\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Jonston\SanctumBundle\Entity\AccessToken;
use Jonston\SanctumBundle\Repository\AccessTokenRepository;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AccessTokenRepositoryTest extends TestCase
{
    private AccessTokenRepository|MockObject $repo;

    protected function setUp(): void
    {
        $this->repo = $this->getMockBuilder(AccessTokenRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['save', 'remove', 'findOneByToken', 'createQueryBuilder'])
            ->getMock();
    }

    public function testSaveAndRemove(): void
    {
        $token = new AccessToken();
        $this->repo->expects($this->once())->method('save')->with($token, true);
        $this->repo->save($token, true);
        $this->repo->expects($this->once())->method('remove')->with($token, true);
        $this->repo->remove($token, true);
    }

    public function testFindOneByToken(): void
    {
        $token = new AccessToken();
        $this->repo->method('findOneByToken')->with('hashed')->willReturn($token);
        $result = $this->repo->findOneByToken('hashed');
        $this->assertInstanceOf(AccessToken::class, $result);
    }

    public function testRemoveExpiredTokens(): void
    {
        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->method('delete')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $query = $this->getMockBuilder('Doctrine\ORM\Query')
            ->disableOriginalConstructor()
            ->getMock();
        $query->method('execute')->willReturn(2);
        $qb->method('getQuery')->willReturn($query);
        $this->repo->method('createQueryBuilder')->willReturn($qb);
        $this->assertEquals(2, $this->repo->removeExpiredTokens());
    }
}
