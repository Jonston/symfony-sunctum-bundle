<?php

namespace Jonston\SanctumBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AccessTokenMappingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        // No automatic mapping or listener registration performed here.
        // Mapping is now expected to be configured via the application's doctrine config
        // using the '%sanctum.owner_class%' parameter published by the bundle recipe.
    }
}
