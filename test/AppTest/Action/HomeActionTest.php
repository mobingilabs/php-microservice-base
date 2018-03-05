<?php

namespace AppTest\Action;

use PHPUnit\Framework\TestCase;
use Zend\Expressive\Router\RouterInterface;

class HomeActionTest extends TestCase
{
    /** @var RouterInterface */
    protected $router;

    protected function setUp()
    {
        $this->router = $this->prophesize(RouterInterface::class);
    }

    public function testExample()
    {
        $this->assertTrue(true);
    }
}
