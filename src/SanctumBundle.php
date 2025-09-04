<?php

namespace Jonston\SanctumBundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Jonston\SanctumBundle\DependencyInjection\SanctumExtension;

class SanctumBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new SanctumExtension();
    }
}