<?php

declare(strict_types=1);

namespace AppTest\Handler;

use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Zend\Expressive\Router\RouterInterface;

class HomeHandlerTest extends TestCase
{
    /** @var RouterInterface */
    protected $router;

    /** @var ContainerInterface|ObjectProphecy */
    protected $container;

    protected function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->router = $this->prophesize(RouterInterface::class);
        $this->container->get(RouterInterface::class)->willReturn($this->router);
    }

    public function testExample()
    {
        $this->assertTrue(true);
    }
}
