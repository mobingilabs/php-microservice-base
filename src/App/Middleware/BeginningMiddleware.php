<?php

namespace App\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;

class BeginningMiddleware implements MiddlewareInterface
{

    /**
     * Process an incoming server request and return a response, optionally delegating
     * to the next middleware component to create the response.
     *
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $methods = ['POST', 'PATCH'];
        if (in_array($request->getMethod(), $methods)) {
            $json = json_decode($request->getBody()->getContents());

            if (empty($json)) {
                return new JsonResponse(['message' => 'Problems parsing JSON'], StatusCodeInterface::STATUS_BAD_REQUEST);
            } elseif (empty((array)$json)) {
                return new JsonResponse(
                    ['message' => 'Body should be a valid JSON object'],
                    StatusCodeInterface::STATUS_BAD_REQUEST
                );
            }
        }

        return $delegate->process($request);
    }
}
