<?php

declare(strict_types=1);

namespace App\Model;

class RolesModel extends AbstractModel
{
    public function removeRoleIdAttr($username)
    {
        $data = [
            'TableName'        => 'MC_IDENTITY',
            'Key'              => [
                'username' => [
                    'S' => $username,
                ],
            ],
            'AttributeUpdates' => [
                'role_id' => [
                    'Action' => 'DELETE',
                ],
            ],
        ];

        $this->dynamo->updateItem($data);
    }
}
