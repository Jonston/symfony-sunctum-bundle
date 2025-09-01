<?php

namespace Jonston\SanctumBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Jonston\SanctumBundle\DependencyInjection\SanctumExtension;

class SanctumBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
    }

    public function getContainerExtension()
    {
        return new SanctumExtension();
    }
}