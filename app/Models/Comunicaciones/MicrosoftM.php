<?php

namespace App\Models\Comunicaciones;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MicrosoftM extends Model
{
    use HasFactory;
    protected $table = 'microsoft_m';
    protected $fillable = [
        'serviceName',
        'revisionDate',
        'state',
        'description',
        'ejecution',
        'id_user'
    ];

    public function userCrea()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}
