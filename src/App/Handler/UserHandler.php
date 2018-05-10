<?php

declare(strict_types=1);

namespace App\Handler;

use App\Filter\UserFilter;
use App\Model\UserModel;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
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
     * @return ResponseInterface
     */
    public function userRead(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        if ($id) {
            $filtered = $this->filter->filterUserRead(['id' => $id]);
            if (is_array($filtered)) {
                return new JsonResponse($filtered, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
            }

            $data = $this->dynamo->get('MC_USERS', ['user_id' => $id]);

            if (!$data) {
                $data = $this->dynamo->get('MC_IDENTITY', ['username' => $id]);
                if (!$data) {
                    return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
                }
            }

            if (isset($this->user['username'])) {
                if (!isset($data['username']) || $data['username'] != $this->user['username']) {
                    return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
                }
            } else {
                if (isset($data['user_id']) && $data['user_id'] != $this->user['user_id']) {
                    return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
                }

                if (isset($data['mobingi_user']) && $data['mobingi_user'] != $this->user['user_id']) {
                    return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
                }
            }

            unset($data['password']);
            return new JsonResponse($data);
        } else {
            if (isset($this->user['username'])) {
                $data[] = $this->dynamo->get('MC_IDENTITY', ['username' => $this->user['username']]);
            } else {
                $data = $this->dynamo->getCollection(
                    'MC_IDENTITY',
                    '#mobingi_user = :mobingi_user',
                    ['mobingi_user' => $this->user['user_id']]
                );
            }
            foreach ($data as &$current) {
                unset($current['password']);
            }
        }

        return new JsonResponse($data);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function userCreate(ServerRequestInterface $request): ResponseInterface
    {
        if (isset($this->user['username'])) {
            return new EmptyResponse(StatusCodeInterface::STATUS_FORBIDDEN);
        }

        $body = $request->getParsedBody();

        $filtered = $this->filter->filterUserCreate($body);
        if (is_array($filtered)) {
            return new JsonResponse($filtered, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $data = $this->dynamo->get('MC_IDENTITY', ['username' => $body['username']]);
        if ($data) {
            return new JsonResponse(
                ['message' => 'The `username` already exists.'],
                StatusCodeInterface::STATUS_FORBIDDEN
            );
        }

        $info['username']              = $body['username'];
        $info['notification']['email'] = $body['notification']['email'];
        if (isset($body['email'])) {
            $info['email'] = $body['email'];
        }
        $info['password']     = password_hash($body['password'], PASSWORD_DEFAULT);
        $info['mobingi_user'] = $this->user['user_id'];
        $info['create_time']  = (new \DateTime)->format(DATE_ATOM);
        $info['update_time']  = $info['create_time'];

        $this->dynamo->put('MC_IDENTITY', $info);

        unset($info['password']);

        $location = $this->headerLocation("/user/{$info['username']}");

        return new JsonResponse($info, StatusCodeInterface::STATUS_CREATED, ['Location' => $location]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function userUpdate(ServerRequestInterface $request): ResponseInterface
    {
        $id         = $request->getAttribute('id');
        $body       = $request->getParsedBody();
        $body['id'] = $id;

        $filtered = $this->filter->filterUserUpdate($body);
        if (is_array($filtered)) {
            return new JsonResponse($filtered, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $condition['mobingi_user'] = $this->user['user_id'];
        if (isset($this->user['username'])) {
            $condition['username'] = $this->user['username'];
        }

        if (isset($body['password'])) {
            $info['password'] = password_hash($body['password'], PASSWORD_DEFAULT);
        }
        if (isset($body['email'])) {
            $info['email'] = $body['email'];
        }
        if(isset($body['notification']['email'])) {
            $info['notification']['email'] = $body['notification']['email'];
        }

        $info['update_time'] = (new \DateTime)->format(DATE_ATOM);

        $rootUser = $this->dynamo->get('MC_USERS', ['user_id' => $id]);
        if ($rootUser && $rootUser['user_id'] == $this->user['user_id']) {
            $data = $this->dynamo->update('MC_USERS', ['user_id' => $id], $info);
        } else {
            $data = $this->dynamo->update('MC_IDENTITY', ['username' => $id], $info, $condition);
            if ($data === false) {
                return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
            }
        }

        unset($data['password'], $data['id']);

        $location = $this->headerLocation("/user/{$id}");

        return new JsonResponse($data, StatusCodeInterface::STATUS_OK, ['Location' => $location]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function userDelete(ServerRequestInterface $request): ResponseInterface
    {
        if (isset($this->user['username'])) {
            return new EmptyResponse(StatusCodeInterface::STATUS_FORBIDDEN);
        }

        $username = $request->getAttribute('username');

        $filtered = $this->filter->filterUserDelete(['username' => $username]);
        if (is_array($filtered)) {
            return new JsonResponse($filtered, StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY);
        }

        $condition['mobingi_user'] = $this->user['user_id'];
        if (isset($this->user['username'])) {
            $condition['username'] = $this->user['username'];
        }

        $result = $this->dynamo->delete('MC_IDENTITY', ['username' => $username], $condition);

        return new EmptyResponse(
            ($result) ? StatusCodeInterface::STATUS_NO_CONTENT : StatusCodeInterface::STATUS_NOT_FOUND
        );
    }
}
