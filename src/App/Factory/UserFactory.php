<?php

declare(strict_types=1);

namespace App\Factory;

use App\Filter\UserFilter;
use App\Handler\UserHandler;
use App\Model\UserModel;
use Psr\Container\ContainerInterface;

class UserFactory extends AbstractFactory
{
    public function __invoke(ContainerInterface $container): UserHandler
    {
        $construct           = parent::__invoke($container);
        $construct['dynamo'] = new UserModel();
        $construct['filter'] = new UserFilter();

        return new UserHandler($construct);
    }
}
