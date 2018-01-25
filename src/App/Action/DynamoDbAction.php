<?php

namespace App\Action;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Aws\Sdk;
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

class DynamoDbAction extends AbstractAction
{
    /**
     * @var DynamoDbClient
     */
    public $dynamo;

    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return JsonResponse
     */
    public function indexGet(ServerRequestInterface $request, DelegateInterface $delegate)
    {
//        $route     = $request->getAttribute(RouteResult::class);
//        $routeName = $route->getMatchedRoute()->getName();

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

        $id = $request->getAttribute('id', 'team-5837e6c9ef3bd-DZcGfEMBV');

        $marshaler = new Marshaler();
        $tableName = 'MC_TEAM';


        $key = $marshaler->marshalJson('
            {
                "team_id": "'.$id.'"
            }
        ');

        $params = [
            'TableName' => $tableName,
            'Key' => $key,
        ];

        try {
            $result = $this->dynamo->getItem($params);
        } catch (DynamoDbException $e) {
            echo "Unable to get item:\n";
            echo $e->getMessage() . "\n";
        }

        return new JsonResponse($marshaler->unmarshalItem($result['Item']));
    }
}