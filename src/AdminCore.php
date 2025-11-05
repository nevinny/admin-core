<?php
namespace  AdminCore;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class AdminCore extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}