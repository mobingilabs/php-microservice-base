<?php

namespace App\Action;

use Fig\Http\Message\StatusCodeInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;

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
        $action = $request->getAttribute('action', 'index') . ucfirst(strtolower($request->getMethod()));

        if (! method_exists($this, $action)) {
            return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
        }

        return $this->{$action}($request, $delegate);
    }
}
