<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Zend\Expressive\Application;
use Zend\Expressive\MiddlewareFactory;

/**
 * @param Application $app
 * @param MiddlewareFactory $factory
 * @param ContainerInterface $container
 */
return function (Application $app, MiddlewareFactory $factory, ContainerInterface $container): void {
    $app->get('/', App\Handler\HomeHandler::class, 'home');

    $app->post('/example', App\Handler\ExampleHandler::class, 'exampleCreate');
    $app->get('/example[/{example_id}]', App\Handler\ExampleHandler::class, 'exampleRead');
    $app->patch('/example/{example_id}', App\Handler\ExampleHandler::class, 'exampleUpdate');
    $app->delete('/example/{example_id}', App\Handler\ExampleHandler::class, 'exampleDelete');
};
