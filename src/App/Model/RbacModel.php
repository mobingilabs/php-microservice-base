<?php

declare(strict_types=1);

namespace App\Model;

class RbacModel extends AbstractModel
{
    public function getAllRoles(): array
    {
        $json = file_get_contents("./src/App/Validation/rolesSchema.json");
        return json_decode($json)->enum;
    }
}
