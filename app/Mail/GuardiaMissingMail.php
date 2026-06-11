<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GuardiaMissingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $url) {}

    public function build()
    {
        return $this
            ->subject('⚠️ No hay guardia activa - iniciar guardia')
            ->view('emails.guardias.missing')
            ->with(['url' => $this->url]);
    }
}
