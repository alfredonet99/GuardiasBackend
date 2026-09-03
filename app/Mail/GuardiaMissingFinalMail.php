<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GuardiaMissingFinalMail extends Mailable
{
    use Queueable, SerializesModels;

    public $inicio;
    public $fin;
    public $url;

    public function __construct($inicio, $fin, $url)
    {
        $this->inicio = $inicio;
        $this->fin = $fin;
        $this->url = $url;
    }

    public function build()
    {
        return $this->subject('Alerta - No hubo inicio de guardia')
            ->view('emails.guardia-missing-final');
    }
}