<?php

declare(strict_types=1);

namespace App\Factory;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;

class AbstractFactory
{
    /**
     * @param ContainerInterface $container
     *
     * @return array
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