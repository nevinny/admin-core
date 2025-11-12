<?php
namespace Nevinny\AdminCoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AdminCoreExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'AdminCore' => [
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => dirname(__DIR__) . '/Entity',
                        'prefix' => 'AdminCore\Entity',
                        'alias' => 'AdminCore',
                    ],
                ],
            ],
        ]);

        $container->prependExtensionConfig('twig', [
            'paths' => [
                dirname(__DIR__) . '/Resources/views' => 'AdminCore',
            ],
        ]);
    }
}
