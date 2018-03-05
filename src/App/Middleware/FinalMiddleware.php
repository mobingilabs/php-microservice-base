<?php

namespace App\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class FinalMiddleware implements MiddlewareInterface
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
        /** @var Response $response */
        $response = $delegate->process($request);

        $response = $response->withHeader('X-Powered-By', 'Mobingi.com')
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
