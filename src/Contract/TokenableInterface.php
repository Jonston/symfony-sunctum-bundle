<?php

namespace Jonston\SanctumBundle\Contract;

interface TokenableInterface
{
    public function getTokenableId(): string|int;
    public function getTokenableType(): string;
}