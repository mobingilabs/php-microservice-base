<?php

declare(strict_types=1);

namespace App\Factory;

use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;
use Zend\Http\Client;

class AbstractFactory
{
    /**
     * @param ContainerInterface $container
     *
     * @return array
     */
    public function __invoke(ContainerInterface $container)
    {
        $client = new Client();
        $client->setOptions([
                                'timeout'   => 30,
                                'useragent' => 'RBAC-Request',
                            ]);
        $injection = [
            'app'    => $container->get(Application::class),
            'config' => $container->get('config'),
            'client' => $client,
        ];

        return $injection;
    }
}