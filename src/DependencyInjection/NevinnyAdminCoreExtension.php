<?php
namespace Nevinny\AdminCoreBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class NevinnyAdminCoreExtension extends Extension implements PrependExtensionInterface
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
        // Регистрация Entity для Doctrine
        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'NevinnyAdminCoreBundle' => [  // ← Изменено название маппинга
                        'is_bundle' => false,
                        'type' => 'attribute',
                        'dir' => dirname(__DIR__) . '/Entity',
                        'prefix' => 'Nevinny\\AdminCoreBundle\\Entity',  // ← ИСПРАВЛЕНО!
                        'alias' => 'NevinnyAdminCore',  // ← Изменен alias
                    ],
                ],
            ],
        ]);

        // Регистрация Twig шаблонов
        $container->prependExtensionConfig('twig', [
            'paths' => [
                dirname(__DIR__) . '/Resources/views' => 'NevinnyAdminCore',  // ← Изменен alias
            ],
        ]);
    }
}