<?php

namespace App\Models\Operaciones;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppService extends Model
{
    use HasFactory;

    protected $table = "app_service";
    protected $fillable = [
        'nameService',
        'descriptionService',
        'activo',
    ];
}
