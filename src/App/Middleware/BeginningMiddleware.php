<?php

namespace App\Middleware;

use Fig\Http\Message\StatusCodeInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Http\Client;

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
        if (! $request->hasHeader('user-agent')) {
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
            } elseif (empty((array)$json)) {
                return new JsonResponse(
                    ['message' => 'Body should be a valid JSON object.'],
                    StatusCodeInterface::STATUS_BAD_REQUEST
                );
            }
        }

        if (! $request->hasHeader('authorization')) {
            return new JsonResponse(
                ['message' => 'Please make sure your request has a Authorization header.'],
                StatusCodeInterface::STATUS_FORBIDDEN
            );
        }

        $user = $this->decryptToken($request);
        if (! isset($user['user_id'])) {
            return new JsonResponse(
                ['message' => 'Invalid Authorization header.'],
                StatusCodeInterface::STATUS_FORBIDDEN
            );
        }

        $response = $delegate->process($request->withAttribute('user', $user));
        if ($request->hasHeader('X-Correlation-Id')) {
            $response = $response->withAddedHeader('X-Correlation-Id', $request->getHeader('X-Correlation-Id')[0]);
        }

        if (! $request->hasHeader('Access-Control-Allow-Origin')) {
            $response = $response->withAddedHeader('Access-Control-Allow-Origin', '*');
        }

        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     * @return array
     */
    public function decryptToken(ServerRequestInterface $request): array
    {
        $client = new Client();
        $client->setOptions(['timeout' => 60]);
        $client->setUri(getenv('API_DEV_URL') . '/decrypt');
        $client->setMethod('GET');
        $client->setHeaders(['authorization' => $request->getHeader('authorization')[0]]);
        if ($request->hasHeader('X-Correlation-Id')) {
            $client->setHeaders(['X-Correlation-Id' => $request->getHeader('X-Correlation-Id')[0]]);
        }

        $response     = $client->send();
        $user         = (array)json_decode($response->getBody());
        $user['root'] = isset($user['username']) ? false : true;

        return $user;
    }
}
