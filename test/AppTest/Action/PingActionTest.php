<?php

namespace AppTest\Action;

use App\Action\EnvVarsAction;
use Interop\Http\ServerMiddleware\DelegateInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class PingActionTest extends TestCase
{
    public function testResponse()
    {
        $pingAction = new EnvVarsAction();
        $response   = $pingAction->process(
            $this->prophesize(ServerRequestInterface::class)->reveal(),
            $this->prophesize(DelegateInterface::class)->reveal()
        );

        $json = json_decode((string)$response->getBody());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertTrue(isset($json->ack));
    }
}
