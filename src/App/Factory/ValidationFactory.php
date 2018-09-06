<?php

declare(strict_types=1);

namespace App\Factory;

use App\Middleware\ValidationMiddleware;
use App\Model\ValidationModel;
use Psr\Container\ContainerInterface;

class ValidationFactory extends AbstractFactory
{
    public function __invoke(ContainerInterface $container): ValidationMiddleware
    {
        $construct           = parent::__invoke($container);
        $construct['dynamo'] = new ValidationModel();

        return new ValidationMiddleware($construct);
    }
}
