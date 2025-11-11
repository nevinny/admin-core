<?php
namespace  AdminCore;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AdminCoreBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import(__DIR__ . '/Controller/', 'attribute');
    }
}
