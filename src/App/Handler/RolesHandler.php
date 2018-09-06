<?php

declare(strict_types=1);

namespace App\Handler;

use App\Model\RbacModel;
use App\Model\RolesModel;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Permissions\Rbac\Rbac;
use Zend\Permissions\Rbac\Role;

class RolesHandler extends AbstractHandler
{
    /**
     * @var RolesModel
     */
    public $dynamo;

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function rolesCreate(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode((string)$request->getBody(), true);

        $info['role_id']     = 'role-' . time() . bin2hex(random_bytes(8));
        $info['user_id']     = $this->user['user_id'];
        $info['name']        = $body['name'];
        $info['scope']       = $body['scope'];
        $info['create_time'] = (new \DateTime)->format(DATE_ATOM);
        $info['update_time'] = $info['create_time'];

        $this->dynamo->put('MC_ROLES', $info);

        $location = $this->headerLocation("/roles/{$info['role_id']}");

        return new JsonResponse($info, StatusCodeInterface::STATUS_CREATED, ['Location' => $location]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function rolesRead(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('role_id');

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
    public function rolesUpdate(ServerRequestInterface $request): ResponseInterface
    {
        $id   = $request->getAttribute('role_id');
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

        $location = $this->headerLocation("/roles/{$id}");

        return new JsonResponse($data, StatusCodeInterface::STATUS_OK, ['Location' => $location]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function rolesDelete(ServerRequestInterface $request): ResponseInterface
    {
        $id                   = $request->getAttribute('role_id');
        $condition['user_id'] = $this->user['user_id'];

        $result = $this->dynamo->delete('MC_ROLES', ['role_id' => $id, 'user_id' => $this->user['user_id']], $condition);

        return new EmptyResponse(
            ($result) ? StatusCodeInterface::STATUS_NO_CONTENT : StatusCodeInterface::STATUS_NOT_FOUND
        );
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function rolesUserUpdate(ServerRequestInterface $request): ResponseInterface
    {
        $username = $request->getAttribute('username');
        $body     = json_decode((string)$request->getBody(), true);

        $info['role_id']     = $body['role_id'];
        $info['update_time'] = (new \DateTime)->format(DATE_ATOM);

        $condition['mobingi_user'] = $this->user['user_id'];

        $data = $this->dynamo->update('MC_IDENTITY', ['username' => $username], $info, $condition);
        if ($data === false) {
            return new EmptyResponse(StatusCodeInterface::STATUS_NOT_FOUND);
        }

        $location = $this->headerLocation("/roles/{$info['role_id']}");

        unset($data['password']);

        return new JsonResponse($data, StatusCodeInterface::STATUS_OK, ['Location' => $location]);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    public function rolesUserDelete(ServerRequestInterface $request): ResponseInterface
    {
        $username = $request->getAttribute('username');

        $this->dynamo->removeRoleIdAttr($username);

        return new EmptyResponse(StatusCodeInterface::STATUS_NO_CONTENT);
    }
}
