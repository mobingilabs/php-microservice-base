<?php

namespace App\Action;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class HomeAction extends AbstractAction
{
    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return JsonResponse
     */
    public function home(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $routes = [];
        foreach ($this->app->getRoutes() as $route) {
            $methods       = implode('|', $route->getAllowedMethods());
            $name          = "{$methods}:{$route->getName()}";
            $routes[$name] = $this->headerLocation($route->getPath());
        }

        return new JsonResponse($routes);
    }
}
