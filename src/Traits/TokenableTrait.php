<?php

namespace Jonston\SanctumBundle\Traits;

trait TokenableTrait
{
    protected string|int $id;

    public function getId(): string|int
    {
        return $this->id;
    }

    public function getTokenableId(): string|int
    {
        return $this->getId();
    }

    public function getTokenableType(): string
    {
        return static::class;
    }
}