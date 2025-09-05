<?php

namespace Jonston\SanctumBundle;

use Jonston\SanctumBundle\DependencyInjection\SanctumExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SanctumBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new SanctumExtension();
    }
}