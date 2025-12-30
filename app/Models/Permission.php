<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area', 'id');
    }
}
