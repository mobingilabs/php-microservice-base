<?php

namespace App\Factory;

use App\Action\DynamoDbAction;
use Aws\Sdk;
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

        $sdk = new Sdk([
            'region'      => getenv('DYNAMO_REGION'),
            'version'     => getenv('DYNAMO_VERSION'),
            'credentials' => [
                'key'    => getenv('DYNAMO_KEY'),
                'secret' => getenv('DYNAMO_SECRET'),
            ]
        ]);

        $construct['dynamo'] = $sdk->createDynamoDb();

        return new DynamoDbAction($construct);
    }
}
