<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Model\ValidationModel;
use Fig\Http\Message\StatusCodeInterface;
use JsonSchema\Validator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\InputFilter\InputFilter;

class ValidationMiddleware implements MiddlewareInterface
{
    const FILTER_ROLE_ID
        = [
            'name'       => 'example_id',
            'required'   => true,
            'validators' => [
                [
                    'name'    => 'StringLength',
                    'options' => [
                        'min' => 20,
                        'max' => 255,
                    ],
                ],
                [
                    'name'    => 'Regex',
                    'options' => [
                        'pattern' => '/^[a-zA-Z0-9-]+$/',
                        'message' => "It is only allowed 'letters', 'numbers', '-'",
                    ],
                ],
            ],
        ];

    /**
     * @var array User information
     */
    public $user = [];

    /**
     * @var ValidationModel
     */
    public $dynamo;

    /**
     * @var InputFilter
     */
    public $validator;

    /**
     * @var ServerRequestInterface
     */
    public $request;

    /**
     * @param array $injection
     */
    public function __construct(array $injection)
    {
        foreach ($injection as $name => $value) {
            $this->{$name} = $value;
        }
    }

    /**
     * Process an incoming server request and return a response, optionally delegating
     * response creation to a handler.
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->user   = $request->getAttribute('authorization-user');
        $currentRoute = $request->getAttribute('Zend\Expressive\Router\RouteResult')->getMatchedRouteName();
        $data         = json_decode((string)$request->getBody());
        $schemaFile   = ucfirst($currentRoute);

        // 1) verify raw json body validation
        if (file_exists("./src/App/Validation/{$schemaFile}Schema.json")) {
            $validator = new Validator();
            $validator->coerce($data, (object)['$ref' => 'file://' . realpath("./src/App/Validation/{$schemaFile}Schema.json")]);

            if (!$validator->isValid()) {
                $errors = [];

                foreach ($validator->getErrors() as $error) {
                    if (!empty($error['property'])) {
                        $errors[$error['property']] = [$error['constraint'] => str_replace('"', '', $error['message'])];
                    }
                }

                return new JsonResponse($errors, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
            }
        }

        // 2) check for custom raw json body validation and url params validation
        if (method_exists($this, $currentRoute)) {
            $this->validator = new InputFilter();
            $this->request   = $request;
            $errors          = $this->{$currentRoute}($data);
            if (!empty($errors)) {
                return new JsonResponse($errors, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
            }
        }

        return $handler->handle($request);
    }

    public function exampleCreate($data): array
    {
        $errors = [];
        foreach ($data->scope->statement as $index => $item) {
            if ($item->resource !== '*') {
                if (!is_array($item->resource)) {
                    $errors["scope.statement[{$index}].resource"] = [
                        'invalid_property' => 'The property should be string \'*\' or an array of resources'
                    ];
                }
            }
        }

        return $errors;
    }

    public function exampleRead(): array
    {
        return $this->paramValidation('example_id', self::FILTER_ROLE_ID);
    }

    public function exampleUpdate($data): array
    {
        $params = $this->paramValidation('example_id', self::FILTER_ROLE_ID);
        $errors = $this->exampleCreate($data);

        return array_merge($params, $errors);
    }

    public function exampleDelete(): array
    {
        return $this->paramValidation('example_id', self::FILTER_ROLE_ID);
    }

    private function paramValidation(string $param, array $filter): array
    {
        $errors = [];

        if (!is_null($params[$param] = $this->request->getAttribute($param))) {
            $this->validator->add($filter);
            $this->validator->setData($params);
            if (!$this->validator->isValid()) {
                $errors = $this->validator->getMessages();
            }
        }

        return $errors;
    }
}