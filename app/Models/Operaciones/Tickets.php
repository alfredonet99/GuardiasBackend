<?php

namespace App\Models\Operaciones;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Tickets extends Model
{
    use HasFactory;

    protected $table = 'tickets';

     protected $fillable = [
        'numTicket',
        'numTicketNoct',
        'user_create_ticket',
        'assigned_user_id',
        'titleTicket',
        'descriptionTicket',
        'status',
        'id_guardia',
    ];

    protected $casts = [
        'numTicket'       => 'integer',
        'numTicketNoct'   => 'integer',
        'user_create_ticket' => 'integer',
        'assigned_user_id'=> 'integer',
        'status'          => 'integer',
        'id_guardia'      => 'integer',
    ];

     public function creator()
    {
        return $this->belongsTo(User::class, 'user_create_ticket');
    }

     public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function guardia()
    {
        return $this->belongsTo(Guardias::class, 'id_guardia');
    }

}
