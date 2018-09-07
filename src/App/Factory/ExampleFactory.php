<?php

declare(strict_types=1);

namespace App\Factory;

use App\Handler\ExampleHandler;
use App\Model\ExampleModel;
use Psr\Container\ContainerInterface;

class ExampleFactory extends AbstractFactory
{
    public function __invoke(ContainerInterface $container): ExampleHandler
    {
        $construct           = parent::__invoke($container);
        $construct['dynamo'] = new ExampleModel();

        return new ExampleHandler($construct);
    }
}
