<?php

namespace App\Action;

use App\Model\DynamoDbModel;
use Fig\Http\Message\StatusCodeInterface;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ServerRequestInterface;

class DynamoDbAction extends AbstractAction
{
    /**
     * @var DynamoDbModel
     */
    public $dynamo;

    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface $delegate
     * @return EmptyResponse|JsonResponse
     */
    public function indexGet(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $id = $request->getAttribute('id');

        if ($id) {
            $data = $this->dynamo->get('MC_TEAM', ['team_id' => $id]);
            if ($data) {
                return new JsonResponse($data);
            } else {
                return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
            }
        } else {
            $data = $this->dynamo->getCollection('MC_TEAM', '#user_id = :user_id', ['user_id' => '590fdb7bad55s']);
        }

        return new JsonResponse($data);
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
        $this->dynamo->put('MC_TEAM', $body);

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
        $id        = $request->getAttribute('id', 'team-5837e6c9ef3bd-DZcGfEMBV');
        $body      = (array)json_decode($request->getBody()->getContents());

        $location = "{$_SERVER['REQUEST_SCHEME']}://{$_SERVER['HTTP_HOST']}/dynamo-db/{$id}";

        return new JsonResponse(
            $this->dynamo->update('MC_TEAM', ['team_id' => $id], $body),
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
        $id = $request->getAttribute('id', 'team-5837e6c9ef3bd-DZcGfEMBV');

        $this->dynamo->delete('MC_TEAM', ['team_id' => $id]);

        return new EmptyResponse(StatusCodeInterface::STATUS_NO_CONTENT);
    }
}
