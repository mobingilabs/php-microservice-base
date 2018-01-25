<?php

namespace App\Factory;

use Interop\Container\ContainerInterface;
use Zend\Expressive\Application;

class AbstractFactory
{
    /**
     * @param ContainerInterface $container
     * @return array
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container)
    {
        $injection = [
            'app'    => $container->get(Application::class),
            'config' => $container->get('config'),
        ];

        return $injection;
    }
}