<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClienteVeeam extends Model
{
    use HasFactory;

    protected $table = 'c_veeam';

     protected $fillable = [
        'numCV',
        'nameCV',
        'app',
        'backup',
        'jobs',
        'activo',
    ];

}
