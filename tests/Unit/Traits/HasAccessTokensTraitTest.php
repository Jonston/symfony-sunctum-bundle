<?php

namespace Jonston\SanctumBundle\Tests\Unit\Traits;

use Doctrine\Common\Collections\ArrayCollection;
use Jonston\SanctumBundle\Entity\AccessToken;
use Jonston\SanctumBundle\Traits\HasAccessTokensTrait;
use Jonston\SanctumBundle\Contract\HasAccessTokensInterface;
use PHPUnit\Framework\TestCase;

class HasAccessTokensTraitTest extends TestCase
{
    private HasAccessTokensInterface $user;

    protected function setUp(): void
    {
        $this->user = new class implements HasAccessTokensInterface {
            use HasAccessTokensTrait;
            private $accessTokens;
            public function __construct() { $this->accessTokens = new ArrayCollection(); }
            public function getId(): int|string { return 1; }
        };
    }

    public function testAddAndHasAccessToken(): void
    {
        $token = new AccessToken();
        $this->user->addAccessToken($token);
        $this->assertTrue($this->user->hasAccessToken($token));
    }

    public function testRemoveAccessToken(): void
    {
        $token = new AccessToken();
        $this->user->addAccessToken($token);
        $this->user->removeAccessToken($token);
        $this->assertFalse($this->user->hasAccessToken($token));
    }

    public function testGetAccessTokens(): void
    {
        $token1 = new AccessToken();
        $token2 = new AccessToken();
        $this->user->addAccessToken($token1);
        $this->user->addAccessToken($token2);
        $tokens = $this->user->getAccessTokens();
        $this->assertInstanceOf(ArrayCollection::class, $tokens);
        $this->assertCount(2, $tokens);
    }

    public function testRevokeAllTokens(): void
    {
        $token1 = new AccessToken();
        $token2 = new AccessToken();
        $this->user->addAccessToken($token1);
        $this->user->addAccessToken($token2);
        $this->user->removeAccessToken($token1);
        $this->user->removeAccessToken($token2);
        $this->assertCount(0, $this->user->getAccessTokens());
    }
}
