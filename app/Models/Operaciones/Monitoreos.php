<?php

namespace App\Models\Operaciones;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Monitoreos extends Model
{
    use HasFactory;
    
    protected $table = 'monitoreos';

    protected $fillable = [
        'siteApp',
        'client_id',
        'dateRest',
        'estatus',
        'observacion',
        'concluido',
        'id_guard',
        'user_Cre',
        'user_Upd',
    ];

    public function Appmv(){
        return $this->belongsTo(AppService::class, 'siteApp');
    }

     public function Cvm(){
        return $this->belongsTo(ClienteVeeam::class, 'client_id');
    }

    public function userCreate(){
        return $this->belongsTo(User::class,'user_Cre');
    }

    public function userUpdate(){
        return $this->belongsTo(User::class, 'user_Upd');
    }

    public function guardiaMonit()
    {
        return $this->belongsTo(Guardias::class, 'id_guard');
    }
}
