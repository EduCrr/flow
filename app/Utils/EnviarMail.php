<?php

namespace App\Utils;

use Illuminate\Support\Facades\Mail;

class EnviarMail
{
    public static function enviarEmail($email, $nome, $actionLink, $bodyEmail, $titleEmail, $mensagem = null, $nomeUsuario = null) {
        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $nome, 'body' => $bodyEmail, 'titulo' => $titleEmail, 'mensagem' => $mensagem, 'nomeUsuario' => $nomeUsuario], function($message) use ($email, $titleEmail) {
            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
            ->to($email)
            ->subject($titleEmail);
        });
    }
}
