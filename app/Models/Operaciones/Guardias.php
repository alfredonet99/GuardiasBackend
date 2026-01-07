<?php

namespace App\Models\Operaciones;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Guardias extends Model
{
    use HasFactory;

    protected $table='info_guard';

    protected $fillable = [
        'id_user',
        'dateInit',
        'dateFinish',
        'status',
    ];

    public function user(){
        return $this->belongsTo(User::class, 'id_user');
    }
}
