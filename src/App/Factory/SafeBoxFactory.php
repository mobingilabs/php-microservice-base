<?php

namespace App\Factory;

use App\Action\SafeBoxAction;
use App\Filter\SafeBoxFilter;
use App\Model\SafeBoxModel;
use Interop\Container\ContainerInterface;
use Zend\Crypt\BlockCipher;

class SafeBoxFactory extends AbstractFactory
{
    /**
     * @param ContainerInterface $container
     * @return SafeBoxAction
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function __invoke(ContainerInterface $container)
    {
        $construct = parent::__invoke($container);

        $construct['dynamo'] = new SafeBoxModel($construct);
        $blockCipher         = BlockCipher::factory('openssl', ['algo' => 'aes']);
        $blockCipher->setKey(getenv('ENCRYPT_KEY'));
        $construct['blockCipher'] = $blockCipher;
        $construct['filter']      = new SafeBoxFilter();

        return new SafeBoxAction($construct);
    }
}
