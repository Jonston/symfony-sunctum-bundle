<?php

namespace Jonston\SanctumBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SanctumExtension extends Extension
{
    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new SanctumConfiguration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sanctum.owner_class', $config['owner_class']);
        $container->setParameter('sanctum.token_length', $config['token_length']);
        $container->setParameter('sanctum.default_expiration_hours', $config['default_expiration_hours']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}