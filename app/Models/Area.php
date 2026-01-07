<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Permission;

class Area extends Model
{
    use HasFactory;
    protected $table = "areas";

    protected $fillable = [
        'name',
        'activo',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

     public function permissions()
    {
        return $this->hasMany(Permission::class, 'id_area', 'id');
    }
}
