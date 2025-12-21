<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function areaIdFromRoleId(?int $roleId): ?int
    {
        return \App\Support\RoleAreaMapper::areaIdFromRoleId($roleId);
    }

    protected function roleIdsForArea(?int $areaId): array
    {
        return \App\Support\RoleAreaMapper::roleIdsForArea($areaId);
    }

    protected function roleBelongsToArea(int $roleId, int $areaId): bool
    {
        return \App\Support\RoleAreaMapper::roleBelongsToArea($roleId, $areaId);
    }
}
