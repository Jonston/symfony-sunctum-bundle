<?php

namespace Jonston\SanctumBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class SanctumConfiguration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('sanctum');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->children()
            ->scalarNode('owner_class')
                ->defaultNull()
                ->info('The class that will own the access tokens (optional)')
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
}
