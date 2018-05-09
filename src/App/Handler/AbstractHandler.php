<?php

declare(strict_types=1);

namespace App\Handler;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;
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

        $this->user = $request->getAttribute('x-user');

        return $this->{$function}($request);
    }
}
