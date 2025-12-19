<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Supabase\Storage\StorageClient;
use App\Services\SuperBaseStorage;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'activo',
        'area_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function isAdmin(): bool
    {
        return $this->hasRole('Administrador');
    }



    protected static function booted()
    {
        static::creating(function ($user) {
            if (empty($user->avatar)) {
                $storage = new SuperBaseStorage();
                $user->avatar = $storage->getPublicUrl('userdefault.jpg');
            }
        });
    }


    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            "test" => "hola"
        ];
    }

    public function area(){
        return $this->belongsTo(Area::class);
    }

}


