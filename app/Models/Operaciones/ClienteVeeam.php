<?php

namespace App\Models\Operaciones;

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

    public function AppCV() {
        return $this->belongsTo(AppService::class,'app');
    }

}
