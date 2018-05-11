<?php

declare(strict_types=1);

namespace App\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class MainMiddleware implements MiddlewareInterface
{
    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface  $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$request->hasHeader('user-agent')) {
            return new JsonResponse(
                ['message' => 'Please make sure your request has a User-Agent header.'],
                StatusCodeInterface::STATUS_FORBIDDEN
            );
        }
        $methods = ['POST', 'PATCH'];
        if (in_array($request->getMethod(), $methods)) {
            $json = json_decode($request->getBody()->getContents());

            if (empty($json)) {
                return new JsonResponse(
                    ['message' => 'Problems parsing JSON.'],
                    StatusCodeInterface::STATUS_BAD_REQUEST
                );
            } else if (empty((array)$json)) {
                return new JsonResponse(
                    ['message' => 'Body should be a valid JSON object.'],
                    StatusCodeInterface::STATUS_BAD_REQUEST
                );
            }
        }

        if (!$request->hasHeader('authorization')) {
            return new JsonResponse(
                ['message' => 'Please make sure your request has a Authorization header for Microservices.'],
                StatusCodeInterface::STATUS_FORBIDDEN
            );
        }

        $user = explode('Bearer ', $request->getHeader('authorization')[0]);
        $user = json_decode(base64_decode($user[1]));

        if (empty($user) || empty((array)$user) || !isset($user->user_id)) {
            return new JsonResponse(
                ['message' => 'Authorization header for Microservices is invalid.'],
                StatusCodeInterface::STATUS_BAD_REQUEST
            );
        }

        $response = $handler->handle($request->withAttribute('authorization-user', (array)$user));

        if ($request->hasHeader('X-Correlation-Id')) {
            $response = $response->withAddedHeader('X-Correlation-Id', $request->getHeader('X-Correlation-Id')[0]);
        }

        if (!$request->hasHeader('Access-Control-Allow-Origin')) {
            $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
        }

        $response = $response->withHeader('X-Powered-By', 'Mobingi.com')
                             ->withHeader('X-Content-Type-Options', 'nosniff')
                             ->withHeader('X-Frame-Options', 'deny')
                             ->withHeader('X-XSS-Protection', '1; mode=block')
                             ->withHeader('X-RateLimit-Limit', '500')
                             ->withHeader('X-RateLimit-Remaining', '497')
                             ->withHeader('X-RateLimit-Reset', '1519339170');

        if ($response instanceof JsonResponse) {
            $payload  = $response->getPayload();
            $eTag     = '"' . md5(serialize($payload)) . '"';
            $response = $response->withHeader('ETag', $eTag);

            if ($request->hasHeader('If-None-Match')) {
                $ifNoneMatch = $request->getHeader('If-None-Match')[0];
                if ($ifNoneMatch === $eTag) {
                    return new EmptyResponse(StatusCodeInterface::STATUS_NOT_MODIFIED, $response->getHeaders());
                }
            }
        }

        return $response;
    }

}
