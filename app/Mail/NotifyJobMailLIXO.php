<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NotifyJobMail extends Mailable
{
    use Queueable, SerializesModels;

    public $action_link;
    public $nome;
    public $body;
    public $titulo;
    public $subject;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($actionLink, $nome, $bodyEmail, $titleEmail, $subject)
    {
        $this->action_link = $actionLink;
        $this->nome = $nome;
        $this->body = $bodyEmail;
        $this->titulo = $titleEmail;
        $this->subject = $subject;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('informativo@uniflow.app.br', 'Informativo UniFlow Unicasa')
        ->subject($this->subject)
        ->view('emails.notify-job')
        ->with([
            'action_link' => $this->action_link,
            'nome' => $this->nome,
            'body' => $this->body,
            'titulo' => $this->titulo,
        ]);
    }
}
