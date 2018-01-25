<?php

namespace App\Factory;

use App\Action\DynamoDbAction;
use Interop\Container\ContainerInterface;

class DynamoDbFactory extends AbstractFactory
{
    /**
     * @param ContainerInterface $container
     * @return DynamoDbAction
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container)
    {
        $construct = parent::__invoke($container);

        return new DynamoDbAction($construct);
    }
}
