<?php

declare(strict_types=1);

namespace App\Handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class HomeHandler extends AbstractHandler
{
    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function home(ServerRequestInterface $request): ResponseInterface
    {
        $data = [];
        foreach ($this->app->getRoutes() as $route) {
            $methods     = implode('|', $route->getAllowedMethods());
            $name        = "{$methods}:{$route->getName()}";
            $data[$name] = $this->headerLocation($route->getPath());
        }

        return new JsonResponse($data);
    }
}
