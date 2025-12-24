<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppService extends Model
{
    use HasFactory;

    protected $table = "app_service";
    protected $fillable = [
        'nameService',
        'descriptionService'
    ];
}
