<?php

namespace App\Action;

use Fig\Http\Message\StatusCodeInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\Response\TextResponse;
use Zend\Expressive\Router\RouteResult;

class DynamoDbAction implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $route     = $request->getAttribute(RouteResult::class);
        $routeName = $route->getMatchedRoute()->getName();
//        return new EmptyResponse();
//        return new EmptyResponse(StatusCodeInterface::STATUS_ACCEPTED);
//        return new EmptyResponse(StatusCodeInterface::STATUS_ACCEPTED, ['Location' => 'api/ping']);

//        return new RedirectResponse('/api/ping');
//        return new RedirectResponse('/api/ping', StatusCodeInterface::STATUS_PERMANENT_REDIRECT);
//        return new RedirectResponse(
//            '/api/ping',
//            StatusCodeInterface::STATUS_TEMPORARY_REDIRECT,
//            ['X-ORIGINAL_URI' =>  'dynamo-db']
//        );

//        return new TextResponse('Hello, world!');
//        return new HtmlResponse('<h1>Hello Zend!</h1>');
        return new JsonResponse(['$routeName' => $routeName]);
    }
}