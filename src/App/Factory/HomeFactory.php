<?php

declare(strict_types=1);

namespace App\Factory;

use App\Handler\HomeHandler;
use Psr\Container\ContainerInterface;

class HomeFactory extends AbstractFactory
{

    /**
     * @param ContainerInterface $container
     *
     * @return HomeHandler|array
     */
    public function __invoke(ContainerInterface $container)
    {
        $construct = parent::__invoke($container);

        return new HomeHandler($construct);
    }
}
