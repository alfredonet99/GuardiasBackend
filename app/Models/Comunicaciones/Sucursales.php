<?php

namespace App\Models\Comunicaciones;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sucursales extends Model
{
    use HasFactory;

   protected $table = 'sucursales';

   protected $fillable = [
    'nameS',
    'servHost',
    'plat',
    'keys',
    'ip',
   ];
}
