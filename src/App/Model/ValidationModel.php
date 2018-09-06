<?php

declare(strict_types=1);

namespace App\Model;

class ValidationModel extends AbstractModel
{
    public function validRoleId(string $roleId, string $userId): bool
    {
        $data = $this->get('MC_ROLES', ['role_id' => $roleId, 'user_id' => $userId]);
        if (empty($data)) {
            return false;
        }

        return true;
    }
}
