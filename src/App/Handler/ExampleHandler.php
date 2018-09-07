<?php

declare(strict_types=1);

namespace App\Handler;

use App\Model\ExampleModel;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class ExampleHandler extends AbstractHandler
{
    /**
     * @var ExampleModel
     */
    public $dynamo;

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function exampleCreate(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string)$request->getBody(), true);

        $info['role_id']     = 'role-' . time() . bin2hex(random_bytes(8));
        $info['user_id']     = $this->user['user_id'];
        $info['name']        = $body['name'];
        $info['scope']       = $body['scope'];
        $info['create_time'] = (new \DateTime)->format(DATE_ATOM);
        $info['update_time'] = $info['create_time'];

        $this->dynamo->put('MC_ROLES', $info);

        $location = $this->headerLocation("/example/{$info['role_id']}");

        return new JsonResponse($info, StatusCodeInterface::STATUS_CREATED, ['Location' => $location]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function exampleRead(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('example_id');

        if ($id) {
            $data = $this->dynamo->get('MC_ROLES', ['role_id' => $id, 'user_id' => $this->user['user_id']]);

            if (!$data) {
                return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
            }

            if ($data['user_id'] != $this->user['user_id']) {
                return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
            }

            return new JsonResponse($data);
        }

        $data = $this->dynamo->getCollection(
            'MC_ROLES',
            '#user_id = :user_id',
            ['user_id' => $this->user['user_id']]
        );
        return new JsonResponse($data);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function exampleUpdate(ServerRequestInterface $request): ResponseInterface
    {
        $id   = $request->getAttribute('example_id');
        $body = json_decode((string)$request->getBody(), true);

        if (isset($body['name'])) {
            $info['name'] = $body['name'];
        }

        if (isset($body['scope'])) {
            $info['scope'] = $body['scope'];
        }

        $info['update_time']  = (new \DateTime)->format(DATE_ATOM);
        $condition['user_id'] = $this->user['user_id'];

        $data = $this->dynamo->update('MC_ROLES', ['role_id' => $id, 'user_id' => $this->user['user_id']], $info, $condition);
        if ($data === false) {
            return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
        }

        $location = $this->headerLocation("/example/{$id}");

        return new JsonResponse($data, StatusCodeInterface::STATUS_OK, ['Location' => $location]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function exampleDelete(ServerRequestInterface $request): ResponseInterface
    {
        $id                   = $request->getAttribute('example_id');
        $condition['user_id'] = $this->user['user_id'];

        $result = $this->dynamo->delete('MC_ROLES', ['role_id' => $id, 'user_id' => $this->user['user_id']], $condition);

        return new EmptyResponse(
            ($result) ? StatusCodeInterface::STATUS_NO_CONTENT : StatusCodeInterface::STATUS_NOT_FOUND
        );
    }

}
