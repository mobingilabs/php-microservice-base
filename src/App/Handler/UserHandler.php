<?php

declare(strict_types=1);

namespace App\Handler;

use App\Filter\UserFilter;
use App\Model\UserModel;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;

class UserHandler extends AbstractHandler
{
    /**
     * @var UserModel
     */
    public $dynamo;

    /**
     * @var UserFilter
     */
    public $filter;

    /**
     * @param ServerRequestInterface $request
     *
     * @return EmptyResponse|JsonResponse
     */
    public function userRead(ServerRequestInterface $request)
    {
        $id = $request->getAttribute('id');

        if ($id) {
            $filtered = $this->filter->filterUserRead(['id' => $id]);
            if (is_array($filtered)) {
                return new JsonResponse($filtered, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
            }

            $data = $this->dynamo->get('MC_USERS', ['user_id' => $id]);

            if ($data) {
                unset($data['password']);
                return new JsonResponse($data);
            }

            $data = $this->dynamo->get('MC_IDENTITY', ['username' => $id]);
            if ($data) {
                unset($data['password']);
                return new JsonResponse($data);
            }
        }

        return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
    }
}
