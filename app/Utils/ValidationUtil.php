<?php

namespace App\Utils;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ValidationUtil
{
    public static function validateData($data, $rules, $messages)
    {
        $validator = Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return null;
    }

    public static function yourValidationRules($ag = false, $col = false)
    {
        $rules = [
            'titulo' => 'required|min:3',
            // 'agencia' => $col ? 'required' : '',
            // 'users' => 'required',
            // 'colaborador' => $ag ? 'required' : '',
            // 'marcas' => 'required',
            'inicio' => 'required',
            'final' => 'required',
            'prioridade' => 'required',
            'briefing' => 'required|min:3',
            'objetivos' => 'required',
            'pecas' => 'required',
            'formato' => 'required',
            'formatoInput' => 'required',
        ];

        return $rules;

    }

    public static function yourValidationMessages($ag = false, $col = false)
    {
        $msg = [
            'titulo.required' => 'Preencha o campo título.',
            'titulo.min' => 'O campo título deve ter pelo menos 3 caracteres.',
            // 'agencia.required' => $col ? 'Preencha o campo agência.' : '',
            // 'users.required' => 'Preencha o campo usuários.',
            // 'colaborador.required' => $ag ? 'Preencha o campo colaborador.' : '',
            // 'marcas.required' => 'Preencha o campo marca.',
            'inicio.required' => 'Preencha o campo data inicial.',
            'final.required' => 'Preencha o campo data de entrega.',
            'prioridade.required' => 'Preencha o campo prioridade.',
            'briefing.required' => 'Preencha o campo descrição.',
            'briefing.min' => 'O campo briefing deve ter pelo menos 3 caracteres.',
            'objetivos.required' => 'Preencha o campo objetivos.',
            'pecas.required' => 'Preencha o campo peças.',
            'formato.required' => 'Preencha o campo formato.',
            'formatoInput.required' => 'Preencha o campo formato.',
        ];

        return $msg;
    }
}
