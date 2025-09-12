<?php

namespace Jonston\SanctumBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SanctumExtension extends Extension implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sanctum');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode('owner_class')
                ->isRequired()
                ->cannotBeEmpty()
                ->info('The class that will own the access tokens')
            ->end()
            ->integerNode('token_length')
                ->defaultValue(40)
                ->min(32)
                ->info('Length of the generated token')
            ->end()
            ->integerNode('default_expiration_hours')
                ->defaultNull()
                ->info('Default token expiration in hours (null for no expiration)')
            ->end()
        ->end();

        return $treeBuilder;
    }

    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sanctum.owner_class', $config['owner_class']);
        $container->setParameter('sanctum.token_length', $config['token_length']);
        $container->setParameter('sanctum.default_expiration_hours', $config['default_expiration_hours']);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');
    }
}