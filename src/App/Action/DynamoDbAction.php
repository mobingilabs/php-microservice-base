<?php

namespace App\Action;

use Aws\DynamoDb\DynamoDbClient;
use Aws\DynamoDb\Exception\DynamoDbException;
use Aws\DynamoDb\Marshaler;
use Fig\Http\Message\StatusCodeInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

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
        $id = $request->getAttribute('id', 'team-5837e6c9ef3bd-DZcGfEMBV');

        $marshaler = new Marshaler();
        $tableName = 'MC_TEAM';


        $key = $marshaler->marshalJson('
            {
                "team_id": "' . $id . '"
            }
        ');

        $params = [
            'TableName' => $tableName,
            'Key'       => $key,
        ];

        try {
            $result = $this->dynamo->getItem($params);
        } catch (DynamoDbException $e) {
            echo "Unable to get item:\n";
            echo $e->getMessage() . "\n";
        }

        return new JsonResponse($marshaler->unmarshalItem($result['Item']));
    }

    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return JsonResponse
     */
    public function indexPost(ServerRequestInterface $request, DelegateInterface $delegate): JsonResponse
    {
        $body = (array)json_decode($request->getBody()->getContents());

        $body['team_id']     = 'team-590fdb7bad55s-xxx';
        $body['create_time'] = (new \DateTime)->format(DATE_ATOM);
        $marshaler           = new Marshaler();

        $params = [
            'TableName' => 'MC_TEAM',
            'Item'      => $marshaler->marshalItem($body)
        ];


        try {
            $result = $this->dynamo->putItem($params);
        } catch (DynamoDbException $e) {
            echo "Unable to add item:\n";
            echo $e->getMessage() . "\n";
        }

        $location = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/dynamo-db/{$body['team_id']}";

        return new JsonResponse($body, StatusCodeInterface::STATUS_CREATED, ['Location' => $location]);
    }

    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return JsonResponse
     */
    public function indexPatch(ServerRequestInterface $request, DelegateInterface $delegate): JsonResponse
    {

        $id   = $request->getAttribute('id', 'team-5837e6c9ef3bd-DZcGfEMBV');
        $body = (array)json_decode($request->getBody()->getContents());

//        $body['team_id']     = $id;
        $marshaler = new Marshaler();

        $key = $marshaler->marshalJson('
            {
                "team_id": "' . $id . '"
            }
        ');


        $eav = $marshaler->marshalJson('
            {
                ":p": "Everything happens all at once."
            }
        ');

        $params = [
            'TableName' => 'MC_TEAM',
            'Key' => $key,
            'UpdateExpression' =>
                'set myname=:p',
            'ExpressionAttributeValues'=> $eav,
            'ReturnValues' => 'ALL_NEW'
        ];

        try {
            $result = $this->dynamo->updateItem($params);

        } catch (DynamoDbException $e) {
            echo "Unable to update item:\n";
            echo $e->getMessage() . "\n";
        }

        $location = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/dynamo-db/{$id}";

        return new JsonResponse(
            $marshaler->unmarshalItem($result['Attributes']),
            StatusCodeInterface::STATUS_OK,
            ['Location' => $location]
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return EmptyResponse
     */
    public function indexDelete(ServerRequestInterface $request, DelegateInterface $delegate): EmptyResponse
    {
        $id   = $request->getAttribute('id', 'team-5837e6c9ef3bd-DZcGfEMBV');

        $marshaler = new Marshaler();

        $key = $marshaler->marshalJson('
            {
                "team_id": "' . $id . '"
            }
        ');

        $params = [
            'TableName'                 => 'MC_TEAM',
            'Key'                       => $key,
        ];

        try {
            $result = $this->dynamo->deleteItem($params);
        } catch (DynamoDbException $e) {
            echo "Unable to delete item:\n";
            echo $e->getMessage() . "\n";
        }

        return new EmptyResponse(StatusCodeInterface::STATUS_NO_CONTENT);
    }
}
