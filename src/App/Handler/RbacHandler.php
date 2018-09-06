<?php

declare(strict_types=1);

namespace App\Handler;

use App\Model\RbacModel;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\EmptyResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Permissions\Rbac\Rbac;
use Zend\Permissions\Rbac\Role;

class RbacHandler extends AbstractHandler
{
    // Deny All
    const DEFAULT_SUBUSER_ROLE = [
        'version'   => '2017-05-05',
        'statement' => [],
    ];

    // Allow All
    const ROOT_ROLE = [
        'version'   => '2017-05-05',
        'statement' => [
            [
                "action"   => "*",
                "resource" => "*",
                "effect"   => "allow"
            ]
        ],
    ];

    const ROLE_ACTIONS = ['view', 'create', 'edit', 'delete'];

    // used to show in UI
    const PROJECT_MAP = [
        'events'             => 'alm',
        'vendor.credentials' => 'alm',
        'vendor.stacks'      => 'alm',
        'users'              => 'alm',
        'users.client'       => 'alm',

        'wave.account'       => 'wave',
        'wave.user.password' => 'wave',
    ];

    /**
     * @var RbacModel
     */
    public $dynamo;

    /**
     * @return ResponseInterface
     */
    public function actions(): ResponseInterface
    {
        $result = $this->dynamo->getAllRoles();
        $roles  = [];
        $array  = [];

        foreach ($result as $item) {
            $temp = explode(':', $item);

            $roles[$temp[1]]['type'][] = $temp[0];
        }
        foreach ($roles as $index => $role) {
            if (isset(self::PROJECT_MAP[$index])) {
                $array[] = [
                    'action'  => $index,
                    'type'    => $role['type'],
                    'project' => self::PROJECT_MAP[$index],
                ];
            }
        }

        return new JsonResponse($array);
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @return ResponseInterface
     */
    public function inspect(ServerRequestInterface $request): ResponseInterface
    {
        $body  = json_decode((string)$request->getBody(), true);
        $roles = [];

        if ($this->user['root']) {
            $roles = self::ROOT_ROLE;
        } else {
            $subUser = $this->dynamo->get('MC_IDENTITY', ['username' => $this->user['username']]);

            if (isset($subUser['role_id'])) {
                $roles = $this->getRoleById($subUser['role_id']);
            }

            if (empty($roles)) {
                $roles = self::DEFAULT_SUBUSER_ROLE;
            }
        }

        $roles = $this->prepareRoles($roles);

        if (!$this->isRoleAllowed($body, $roles)) {
            $message = "RBAC role [{$body['role']}] is not allowed" . (isset($body['resource_id']) ? " for [{$body['resource_id']}]" : '');
            return new JsonResponse(['message' => $message], StatusCodeInterface::STATUS_FORBIDDEN);
        }

        $rawBody = (isset($body['raw_body']) ? json_encode($body['raw_body']) : '');

        $response = $this->send($body['url'], strtoupper($body['method']), $rawBody);
        if (!$response) {
            return $this->errorResponse();
        }

        $result = json_decode($response->getBody());
        $result = $this->filterResult($result, $roles, $body);

        if (is_null($result)) {
            return new EmptyResponse($response->getStatusCode());
        } else {
            return new JsonResponse($result, $response->getStatusCode());
        }
    }

    private function isRoleAllowed(array $body, array $roles): bool
    {
        $rbac = new Rbac();
        if (isset($roles['deny'])) {
            $denyRole = new Role('deny');
            foreach ($roles['deny'] as $action => $resource) {
                $denyRole->addPermission($action);
            }
            $rbac->addRole($denyRole);
        }
        if (isset($roles['allow'])) {
            $allowRole = new Role('allow');
            foreach ($roles['allow'] as $action => $resource) {
                $allowRole->addPermission($action);
            }
            $rbac->addRole($allowRole);
        }

        // Deny = high priority
        if ($rbac->hasRole('deny')) {
            if ($rbac->isGranted('deny', $body['role'])) {
                if ($roles['deny'][$body['role']] !== '*') {
                    if (isset($body['resource_id'])) {
                        if (!in_array($body['resource_id'], $roles['deny'][$body['role']])) {
                            return true;
                        }
                    } else {
                        return true;
                    }

                }
            }
        }
        if ($rbac->hasRole('allow')) {
            if ($rbac->isGranted('allow', $body['role'])) {
                if ($roles['allow'][$body['role']] === '*') {
                    return true;
                } else {
                    if (isset($body['resource_id'])) {
                        if (in_array($body['resource_id'], $roles['allow'][$body['role']])) {
                            return true;
                        }
                    } else {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    private function filterResult($result, $roles, $body)
    {
        if (isset($body['filter_field']) && is_array($result)) {
            if (isset($roles['deny']) && isset($roles['deny'][$body['role']]) && is_array($roles['deny'][$body['role']])) {
                $result = array_filter($result, function ($value) use ($body, $roles) {
                    if (isset($value->{$body['filter_field']}) && in_array($value->{$body['filter_field']}, $roles['deny'][$body['role']])) {
                        return false;
                    }

                    return true;
                });
                $result = array_values($result);
            }

            if (isset($roles['allow']) && isset($roles['allow'][$body['role']]) && is_array($roles['allow'][$body['role']])) {
                $result = array_filter($result, function ($value) use ($body, $roles) {
                    if (isset($value->{$body['filter_field']}) && in_array($value->{$body['filter_field']}, $roles['allow'][$body['role']])) {
                        return true;
                    }

                    return false;
                });
                $result = array_values($result);
            }
        }

        return $result;
    }

    /**
     * @param string $roleId
     *
     * @return array
     */
    private function getRoleById($roleId)
    {
        $roles = $this->dynamo->get('MC_ROLES', ['role_id' => $roleId, 'user_id' => $this->user['user_id']]);
        if (empty($roles)) {
            return [];
        }

        return $roles['scope'];
    }

    /**
     * Transform and merge roles to RBAC
     *
     * @param array $roles
     *
     * @return array
     */
    private function prepareRoles($roles)
    {
        $result = [];
        foreach ($roles['statement'] as $role) {
            $actions = $this->parseAction($role['action']);
            foreach ($actions as $action) {
                $result[$role['effect']][] = [
                    'action'    => $action,
                    'resources' => $role['resource'],
                ];
            }
        }
        $result = $this->mergeActions($result);
        $result = $this->mergeAllowDenyRules($result);

        return $result;
    }

    /**
     * Change all '*' to explicit values
     *
     * @param array|string $permission
     *
     * @return array
     */
    private function parseAction($permission)
    {
        if ($permission === '*') {
            return $this->dynamo->getAllRoles();
        }
        $result = [];
        $temp   = explode(':', $permission);
        $action = $temp[0];
        $path   = $temp[1];
        if ($action === '*') {
            foreach (self::ROLE_ACTIONS as $current) {
                $result[] = "{$current}:{$path}";
            }
        } else {
            $result[] = $permission;
        }

        return $result;
    }

    /**
     * Merge all duplicate values in roles array where '*' has high priority
     *
     * @param array $roles
     *
     * @return array
     */
    private function mergeActions(array $roles)
    {
        $result = [];
        foreach ($roles as $type => $value) {
            foreach ($value as $role) {
                if ($role['resources'] === '*') {
                    $result[$type][$role['action']] = $role['resources'];
                } else {
                    if (isset($result[$type][$role['action']])) {
                        if ($result[$type][$role['action']] === '*') {
                            continue;
                        }
                        $result[$type][$role['action']] = array_merge($result[$type][$role['action']], $role['resources']);
                    } else {
                        $result[$type][$role['action']] = $role['resources'];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Merge allow and deny rules resolving conflicts
     *
     * @param array $role
     *
     * @return array
     */
    private function mergeAllowDenyRules(array $role)
    {
        $result = [];
        if (isset($role['allow']) && isset($role['deny'])) {
            foreach ($role['allow'] as $action => $resource) {
                if (isset($role['deny'][$action])) {
                    if (($role['deny'][$action] === '*') || ($role['deny'][$action] === $resource)) {
                        continue;
                    }
                    if ($resource === '*') {
                        $result['deny'][$action] = $role['deny'][$action];
                    } else {
                        $result['allow'][$action] = array_diff($resource, $role['deny'][$action]);
                    }
                } else {
                    $result['allow'][$action] = $resource;
                }
            }
        } else if (isset($role['allow'])) {
            $result['allow'] = $role['allow'];
        }

        return $result;
    }
}
