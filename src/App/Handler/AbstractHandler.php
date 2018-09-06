<?php

declare(strict_types=1);

namespace App\Handler;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Expressive\Router\RouteResult;

class AbstractHandler implements RequestHandlerInterface
{
    /**
     * @var \Zend\Expressive\Application
     */
    public $app;

    /**
     * @var array All application configurations
     */
    public $config = [];


    /**
     * @var array User information
     */
    public $user = [];

    /**
     * @var \Zend\Http\Client
     */
    public $client;

    /**
     * AbstractAction constructor.
     *
     * @param array $injection
     */
    public function __construct(array $injection)
    {
        foreach ($injection as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /**
     * Generate Header Location
     *
     * @param $path
     *
     * @return string
     */
    public function headerLocation($path): String
    {
        return "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}{$path}";
    }

    /**
     * Handle the request and return a response.
     *
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $result   = $request->getAttribute(RouteResult::class);
        $function = $result->getMatchedRouteName();

        if (!method_exists($this, $function)) {
            return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
        }

        $headers = ['X-User' => $request->getHeader('X-User')[0]];
        if ($request->hasHeader('authorization')) {
            $headers['authorization'] = $request->getHeader('authorization')[0];
        }
        $this->client->setHeaders($headers);

        $this->user = $request->getAttribute('authorization-user');

        return $this->{$function}($request);
    }

    /**
     * Standard error response for microservice problems
     *
     * @return JsonResponse
     */
    public function errorResponse(): JsonResponse
    {
        $this->log("[INTERNAL_ERROR] - {$this->client->getLastRawResponse()}");

        return new JsonResponse(
            ['message' => 'Internal Service Error, Please try again after.'],
            StatusCodeInterface::STATUS_INTERNAL_SERVER_ERROR
        );
    }

    public function log($message)
    {
        if (getenv('LOGS_IN_FILE') === '1') {
            error_log($message . PHP_EOL, 3, './data/php.log');
        }

        error_log("||{$message}||");
    }

    /**
     * @param string $uri
     * @param string $method
     * @param string $rawBody
     *
     * @return bool|\Zend\Http\Response
     */
    public function send($uri, $method = 'GET', $rawBody = '')
    {
        $responseTime = microtime(true);
        $this->log("[SEND_START] - {$method} - {$uri} - {$rawBody}");

        $this->client->setUri($uri);
        $this->client->setMethod($method);
        if (!empty($rawBody)) {
            $this->client->setRawBody($rawBody);
            $this->client->setEncType('application/json');
        }

        try {
            $response = $this->client->send();
        } catch (\Exception $e) {
            $this->log("[SEND_ERROR] - {$e->getMessage()}");
            return false;
        }

        if ($response->getStatusCode() === 500) {
            return false;
        }

        if (!empty($response->getBody() && is_null(json_decode($response->getBody())))) {
            return false;
        }

        $this->log("[SEND_END](" . (microtime(true) - $responseTime) . ") - {$method} - {$uri} - " . $response->getStatusCode() . " - " . $response->getBody());

        return $response;
    }
}
