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
    public function indexGet(ServerRequestInterface $request, DelegateInterface $delegate)
    {

//        $route     = $request->getAttribute(RouteResult::class);
//        $routeName = $route->getMatchedRoute()->getName();
//
//        return new EmptyResponse();
//        return new EmptyResponse(StatusCodeInterface::STATUS_ACCEPTED);
//        return new EmptyResponse(StatusCodeInterface::STATUS_ACCEPTED, ['Location' => 'api/ping']);
//
//        return new RedirectResponse('/api/ping');
//        return new RedirectResponse('/api/ping', StatusCodeInterface::STATUS_PERMANENT_REDIRECT);
//        return new RedirectResponse(
//            '/api/ping',
//            StatusCodeInterface::STATUS_TEMPORARY_REDIRECT,
//            ['X-ORIGINAL_URI' => 'dynamo-db']
//        );
//
//        return new TextResponse('Hello, world!');
//
//        return new HtmlResponse('<h1>Hello Zend!</h1>');

        $routes = [];
        foreach ($this->app->getRoutes() as $route) {
            $methods       = implode('|', $route->getAllowedMethods());
            $name          = "{$methods}:{$route->getName()}";
            $routes[$name] = 'http://' . $_SERVER['HTTP_HOST'] . $route->getPath();
        }

        return new JsonResponse($routes);
    }
}
