<?php

namespace Jonston\SanctumBundle\Tests\Unit\Traits;

use Jonston\SanctumBundle\Contract\TokenableInterface;
use Jonston\SanctumBundle\Traits\TokenableTrait;
use PHPUnit\Framework\TestCase;

class TestTokenableEntity implements TokenableInterface
{
    use TokenableTrait;

    public function __construct(int $id = 123)
    {
        $this->id = $id;
    }
}

class TokenableTraitTest extends TestCase
{
    public function testGetTokenableId(): void
    {
        $entity = new TestTokenableEntity(456);

        $this->assertEquals(456, $entity->getTokenableId());
        $this->assertEquals(456, $entity->getId());
    }

    public function testGetTokenableType(): void
    {
        $entity = new TestTokenableEntity();

        $this->assertEquals(TestTokenableEntity::class, $entity->getTokenableType());
    }

    public function testTokenableIdWithStringId(): void
    {
        $entity = new class implements TokenableInterface {
            use TokenableTrait;

            public function __construct()
            {
                $this->id = 'string-id-123';
            }
        };

        $this->assertEquals('string-id-123', $entity->getTokenableId());
        $this->assertEquals('string-id-123', $entity->getId());
    }
}
