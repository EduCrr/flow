<?php

namespace App\Utils;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class ValidationAgenciaUtil
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
    
    public static function yourValidationRules()
    {
        return [
            'titulo' => 'required|min:3',
            'users' => 'required',
            'colaborador' => 'required',
            'inicio' => 'required',
            'final' => 'required',
            'marcas' => 'required',
            'prioridade' => 'required',
            'briefing' => 'required|min:3',
            'objetivos' => 'required',
            'pecas' => 'required',
            'formato' => 'required',
            'formatoInput' => 'required',
        ];
    }
    
    public static function yourValidationMessages()
    {
        return [
            'titulo.required' => 'Preencha o campo título.',
            'titulo.min' => 'O campo título deve ter pelo menos 3 caracteres.',
            'users.required' => 'Preencha o campo usuário.',
            'colaborador.required' => 'Preencha o campo colaborador.',
            'inicio.required' => 'Preencha o campo data inicial.',
            'final.required' => 'Preencha o campo data de entrega.',
            'marcas.required' => 'Preencha o campo marca.',
            'prioridade.required' => 'Preencha o campo prioridade.',
            'briefing.required' => 'Preencha o campo descrição.',
            'briefing.min' => 'O campo briefing deve ter pelo menos 3 caracteres.',
            'objetivos.required' => 'Preencha o campo objetivos.',
            'pecas.required' => 'Preencha o campo peças.',
            'formato.required' => 'Preencha o campo formato.',
            'formatoInput.required' => 'Preencha o campo formato.',
        ];
    }
}
