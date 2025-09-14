<?php

namespace Jonston\SanctumBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class SanctumExtension extends Extension implements PrependExtensionInterface
{
    public function prepend(ContainerBuilder $container): void
    {
        $configuration = new SanctumConfiguration();
        $configs = $container->getExtensionConfig($this->getAlias());
        $config = $this->processConfiguration($configuration, $configs);

        if (!empty($config['owner_class'])) {
            $container->prependExtensionConfig('doctrine', [
                'orm' => [
                    'resolve_target_entities' => [
                        'Jonston\\SanctumBundle\\Contract\\HasAccessTokensInterface' => $config['owner_class'],
                    ],
                ],
            ]);
        }
    }

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
