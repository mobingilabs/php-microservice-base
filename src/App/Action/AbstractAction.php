<?php

namespace App\Action;

use Fig\Http\Message\StatusCodeInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Expressive\Router\RouteResult;

class AbstractAction implements MiddlewareInterface
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
     * @param array $injection
     */
    public function __construct(array $injection)
    {
        foreach ($injection as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return \Psr\Http\Message\ResponseInterface|EmptyResponse
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $result   = $request->getAttribute(RouteResult::class);
        $function = $result->getMatchedRouteName();

        if (! method_exists($this, $function)) {
            return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
        }

        $this->user = $request->getAttribute('user');

        return $this->{$function}($request, $delegate);
    }

    /**
     * Generate Header Location
     *
     * @param $path
     * @return string
     */
    public function headerLocation($path): String
    {
        return "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}{$path}";
    }
}
