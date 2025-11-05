<?php
namespace AdminCore\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AdminCoreExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Загрузка сервисов
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );
        $loader->load('services.yaml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Автоматическая регистрация Doctrine маппинга
        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'mappings' => [
                    'AdminCore' => [
                        'is_bundle' => false,
                        'type' => 'attribute', // или 'annotation'
                        'dir' => dirname(__DIR__) . '/Entity',
                        'prefix' => 'AdminCore\Entity',
                        'alias' => 'AdminCore',
                    ],
                ],
            ],
        ]);
    }
}