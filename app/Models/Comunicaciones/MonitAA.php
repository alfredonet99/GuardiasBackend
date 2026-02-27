<?php

namespace App\Models\Comunicaciones;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MonitAA extends Model
{
    use HasFactory;

    protected $table = 'monit_redes';

    protected $fillable = [
        'sucursal_id',
        'dateRed',
        'statusRed',
        'time_down',
        'time_up',
        'affectation',
        'reason',
        'note',
        'statusMonit',
        'user_create',
        'user_update'
    ];

    public function sucursal()
    {
        return $this->belongsTo(Sucursales::class, 'sucursal_id');
    }
    
    public function userCreate()
    {
        return $this->belongsTo(User::class, 'user_create');
    }

    public function userUpdate()
    {
        return $this->belongsTo(User::class, 'user_update');
    }
}
