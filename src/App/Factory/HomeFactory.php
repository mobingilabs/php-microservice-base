<?php

namespace App\Factory;

use App\Action\HomeAction;
use Interop\Container\ContainerInterface;

class HomeFactory extends AbstractFactory
{
    /**
     * @param ContainerInterface $container
     * @return HomeAction
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container)
    {
        $construct = parent::__invoke($container);

        return new HomeAction($construct);
    }
}
