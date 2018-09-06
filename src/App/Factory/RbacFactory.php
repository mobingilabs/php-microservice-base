<?php

declare(strict_types=1);

namespace App\Factory;

use App\Handler\RbacHandler;
use App\Model\RbacModel;
use Psr\Container\ContainerInterface;

class RbacFactory extends AbstractFactory
{
    public function __invoke(ContainerInterface $container): RbacHandler
    {
        $construct           = parent::__invoke($container);
        $construct['dynamo'] = new RbacModel();

        return new RbacHandler($construct);
    }
}
