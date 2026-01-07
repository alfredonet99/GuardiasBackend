<?php

namespace App\Mail;

use App\Models\Operaciones\Guardias;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GuardiaAutoClosedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Guardias $guardia) {}

    public function build()
    {
        return $this
            ->subject('Guardia cerrada por el sistema')
            ->view('emails.guardias.auto_closed');
    }
}
