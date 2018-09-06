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

    $app->post('/inspect', App\Handler\RbacHandler::class, 'inspect');
    $app->get('/actions', App\Handler\RbacHandler::class, 'actions');

    $app->patch('/roles/user/{username}', App\Handler\RolesHandler::class, 'rolesUserUpdate');
    $app->delete('/roles/user/{username}', App\Handler\RolesHandler::class, 'rolesUserDelete');

    $app->post('/roles', App\Handler\RolesHandler::class, 'rolesCreate');
    $app->get('/roles[/{role_id}]', App\Handler\RolesHandler::class, 'rolesRead');
    $app->patch('/roles/{role_id}', App\Handler\RolesHandler::class, 'rolesUpdate');
    $app->delete('/roles/{role_id}', App\Handler\RolesHandler::class, 'rolesDelete');
};
