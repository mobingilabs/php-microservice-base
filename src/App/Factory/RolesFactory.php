<?php

declare(strict_types=1);

namespace App\Factory;

use App\Handler\RolesHandler;
use App\Model\RolesModel;
use Psr\Container\ContainerInterface;

class RolesFactory extends AbstractFactory
{
    public function __invoke(ContainerInterface $container): RolesHandler
    {
        $construct           = parent::__invoke($container);
        $construct['dynamo'] = new RolesModel();

        return new RolesHandler($construct);
    }
}
