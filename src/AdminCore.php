<?php
namespace  AdminCore;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AdminCore extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/Resources/config')
        );
        $loader->load('services.yaml');
    }

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}