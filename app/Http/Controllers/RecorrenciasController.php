<?php

namespace App\Http\Controllers;

use App\Models\Demanda;
use App\Models\DemandaCampanhaRecorrencia;
use App\Models\DemandaRecorrencia;
use App\Models\DemandaRecorrenciaAjuste;
use App\Models\DemandaRecorrenciaComentario;
use App\Models\DemandaRecorrenciaComentarioLido;
use App\Models\DemandaAtrasadaRecorrencia;
use App\Models\Notificacao;
use Illuminate\Http\Request;
use App\Models\User;
use App\Utils\EnviarMail;
use App\Utils\OrdemJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;

class RecorrenciasController extends Controller
{
    public function campanhaRecorrenciaAction(Request $request, $id){

        $validator = Validator::make($request->all(), [
            'campanha' => 'required|max:255',
            'tipoRecorrencia' => 'required',
        ]);

        $commonMessages = [
            'campanha.required' => 'Preencha o campo título da campanha.',
            'campanha.max' => 'O campo título da campanha deve ter no máximo 255 caracteres.',
            'tipoRecorrencia.required' => 'Preencha o campo tipo.',
        ];


        if ($request->tipoRecorrencia == 'Mensal') {
            $validator->addRules([
                'inicio' => 'required',
                'final' => 'required',
                'dia_ocorrencia' => ['required', 'numeric', function ($attribute, $value, $fail) {
                    if ($value < 1 || $value > 31) {
                        $fail('O campo dia deve estar entre 1 e 31.');
                    }
                }],
            ]);

            $validator->setCustomMessages([
                'inicio.required' => 'Preencha o campo inicio.',
                'final.required' => 'Preencha o campo final.',
                'dia_ocorrencia.required' => 'Preencha o campo dia.',
                'dia_ocorrencia.between' => 'O campo dia deve estar entre :min e :max.',
            ]);

            if ($request->final < $request->inicio) {
                return response()->json([
                    'success' => false,
                    'message' => 'A data de início não pode ser inferior que a data final!'
                ], 400);
            }
        }

        if ($request->tipoRecorrencia == 'Anual') {
            $validator->addRules([
                'anoInicial' => 'required',
                'anoFinal' => 'required',
            ]);

            $validator->setCustomMessages([
                'anoInicial.required' => 'Preencha o campo ano inicial.',
                'anoFinal.required' => 'Preencha o campo ano final.',
            ]);

        }

        if ($request->tipoRecorrencia == 'Semanal') {
            $validator->addRules([
                'dateRange' => 'required',
            ]);

            $validator->setCustomMessages([
                'dateRange.required' => 'Preencha o campo semanas.',
            ]);
        }

        $validator->setCustomMessages($commonMessages);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        if(!$validator->fails()){
            $campanha = new DemandaCampanhaRecorrencia();
            $campanha->titulo = $request->campanha;
            $campanha->demanda_id = $id;
            $campanha->tipo = $request->tipoRecorrencia;
            $campanha->finalizada = 0;
            $campanha->criado = date('Y-m-d H:i:s');
            $responseCampanha = $campanha->save();

            if($responseCampanha){

                $recorrencias = [];

                if($request->tipoRecorrencia == 'Mensal'){
                    $inicio = Carbon::createFromFormat('Y-m', $request->inicio);
                    $final = Carbon::createFromFormat('Y-m', $request->final);

                    $diferencaMeses = $inicio->diffInMonths($final);

                    for ($i = 0; $i <= $diferencaMeses; $i++) {
                        $mes = $inicio->copy()->addMonths($i);

                        if ($mes->lt($inicio)) {
                            continue;
                        }

                        // Verifica se o dia fornecido é maior que o último dia do mês
                        $ultimoDiaMes = $mes->endOfMonth()->day;
                        if ($request->dia_ocorrencia > $ultimoDiaMes) {
                            // Se for, ajusta para o último dia do mês
                            $inicioDia = $mes->copy()->endOfMonth();
                        } else {
                            // Senão, define o dia normalmente
                            $inicioDia = $mes->copy()->startOfMonth()->addDays($request->dia_ocorrencia - 1);
                        }

                        // Verifica se a data resultante é um sábado ou domingo
                        if ($inicioDia->isWeekend()) {
                            // Se for, ajusta para sexta-feira anterior
                            // $inicioDia = $inicioDia->previousWeekday();
                            $inicioDia = $inicioDia->nextWeekday();
                        }

                        $recorrencia = [
                            'campanha_id' => $campanha->id,
                            'data' => $inicioDia->format('Y-m-d'),
                            'tipo' =>  $request->tipoRecorrencia,
                            'criado' => date('Y-m-d H:i:s'),
                            'status' => 'Pendente',
                        ];

                        $recorrencias[] = $recorrencia;
                    }
                }

                if($request->tipoRecorrencia == 'Anual'){

                    $anoAtual = Carbon::createFromFormat('Y', substr($request->anoInicial, 0, 4))->year;
                    $anoFinal = Carbon::createFromFormat('Y', substr($request->anoFinal, 0, 4))->year;
                    $mes = date('m', strtotime($request->anoInicial));
                    $dia = date('d', strtotime($request->anoInicial));
                    $diferencaAnos = $anoFinal - $anoAtual;

                    for ($i = 0; $i <= $diferencaAnos; $i++) {
                        $ano = $anoAtual + $i;

                        $data = Carbon::createFromDate($ano, $mes, $dia);

                        if ($data->isWeekend()) {
                            // $data = $data->previousWeekday();
                            $data = $data->nextWeekday();
                        }

                        $recorrencia = [
                            'campanha_id' => $campanha->id,
                            'data' => $data->format('Y-m-d'),
                            'tipo' =>  $request->tipoRecorrencia,
                            'criado' => date('Y-m-d H:i:s'),
                            'status' => 'Pendente',
                        ];

                        $recorrencias[] = $recorrencia;
                    }
                }

                if($request->tipoRecorrencia == 'Semanal'){
                    $dateRange = $request->dateRange;
                    $datas = explode(' | ', $dateRange);

                    $recorrencias = [];

                    foreach($datas as $item){
                        $recorrencia = [
                            'campanha_id' => $campanha->id,
                            'data' => date('Y-m-d', strtotime($item)),
                            'tipo' =>  $request->tipoRecorrencia,
                            'status' => 'Pendente',
                            'criado' => date('Y-m-d H:i:s'),
                        ];

                        $recorrencias[] = $recorrencia;
                    }
                }

                $response = DemandaRecorrencia::insert($recorrencias);

                if($response){

                    $demanda = Demanda::where('id', $id)->with('demandasUsuario')->first();
                    $demandaUsuariosIds = $demanda->demandasUsuario->pluck('id')->toArray();
                    $demanda->recorrente = 1;
                    
                    $ultimaRecorrencia = DemandaRecorrencia::where('campanha_id', $campanha->id)
                    ->orderBy('id', 'desc')
                    ->first();

                    if($demanda->status == 'Entregue' || $demanda->status == 'Finalizado'){
                        $demanda->entregue = 0;
                        $demanda->em_pauta = 0;
                        $demanda->finalizada = 0;
                        $demanda->recorrente = 1;
                        $demanda->pausado = 0;
                        $demanda->final = Carbon::parse($ultimaRecorrencia->data)->setTime(17, 0, 0);
                        $demanda->status = 'Pendente';
                        $demanda->save();
                    }else{
                        $recorrenciaData = Carbon::parse($ultimaRecorrencia->data)->startOfDay();
                        $demandaFinalData = Carbon::parse($demanda->final)->startOfDay();
        
                        if ($recorrenciaData->greaterThan($demandaFinalData)) {
                            $demanda->final = $recorrenciaData->setTime(17, 0, 0);
                            $demanda->recorrente = 1;
                            $demanda->save();
                            
                        }
                    }

                    $notificacoesReq = [];
                    foreach ($demandaUsuariosIds as $usuario) {
                        $notificacao = [
                            'usuario_id' => $usuario,
                            'demanda_id' => $demanda->id,
                            'criado' => date('Y-m-d H:i:s'),
                            'conteudo' => 'Criada uma nova recorrência ('.$request->campanha.')',
                            'visualizada' => '0',
                            'tipo' => 'criada',
                            'tipo_referencia' => 'campanha-recorrencia-'.$campanha->id,

                        ];
                        $notificacoesReq[] = $notificacao;
                    }

                    Notificacao::insert($notificacoesReq);

                    foreach($demandaUsuariosIds as $usuario) {
                        OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                    }
                    
                    $actionLink = route('Job', ['id' => $demanda->id]);
                    $bodyEmail = 'Criada nova recorrência com sucesso. Acesse pelo link logo abaixo.';
                    $titleEmail = 'Nova recorrência criada';
                    
                    foreach($demanda->demandasUsuario as $item){
                        if($item->notificar_email == 1){
                            Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $item) {
                                $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                                ->to($item->email)
                                ->subject('Nova recorrência criada');
                            });
                        }
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Recorrência criada com sucesso.'
                    ], 200);
                }
            }

            if($response){
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível criar essa campanha!'
                ], 400);
            }

        }

    }

    public function getRecorrenciaMensalAction(Request $request, $id){
        $validator = Validator::make($request->all(),[
            'campanha' => 'required|max:255',
            'titulo' => 'max:255',
            'data' => 'required',
            ],[
                'campanha.required' => 'Preencha o campo título da campanha.',
                'campanha.max' => 'O campo título da campanha deve ter no máximo 255 caracteres.',
                'titulo.max' => 'O campo título deve ter no máximo 255 caracteres.',
                'data.required' => 'Preencha o campo data.',
            ]
        );

        if($validator->fails()) {
             return response()->json([
                 'success' => false,
                 'message' => $validator->errors()->first(),
             ], 400);
        }

        if($id){
            $recorrencia = DemandaRecorrencia::where('id',$id)->with('DemandaCampanhaRecorrencia')->first();
            $campanha = DemandaCampanhaRecorrencia::where('id',$recorrencia->campanha_id)->first();


            if($request->titulo){
                $recorrencia->titulo = $request->titulo;
            }

            if($request->data){
                $recorrencia->data = $request->data;
                DemandaAtrasadaRecorrencia::where('recorrencia_id', $recorrencia->id)->delete();
            }

            if($request->descricao){
                $recorrencia->descricao = $request->descricao;
            }

            $response = $recorrencia->save();

            if($response){

                $demanda = Demanda::select('id', 'final')->where('id', $campanha->demanda_id)->first();
                
                $recorrenciaData = Carbon::parse($recorrencia->data)->startOfDay();
                $demandaFinalData = Carbon::parse($demanda->final)->startOfDay();

                if ($recorrenciaData->greaterThan($demandaFinalData)) {
                    $demanda->final = $recorrenciaData->setTime(17, 0, 0);
                    $demanda->save();
                }

                if($request->campanha){
                    $campanha->titulo = $request->campanha;
                    $campanha->save();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Recorrência criada com sucesso.'
                ], 200);
            }

        }
    }

    public function getRecorrenciaAnualAction(Request $request, $id){

        $validator = Validator::make($request->all(),[
            'campanha' => 'required|max:255',
            'titulo' => 'max:255',
            'data' => 'required',

            ],[
                'campanha.required' => 'Preencha o campo título da campanha.',
                'campanha.max' => 'O campo título da campanha deve ter no máximo 255 caracteres.',
                'titulo.max' => 'O campo título deve ter no máximo 255 caracteres.',
                'data.required' => 'Preencha o campo data.',
            ]
        );

        if($validator->fails()) {
             return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
             ], 400);
        }

        if($id){
            $recorrencia = DemandaRecorrencia::where('id',$id)->with('DemandaCampanhaRecorrencia')->first();
            $campanha = DemandaCampanhaRecorrencia::where('id',$recorrencia->campanha_id)->first();


            if($request->titulo){
                $recorrencia->titulo = $request->titulo;
            }

            if($request->data){
                // $anoAtual = Carbon::createFromFormat('Y', substr($request->data, 0, 4))->year;
                // $mes = date('m', strtotime($request->data));
                // $dia = date('d', strtotime($request->data));
                // $data = Carbon::createFromDate($anoAtual, $mes, $dia);
                $recorrencia->data = $request->data;
                DemandaAtrasadaRecorrencia::where('recorrencia_id', $recorrencia->id)->delete();
            }

            if($request->descricao){
                $recorrencia->descricao = $request->descricao;
            }

            $response = $recorrencia->save();

            if($response){

                $demanda = Demanda::select('id', 'final')->where('id', $campanha->demanda_id)->first();
                
                $recorrenciaData = Carbon::parse($recorrencia->data)->startOfDay();
                $demandaFinalData = Carbon::parse($demanda->final)->startOfDay();

                if ($recorrenciaData->greaterThan($demandaFinalData)) {
                    $demanda->final = $recorrenciaData->setTime(17, 0, 0);
                    $demanda->save();
                }

                if($request->campanha){
                    $campanha->titulo = $request->campanha;
                    $campanha->save();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Recorrência criada com sucesso.'
                ], 200);
            }

        }
    }


    public function getRecorrenciaSemanalAction(Request $request, $id){

        $validator = Validator::make($request->all(),[
            'campanha' => 'required|max:255',
            'titulo' => 'max:255',
            'data' => 'required',

            ],[
                'campanha.required' => 'Preencha o campo título da campanha.',
                'campanha.max' => 'O campo título da campanha deve ter no máximo 255 caracteres.',
                'titulo.max' => 'O campo título deve ter no máximo 255 caracteres.',
                'data.required' => 'Preencha o campo data.',
            ]
        );

        if($validator->fails()) {
             return response()->json([
                 'success' => false,
                 'message' => $validator->errors()->first(),
             ], 400);
        }

        if($id){
            $recorrencia = DemandaRecorrencia::where('id',$id)->with('DemandaCampanhaRecorrencia')->first();
            $campanha = DemandaCampanhaRecorrencia::where('id',$recorrencia->campanha_id)->first();


            if($request->titulo){
                $recorrencia->titulo = $request->titulo;
            }

            if($request->data){
                $recorrencia->data = $request->data;
                DemandaAtrasadaRecorrencia::where('recorrencia_id', $recorrencia->id)->delete();
            }

            if($request->descricao){
                $recorrencia->descricao = $request->descricao;
            }

            $response = $recorrencia->save();

            if($response){
                
                $demanda = Demanda::select('id', 'final')->where('id', $campanha->demanda_id)->first();
                
                $recorrenciaData = Carbon::parse($recorrencia->data)->startOfDay();
                $demandaFinalData = Carbon::parse($demanda->final)->startOfDay();

                if ($recorrenciaData->greaterThan($demandaFinalData)) {
                    $demanda->final = $recorrenciaData->setTime(17, 0, 0);
                    $demanda->save();
                }

                if($request->campanha){
                    $campanha->titulo = $request->campanha;
                    $campanha->save();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Recorrência criada com sucesso.'
                ], 200);
            }

        }
    }

    public function getRecorrenciaMensalCreateAction(Request $request, $id){

        $validator = Validator::make($request->all(), [
            'inicio' => 'required',
            'final' => 'required',
            'dia_ocorrencia' => ['required', 'numeric', function ($attribute, $value, $fail) {
                if ($value < 1 || $value > 31) {
                    $fail('O campo dia deve estar entre 1 e 31.');
                }
            }],
        ], [
            'inicio.required' => 'Preencha o campo início.',
            'final.required' => 'Preencha o campo final.',
            'dia_ocorrencia.required' => 'Preencha o campo dia.',
            'dia_ocorrencia.numeric' => 'O campo dia deve ser um número.',
        ]);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        if($id){

            if ($request->final < $request->inicio) {
                return response()->json([
                    'success' => false,
                    'message' => 'A data de início não pode ser inferior que a data final!'
                ], 400);
            }

            $inicio = Carbon::createFromFormat('Y-m', $request->inicio);
            $final = Carbon::createFromFormat('Y-m', $request->final);

            $diferencaMeses = $inicio->diffInMonths($final);
            $recorrencias = [];
            for ($i = 0; $i <= $diferencaMeses; $i++) {
                $mes = $inicio->copy()->addMonths($i);

                if ($mes->lt($inicio)) {
                    continue;
                }

                // Verifica se o dia fornecido é maior que o último dia do mês
                $ultimoDiaMes = $mes->endOfMonth()->day;
                if ($request->dia_ocorrencia > $ultimoDiaMes) {
                    // Se for, ajusta para o último dia do mês
                    $inicioDia = $mes->copy()->endOfMonth();
                } else {
                    // Senão, define o dia normalmente
                    $inicioDia = $mes->copy()->startOfMonth()->addDays($request->dia_ocorrencia - 1);
                }

                // Verifica se a data resultante é um sábado ou domingo
                if ($inicioDia->isWeekend()) {
                    // Se for, ajusta para sexta-feira anterior
                    // $inicioDia = $inicioDia->previousWeekday();
                    $inicioDia = $inicioDia->nextWeekday();
                }

                $recorrencia = [
                    'campanha_id' => $id,
                    'data' => $inicioDia->format('Y-m-d'),
                    'tipo' =>  'Mensal',
                    'criado' => date('Y-m-d H:i:s'),
                    'status' => 'Pendente',
                ];

                $recorrencias[] = $recorrencia;
            }

            $response = DemandaRecorrencia::insert($recorrencias);

            if($response){
                $campanha = DemandaCampanhaRecorrencia::where('id', $id)->first();
                $demanda = Demanda::where('id', $campanha->demanda_id)->with('demandasUsuario')->first();
                $demanda->recorrente = 1;
                $demandaUsuariosIds = $demanda->demandasUsuario->pluck('id')->toArray();
                $ultimaRecorrencia = DemandaRecorrencia::where('campanha_id', $campanha->id)
                ->orderBy('id', 'desc')
                ->first();

                if($demanda->status == 'Entregue' || $demanda->status == 'Finalizado'){
                    $demanda->entregue = 0;
                    $demanda->em_pauta = 0;
                    $demanda->finalizada = 0;
                    $demanda->recorrente = 1;
                    $demanda->pausado = 0;
                    $demanda->final = Carbon::parse($ultimaRecorrencia->data)->setTime(17, 0, 0);
                    $demanda->status = 'Pendente';
                    $demanda->save();
                }else{
                    $ultimaRecorrenciaData = Carbon::parse($ultimaRecorrencia->data)->startOfDay();
                    $demandaFinalData = Carbon::parse($demanda->final)->startOfDay();

                    if ($ultimaRecorrenciaData->greaterThan($demandaFinalData)) {
                        $demanda->final = $ultimaRecorrenciaData->setTime(17, 0, 0);
                        $demanda->recorrente = 1;
                        $demanda->save();
                    }
                }

                $notificacoesReq = [];
                foreach ($demandaUsuariosIds as $usuario) {
                    $notificacao = [
                        'usuario_id' => $usuario,
                        'demanda_id' => $demanda->id,
                        'criado' => date('Y-m-d H:i:s'),
                        'conteudo' => 'Criada nova recorrência ('.$campanha->titulo.')',
                        'visualizada' => '0',
                        'tipo' => 'criada',
                        'tipo_referencia' => 'campanha-recorrencia-'.$id,

                    ];
                    $notificacoesReq[] = $notificacao;
                }

                Notificacao::insert($notificacoesReq);

                foreach($demandaUsuariosIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                $actionLink = route('Job', ['id' => $demanda->id]);
                $bodyEmail = 'Sua nova recorrência mensal da campanha "' . $campanha->titulo . '" foi criado com sucesso. Acesse pelo link logo abaixo.';
                $titleEmail = 'Nova recorrência mensal criada';
                    
                foreach($demanda->demandasUsuario as $item){
                    if($item->notificar_email == 1){
                        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $item) {
                            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                            ->to($item->email)
                            ->subject('Nova recorrência mensal criada');
                        });
                    }
                }

                $campanha->finalizada = 0;
                $campanha->save();

                return response()->json([
                    'success' => true,
                    'message' => 'Recorrência criada com sucesso.'
                ], 200);
            }

        }

    }

    public function getRecorrenciaAnualCreateAction(Request $request, $id){

        $validator = Validator::make($request->all(),[
                'anoInicial' => 'required',
                'anoFinal' => 'required',

            ],[
                'anoInicial.required' => 'Preencha o campo ano inicial.',
                'anoFinal.required' => 'Preencha o campo ano final.',
            ]
        );

        if($validator->fails()) {
             return response()->json([
                 'success' => false,
                 'message' => $validator->errors()->first(),
             ], 400);
        }

        if($id){
            
            $anoAtual = Carbon::createFromFormat('Y', substr($request->anoInicial, 0, 4))->year;
            $anoFinal = Carbon::createFromFormat('Y', substr($request->anoFinal, 0, 4))->year;
            $mes = date('m', strtotime($request->anoInicial));
            $dia = date('d', strtotime($request->anoInicial));
            $diferencaAnos = $anoFinal - $anoAtual;
            $recorrencias = [];

            for ($i = 0; $i <= $diferencaAnos; $i++) {
                $ano = $anoAtual + $i;

                $data = Carbon::createFromDate($ano, $mes, $dia);

                if ($data->isWeekend()) {
                    // $data = $data->previousWeekday();
                    $data = $data->nextWeekday();
                }

                $recorrencia = [
                    'campanha_id' => $id,
                    'data' => $data->format('Y-m-d'),
                    'tipo' =>  'Anual',
                    'criado' => date('Y-m-d H:i:s'),
                    'status' => 'Pendente',
                ];

                $recorrencias[] = $recorrencia;
            }

            $response = DemandaRecorrencia::insert($recorrencias);

            if($response){

                $campanha = DemandaCampanhaRecorrencia::where('id', $id)->first();
                $demanda = Demanda::where('id', $campanha->demanda_id)->with('demandasUsuario')->first();
                $demandaUsuariosIds = $demanda->demandasUsuario->pluck('id')->toArray();
                $demanda->recorrente = 1;

                $ultimaRecorrencia = DemandaRecorrencia::where('campanha_id', $campanha->id)
                ->orderBy('id', 'desc')
                ->first();

                if($demanda->status == 'Entregue' || $demanda->status == 'Finalizado'){
                    $demanda->entregue = 0;
                    $demanda->em_pauta = 0;
                    $demanda->finalizada = 0;
                    $demanda->recorrente = 1;
                    $demanda->pausado = 0;
                    $demanda->final = Carbon::parse($ultimaRecorrencia->data)->setTime(17, 0, 0);
                    $demanda->status = 'Pendente';
                    $demanda->save();
                }else{
                    $ultimaRecorrenciaData = Carbon::parse($ultimaRecorrencia->data)->startOfDay();
                    $demandaFinalData = Carbon::parse($demanda->final)->startOfDay();

                    if ($ultimaRecorrenciaData->greaterThan($demandaFinalData)) {
                        $demanda->final = $ultimaRecorrenciaData->setTime(17, 0, 0);
                        $demanda->recorrente = 1;
                        $demanda->save();
                    }
                }

                $notificacoesReq = [];
                foreach ($demandaUsuariosIds as $usuario) {
                    $notificacao = [
                        'usuario_id' => $usuario,
                        'demanda_id' => $demanda->id,
                        'criado' => date('Y-m-d H:i:s'),
                        'conteudo' => 'Criada nova recorrência ('.$campanha->titulo.')',
                        'visualizada' => '0',
                        'tipo' => 'criada',
                    ];
                    $notificacoesReq[] = $notificacao;
                }

                Notificacao::insert($notificacoesReq);

                $campanha->finalizada = 0;
                $campanha->save();

                foreach($demandaUsuariosIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                $actionLink = route('Job', ['id' => $demanda->id]);
                $bodyEmail = 'Sua nova recorrência anual da campanha "' . $campanha->titulo . '" foi criado com sucesso. Acesse pelo link logo abaixo.';
                $titleEmail = 'Nova recorrência anual criada';
                    
                foreach($demanda->demandasUsuario as $item){
                    if($item->notificar_email == 1){
                        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $item) {
                            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                            ->to($item->email)
                            ->subject('Nova recorrência anual criada');
                        });
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Recorrência criada com sucesso.'
                ], 200);
            }

        }
    }

    public function getRecorrenciaSemanalCreateAction(Request $request, $id){
        $validator = Validator::make($request->all(),[
            'titulo' => 'max:255',
            'dateRange' => 'required',

            ],[
                'titulo.max' => 'O campo título deve ter no máximo 255 caracteres.',
                'dateRange.required' => 'Preencha o campo data.',
            ]
        );

        if($validator->fails()) {
             return response()->json([
                 'success' => false,
                 'message' => $validator->errors()->first(),
             ], 400);
        }

        if($id){

            $dateRange = $request->dateRange;
            $datas = explode(' | ', $dateRange);

            $recorrencias = [];

            foreach($datas as $item){
                $recorrencia = [
                    'campanha_id' => $id,
                    'tipo' => 'Semanal',
                    'data' => date('Y-m-d', strtotime($item)),
                    'status' => 'Pendente',
                    'criado' => date('Y-m-d H:i:s'),
                ];

                $recorrencias[] = $recorrencia;
            }

            $response = DemandaRecorrencia::insert($recorrencias);


            if($response){

                $campanha = DemandaCampanhaRecorrencia::where('id', $id)->first();
                $demanda = Demanda::where('id', $campanha->demanda_id)->with('demandasUsuario')->first();
                $demandaUsuariosIds = $demanda->demandasUsuario->pluck('id')->toArray();
                $demanda->recorrente = 1;

                $ultimaRecorrencia = DemandaRecorrencia::where('campanha_id', $campanha->id)
                ->orderBy('id', 'desc')
                ->first();

                if($demanda->status == 'Entregue' || $demanda->status == 'Finalizado'){
                    $demanda->entregue = 0;
                    $demanda->em_pauta = 0;
                    $demanda->finalizada = 0;
                    $demanda->recorrente = 1;
                    $demanda->pausado = 0;
                    $demanda->final = Carbon::parse($ultimaRecorrencia->data)->setTime(17, 0, 0);
                    $demanda->status = 'Pendente';
                    $demanda->save();
                }else{
                    $ultimaRecorrenciaData = Carbon::parse($ultimaRecorrencia->data)->startOfDay();
                    $demandaFinalData = Carbon::parse($demanda->final)->startOfDay();

                    if ($ultimaRecorrenciaData->greaterThan($demandaFinalData)) {
                        $demanda->final = $ultimaRecorrenciaData->setTime(17, 0, 0);
                        $demanda->recorrente = 1;
                        $demanda->save();
                    }
                }

                $notificacoesReq = [];
                foreach ($demandaUsuariosIds as $usuario) {
                    $notificacao = [
                        'usuario_id' => $usuario,
                        'demanda_id' => $demanda->id,
                        'criado' => date('Y-m-d H:i:s'),
                        'conteudo' => 'Criada nova recorrência ('.$campanha->titulo.')',
                        'visualizada' => '0',
                        'tipo' => 'criada',
                        'tipo_referencia' => 'campanha-recorrencia-'.$id,

                    ];
                    $notificacoesReq[] = $notificacao;
                }

                Notificacao::insert($notificacoesReq);

                $campanha->finalizada = 0;
                $campanha->save();

                foreach($demandaUsuariosIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                $actionLink = route('Job', ['id' => $demanda->id]);
                $bodyEmail = 'Sua nova recorrência semanal da campanha "' . $campanha->titulo . '" foi criado com sucesso. Acesse pelo link logo abaixo.';
                $titleEmail = 'Nova recorrência semanal criada';
                    
                foreach($demanda->demandasUsuario as $item){
                    if($item->notificar_email == 1){
                        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $item) {
                            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                            ->to($item->email)
                            ->subject('Nova recorrência semanal criada');
                        });
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Recorrência criada com sucesso.'
                ], 200);
            }

        }
    }

    public function recorrenciaDelete($id){
        $campanhaRecorrencia = DemandaCampanhaRecorrencia::findOrFail($id);

        $recorrenciaIds = $campanhaRecorrencia->recorrencias->pluck('id')->toArray();
        $comentarioIds = DemandaRecorrenciaComentario::whereIn('recorrencia_id', $recorrenciaIds)->pluck('id')->toArray();
        $altercaoIds = DemandaRecorrenciaAjuste::whereIn('recorrencia_id', $recorrenciaIds)->pluck('id')->toArray();

        if($campanhaRecorrencia){
            $idDemanda = $campanhaRecorrencia->demanda_id;
            Notificacao::where('tipo_referencia', 'campanha-recorrencia-'.$campanhaRecorrencia->id)->delete();

            $referencias = collect($recorrenciaIds)->map(function ($recId) {
                return 'recorrencia-' . $recId;
            })->toArray();

            $referenciasComentarios = collect($comentarioIds)->map(function ($recId) {
                return 'comentario-recorrencia-' . $recId;
            })->toArray();

            $referenciasAlteracoes = collect($altercaoIds)->map(function ($recId) {
                return 'recorrencia-alteracao-' . $recId;
            })->toArray();

            if($referencias){
                Notificacao::whereIn('tipo_referencia', $referencias)->delete();
            }

            if($referenciasComentarios){
                Notificacao::whereIn('tipo_referencia', $referenciasComentarios)->delete();
            }

            if($referenciasAlteracoes){
                Notificacao::whereIn('tipo_referencia', $referenciasAlteracoes)->delete();
            }

            $countCampanhas = DemandaCampanhaRecorrencia::where('demanda_id', $idDemanda)->count();

            $campanhaRecorrencia->delete();
            DemandaAtrasadaRecorrencia::whereIn('recorrencia_id', $recorrenciaIds)->delete();

            if($countCampanhas == 0){
                $demanda = Demanda::select('id', 'recorrente')->where('id', $idDemanda)->first();
                $demanda->recorrente = 0;
                $demanda->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Excluído com sucesso.',
            ], 200);
        }

        return response()->json([
            'type' => 'error',
            'message' => 'Não foi possível excluir!'
        ], 400);

    }

    public function recorrenciaSingleDelete($id)
    {
        $recorrencia = DemandaRecorrencia::findOrFail($id);
        $campanhaId = $recorrencia->campanha_id;

        if ($recorrencia) {
            $comentarioIds = DemandaRecorrenciaComentario::where('recorrencia_id', $recorrencia->id)->pluck('id')->toArray();
            $alteraçõesIds = DemandaRecorrenciaAjuste::where('recorrencia_id', $recorrencia->id)->pluck('id')->toArray();

            if ($comentarioIds) {
                $referenciasComentarios = collect($comentarioIds)->map(function ($comentarioId) {
                    return 'comentario-recorrencia-' . $comentarioId;
                })->toArray();

                Notificacao::whereIn('tipo_referencia', $referenciasComentarios)->delete();
            }

            if ($alteraçõesIds) {
                $referenciasAlteracoes = collect($alteraçõesIds)->map(function ($alteracaoId) {
                    return 'recorrencia-alteracao-' . $alteracaoId;
                })->toArray();

                Notificacao::whereIn('tipo_referencia', $referenciasAlteracoes)->delete();
            }

            Notificacao::where('tipo_referencia', 'recorrencia-' . $recorrencia->id)->delete();

            $recorrencia->delete();

            $countReq = DemandaRecorrencia::where('campanha_id', $campanhaId)->where('finalizado', 0)->count();
            DemandaAtrasadaRecorrencia::where('recorrencia_id', $recorrencia->id)->delete();

            if($countReq == 0){
                $campanhaAjuste = DemandaCampanhaRecorrencia::where('id', $campanhaId)->first();
                $campanhaAjuste->finalizada = 1;
                $campanhaAjuste->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Excluído com sucesso.',
            ], 200);
        }

        return response()->json([
            'type' => 'error',
            'message' => 'Não foi possível excluir!'
        ], 400);

    }

    public function getRecorrenciaAjusteCreateAction(Request $request){

        $validator = Validator::make($request->all(), [
            'descricao' => 'required',
            'data' => 'required',
        ]);

        $commonMessages = [
            'descricao.required' => 'Preencha o campo descrição.',
            'data.required' => 'Preencha o campo data.',
        ];

        $recorrencia = DemandaRecorrencia::find($request->id);

        $validator->setCustomMessages($commonMessages);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        if(!$validator->fails()){

            $recAjuste = new DemandaRecorrenciaAjuste();
            $recAjuste->recorrencia_id = $recorrencia->id;
            $recAjuste->status = 'Pendente';
            $recAjuste->descricao = $request->descricao;
            $recAjuste->tipo = $recorrencia->tipo;


            if($request->data){
                $recAjuste->data = $request->data;
            }

            $recAjuste->criado = date('Y-m-d H:i:s');

            $response = $recAjuste->save();


            if($response){

                $campanha = DemandaCampanhaRecorrencia::where('id', $recorrencia->campanha_id)->first();
                $recorrencia = DemandaRecorrencia::where('id', $recorrencia->id)->first();
                $demanda = Demanda::where('id', $campanha->demanda_id)->with('demandasUsuario')->first();
                $demandaUsuariosIds = $demanda->demandasUsuario->pluck('id')->toArray();

                if($demanda->status == 'Entregue' || $demanda->status == 'Finalizado'){
                    $demanda->entregue = 0;
                    $demanda->em_pauta = 0;
                    $demanda->finalizada = 0;
                    $demanda->pausado = 0;
                    $demanda->status = 'Pendente';
                    $demanda->save();
                }

                $recorrenciaAjusteData = Carbon::parse($recAjuste->data)->startOfDay();
                $demandaFinalData = Carbon::parse($demanda->final)->startOfDay();
                
                if ($recorrenciaAjusteData->greaterThan($demandaFinalData)) {
                    $demanda->final = $recorrenciaAjusteData->setTime(17, 0, 0);
                    $demanda->save();
                }

                $notificacoesReq = [];
                foreach ($demandaUsuariosIds as $usuario) {
                    $notificacao = [
                        'usuario_id' => $usuario,
                        'demanda_id' => $demanda->id,
                        'criado' => date('Y-m-d H:i:s'),
                        'conteudo' => 'Criada uma nova alteração ('.$campanha->titulo.')',
                        'visualizada' => '0',
                        'tipo' => 'criada',
                        'tipo_referencia' => 'recorrencia-alteracao-'.$recAjuste->id

                    ];
                    $notificacoesReq[] = $notificacao;
                }

                Notificacao::insert($notificacoesReq);
                
                if($recorrencia->finalizado == 1 || $recorrencia->entregue == 1){
                    $recorrencia->status = 'Em alteração';                       
                    $recorrencia->em_alteracao = 1;                       
                    $recorrencia->finalizado = 0;                       
                    $recorrencia->entregue = 0;                       
                    $recorrencia->save();                       
                }

                if($campanha->finalizada == 1){
                    $campanha->finalizada = 0;
                    $campanha->save();
                }

                if (Carbon::parse($recAjuste->data)->gt(Carbon::parse($recorrencia->data))) {
                    if ($recorrencia->finalizado == 0) {
                        $recorrencia->data = $recAjuste->data;
                        $recorrencia->save();
                        DemandaAtrasadaRecorrencia::where('recorrencia_id', $recorrencia->id)->delete();
                    }
                }

                foreach($demandaUsuariosIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                $actionLink = route('Job', ['id' => $demanda->id]);
                $ajusteBody = '';
                
                if ($campanha->titulo && $recorrencia->titulo) {
                    $ajusteBody = 'Uma nova alteração foi criada na recorrência "' . $recorrencia->titulo . '" da campanha "' . $campanha->titulo . '". Acesse pelo link abaixo.';
                } elseif ($campanha->titulo && !$recorrencia->titulo) {
                    $ajusteBody = 'Uma nova alteração foi criada na campanha "' . $campanha->titulo . '". Acesse pelo link abaixo.';
                } elseif (!$campanha->titulo && $recorrencia->titulo) {
                    $ajusteBody = 'Uma nova alteração foi criada na recorrência "' . $recorrencia->titulo . '". Acesse pelo link abaixo.';
                }

                $bodyEmail = $ajusteBody;
                $titleEmail = 'Criada nova alteração de recorrência';
                    
                foreach($demanda->demandasUsuario as $item){
                    if($item->notificar_email == 1){
                        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $item) {
                            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                            ->to($item->email)
                            ->subject('Criada nova alteração de recorrência');
                        });
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Ajuste criado com sucesso.'
                ], 200);
            }

        }

    }

    public function getRecorrenciaAjusteEditAction(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'descricao' => 'required',
            'data' => 'required',
        ]);

        $commonMessages = [
            'descricao.required' => 'Preencha o campo descrição.',
            'data.required' => 'Preencha o campo data.',
        ];

        $recorrenciaAjuste = DemandaRecorrenciaAjuste::find($id);

        $validator->setCustomMessages($commonMessages);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        if(!$validator->fails()){

            if($request->data){
                $recorrenciaAjuste->data = $request->data;
            }

            if($request->descricao){
                $recorrenciaAjuste->descricao = $request->descricao;
            }

            $response = $recorrenciaAjuste->save();

            if($response){
                $recorrencia = DemandaRecorrencia::where('id', $recorrenciaAjuste->recorrencia_id)->first();
                if (Carbon::parse($recorrenciaAjuste->data)->gt(Carbon::parse($recorrencia->data))) {
                    if ($recorrencia->finalizado == 0) {
                        $recorrencia->data = $recorrenciaAjuste->data;
                        $recorrencia->save();
                        DemandaAtrasadaRecorrencia::where('recorrencia_id', $recorrencia->id)->delete();
                    }
                }

                $campanha = $recorrenciaAjuste->DemandaRecorrencia->DemandaCampanhaRecorrencia;

                $demanda = Demanda::select('id', 'final')->where('id', $campanha->demanda_id)->first();
                
                $recorrenciaAjusteData = Carbon::parse($recorrenciaAjuste->data)->startOfDay();
                $demandaFinalData = Carbon::parse($demanda->final)->startOfDay();

                if ($recorrenciaAjusteData->greaterThan($demandaFinalData)) {
                    $demanda->final = $recorrenciaAjusteData->setTime(17, 0, 0);
                    $demanda->save();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Editado com sucesso.',
                ], 200);
            }
        }
    }
 
    public function recorrenciaAjusteDelete($id){
        $recorrenciaAjuste = DemandaRecorrenciaAjuste::findOrFail($id);
        $oldRecorrencia = $recorrenciaAjuste;

        if($recorrenciaAjuste){
            $recorrenciaAjuste->delete();
            Notificacao::where('tipo_referencia', 'recorrencia-alteracao-'.$recorrenciaAjuste->id)->delete();
        
            $recorrencia = DemandaRecorrencia::where('id', $oldRecorrencia->recorrencia_id)->first();
    
            if ($recorrencia) {
                $campanha = DemandaCampanhaRecorrencia::where('id', $recorrencia->campanha_id)
                    ->with('recorrencias.ajustes')
                    ->first();

                $recorrencias = $campanha->recorrencias;
    
                $countAjustesEntreguesZero = $recorrencias->pluck('ajustes')->flatten()->where('entregue', 0)->count();
                
                if ($recorrencia->status == 'Em alteração' && $countAjustesEntreguesZero == 0 && $oldRecorrencia->entregue == 0) {
                    $campanha->finalizada = 1;
                    $campanha->save();
    
                    $recorrencia->status = 'Finalizado';
                    $recorrencia->finalizado = 1;
                    $recorrencia->entregue = 1;
                    $recorrencia->em_alteracao = 0;
                    $recorrencia->em_pauta = 0;
                    $recorrencia->save();
                }
    
                return response()->json([
                    'success' => true,
                    'message' => 'Excluído com sucesso.',
                ], 200);
            }
    
        }

        return response()->json([
            'type' => 'error',
            'message' => 'Não foi possível excluir esta alteração!'
        ], 400);
    }

    public function getRecorrenciaSingleAjusteAction($id){

        if($id){
            $recorrencia = DemandaRecorrenciaAjuste::findOrFail($id);
            return $recorrencia;
        }
        return false;

    }

    public function recorrenciaComentarioCreate(Request $request){
        $user = Auth::User();

        $validator = Validator::make($request->all(),[
            'descricao' => 'required',
            ],[
                'descricao.required' => 'Preencha o campo descrição.',
            ]
        );

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        if(!$validator->fails()){
            if($request->id){
                $comentario = new DemandaRecorrenciaComentario();
                $comentario->usuario_id = $user->id;
                $comentario->recorrencia_id = $request->id;
                $comentario->criado = date('Y-m-d H:i:s');
                $comentario->descricao = $request->descricao;

                $response = $comentario->save();

                if($response){
                    $recorrencia = DemandaRecorrencia::select('id', 'campanha_id')->where('id', $request->id)->first();
                    $campanha = DemandaCampanhaRecorrencia::where('id', $recorrencia->campanha_id)->first();
                    $demanda = Demanda::where('id', $campanha->demanda_id)->with('demandasUsuario')->with('demandaColaboradores')->first();
                    $demandaUsuariosIds = $demanda->demandasUsuario->pluck('id')->toArray();
                   
                    $demandaColaboradoresIds[] = $demanda->criador_id;
                    $demandaColaboradoresIds = array_merge($demandaColaboradoresIds, $demanda->demandaColaboradores->pluck('id')->toArray());
                    $demandaColaboradoresIds = array_unique($demandaColaboradoresIds);

                    $notificacoesReq = [];
                    $comentarioUsuarios = [];

                    if(in_array($user->id, $demandaColaboradoresIds)){
                        //enviar para agencia
                        foreach ($demandaUsuariosIds as $usuario) {
                            $notificacao = [
                                'usuario_id' => $usuario,
                                'demanda_id' => $demanda->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'conteudo' => 'Criado um novo comentário para a recorrência: '.$campanha->titulo,
                                'visualizada' => '0',
                                'tipo' => 'criada',
                                'tipo_referencia' => 'comentario-recorrencia-'.$comentario->id
                            ];
                            $notificacoesReq[] = $notificacao;

                            $comentarioUsuario = [
                                'usuario_id' => $usuario,
                                'comentario_id' => $comentario->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                            ];
                            $comentarioUsuarios[] = $comentarioUsuario;
                        }

                    }else if(in_array($user->id, $demandaUsuariosIds)){
                        //enviar para coloborador
                        foreach ($demandaColaboradoresIds as $usuario) {
                            $notificacao = [
                                'usuario_id' => $usuario,
                                'demanda_id' => $demanda->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'conteudo' => 'Criado um novo comentário para a recorrência: '.$campanha->titulo,
                                'visualizada' => '0',
                                'tipo' => 'criada',
                                'tipo_referencia' => 'comentario-recorrencia-'.$comentario->id
                            ];
                            $notificacoesReq[] = $notificacao;

                            $comentarioUsuario = [
                                'usuario_id' => $usuario,
                                'comentario_id' => $comentario->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                            ];
                            $comentarioUsuarios[] = $comentarioUsuario;
                        }
                    }

                    Notificacao::insert($notificacoesReq);
                    DemandaRecorrenciaComentarioLido::insert($comentarioUsuarios);

                    $campanha->finalizada = 0;
                    $campanha->save();

                    foreach($demandaUsuariosIds as $usuario) {
                        OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                    }

                    $ajusteBody = '';
                
                    if ($campanha->titulo && $recorrencia->titulo) {
                        $ajusteBody = 'Um novo comentário foi criado na recorrência "' . $recorrencia->titulo . '" da campanha "' . $campanha->titulo . '". Acesse pelo link abaixo.';
                    } elseif ($campanha->titulo && !$recorrencia->titulo) {
                        $ajusteBody = 'Um novo comentário foi criado na campanha "' . $campanha->titulo . '". Acesse pelo link abaixo.';
                    } elseif (!$campanha->titulo && $recorrencia->titulo) {
                        $ajusteBody = 'Um novo comentário foi criado na recorrência "' . $recorrencia->titulo . '". Acesse pelo link abaixo.';
                    }

                    $actionLink = route('Job', ['id' => $demanda->id]);
                    $bodyEmail = $ajusteBody;
                    $titleEmail = 'Criado novo comentário para demanda em recorrência';

                    $usersToNotifyByEmail = User::select('id', 'email', 'nome', 'notificar_email')
                    ->where('notificar_email', 1)
                    ->where(function ($query) use ($demandaColaboradoresIds, $demandaUsuariosIds) {
                        $query->whereIn('id', $demandaColaboradoresIds)
                            ->orWhereIn('id', $demandaUsuariosIds);
                    })
                    ->get();
                        
                    foreach($usersToNotifyByEmail as $item){
                        if($item->id != $user->id){
                            try {
                                EnviarMail::enviarEmail($item->email, $item->nome, $actionLink, $bodyEmail, $titleEmail, $comentario->descricao, $user->nome);
                            } catch (\Exception $e) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Oops, ocorreu um erro ao enviar uma notificação via e-mail, mas sua mensagem foi cadastrada em nosso sistema!'
                                ], 400);
                            }
                        }
                    }

                    return response()->json([
                        'success' => true,
                        'message' => 'Comentário criado com sucesso.'
                    ], 200);
                }

            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível adicionar esse cometário.'
                ], 400);
            }
        }
    }

    public function recorrenciaComentarioGetEdit($id){

        if($id){
            $comentario = DemandaRecorrenciaComentario::findOrFail($id);
            return $comentario;
        }
        return false;

    }

    public function recorrenciaComentarioEdit(Request $request, $id){

        $validator = Validator::make($request->all(),[
            'descricao' => 'required',
            ],[
                'descricao.required' => 'Preencha o campo descrição.',
            ]
        );

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        if(!$validator->fails()){
            $recorrenciaComentario = DemandaRecorrenciaComentario::find($id);

            if($recorrenciaComentario){

                $recorrenciaComentario->descricao = $request->descricao;

                $response = $recorrenciaComentario->save();
                if($response){
                    return response()->json([
                        'success' => true,
                        'message' => 'Comentário atualizado com sucesso.'
                    ], 200);
                }

            }
        }
    }

    public function recorrenciaComentarioDelete($id){
        $comentario = DemandaRecorrenciaComentario::findOrFail($id);

        if($comentario){
            Notificacao::where('tipo_referencia', 'comentario-recorrencia-'.$id)->delete();
            $comentario->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comentário excluído com sucesso.'
            ], 200);
        }

        return response()->json([
            'type' => 'error',
            'message' => 'Não foi possível excluir esse comentário!'
        ], 400);

    }

    public function recorrenciaComentarioRead($id){
        $user = Auth::User();
        $recorrencia = DemandaRecorrencia::findOrFail($id);
        $recorrenciaComentarios = DemandaRecorrenciaComentario::select('id', 'recorrencia_id')->where('recorrencia_id', $recorrencia->id)->get();
        $demandaRecorrenciaIds = $recorrenciaComentarios->pluck('id')->toArray();

        foreach ($demandaRecorrenciaIds as $id) {
            DemandaRecorrenciaComentarioLido::where('comentario_id', $id)->where('usuario_id', $user->id)
            ->update(['visualizada' => 1]);
        }
    }

    public function startRecorrencia($id){
        $user = Auth::User();
        $recorrencia = DemandaRecorrencia::with('DemandaCampanhaRecorrencia.demandas.demandaColaboradores')->findOrFail($id);
      
        if($recorrencia){
            $recorrencia->em_pauta = 1;
            $recorrencia->status = 'Em pauta';
            $response = $recorrencia->save();

            if($response){
                $demanda = $recorrencia->DemandaCampanhaRecorrencia->demandas;
                
                $conteudo = 'Uma nova recorrência foi iniciada. '.$recorrencia->DemandaCampanhaRecorrencia->titulo;
                $this->criarNotificacoesRecorrencia($demanda, $user, $conteudo);

                return response()->json([
                    'success' => true,
                    'message' => 'Recorrência iniciada com sucesso.'
                ], 200);
            }
        }
    }

    public function deliverRecorrencia(Request $request, $id){
        $user = Auth::User();
        $dataAtual = date('Y-m-d');
        $recorrencia = DemandaRecorrencia::where('id', $request->id)->first();
        $campanha = DemandaCampanhaRecorrencia::where('id', $recorrencia->campanha_id)->first();
        $demanda = Demanda::where('id', $campanha->demanda_id)->with('demandaColaboradores')->with('demandasUsuario')->first();
        $demandaColaboradoresIds[] = $demanda->criador_id;
        $demandaColaboradoresIds = array_merge($demandaColaboradoresIds, $demanda->demandaColaboradores->pluck('id')->toArray());
        $demandaColaboradoresIds = array_unique($demandaColaboradoresIds);

        if($recorrencia){
            $recorrencia->entregue = 1;
            if($dataAtual > $recorrencia->data){
                $recorrencia->atrasada = 1;
            }else{
                $recorrencia->atrasada = 0;
            }

            $recorrencia->status = 'Entregue';
            $recorrencia->data_entrega = date('Y-m-d H:i:s');

            $response = $recorrencia->save();

            if ($response) {
                
                $notificacoesReq = [];
                foreach ($demandaColaboradoresIds as $usuario) {
                    if ($user->id != $usuario) {
                        $notificacao = [
                            'usuario_id' => $usuario,
                            'demanda_id' => $demanda->id,
                            'criado' => date('Y-m-d H:i:s'),
                            'conteudo' => '('.$campanha->titulo. ')' . ' Entregue a recorrência do dia '.  Carbon::parse($recorrencia->data)->locale('pt_BR')->isoFormat('DD/MM/YYYY'),
                            'visualizada' => '0',
                            'tipo' => 'criada',

                        ];
                        $notificacoesReq[] = $notificacao;
                    }
                }

                Notificacao::insert($notificacoesReq);

                foreach($demandaColaboradoresIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                DemandaAtrasadaRecorrencia::where('recorrencia_id', $recorrencia->id)->delete();
                
                $actionLink = route('Job', ['id' => $demanda->id]);
                $bodyEmail = 'A recorrência com a data de '. Carbon::parse($recorrencia->data)->locale('pt_BR')->isoFormat('DD/MM/YYYY') .' foi entregue com sucesso. Acesse pelo link logo abaixo.';
                $titleEmail = 'Recorrência entregue';

                $usersToNotifyByEmail = User::select('id', 'email', 'nome', 'notificar_email')
                ->where('notificar_email', 1)
                ->where(function ($query) use ($demandaColaboradoresIds) {
                    $query->whereIn('id', $demandaColaboradoresIds);
                })
                ->get();
                
                foreach($usersToNotifyByEmail as $item){
                    if($item->notificar_email == 1){
                        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($item) {
                            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                            ->to($item->email)
                            ->subject('Recorrência entregue');
                        });
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Alterado com sucesso.',
                    'id' => $recorrencia->campanha_id
                ], 200);
            }

        }

        return response()->json([
            'type' => 'error',
            'message' => 'Não foi possível entregar este job!'
        ], 400);

    }

    public function finalizeRecorrencia(Request $request, $id){
        $user = Auth::User();
        $recorrencia = DemandaRecorrencia::where('id', $request->id)->first();
        $recorrencias = DemandaRecorrencia::where('campanha_id', $recorrencia->campanha_id)->get();
        $campanha = DemandaCampanhaRecorrencia::where('id', $recorrencia->campanha_id)->first();
        $demanda = Demanda::where('id', $campanha->demanda_id)->with('demandasUsuario')->first();
       
        $demandaUsuariosIds = $demanda->demandasUsuario->pluck('id')->toArray();

        if($recorrencia){
            $recorrencia->finalizado = 1;
            $recorrencia->status = 'Finalizado';
            $response = $recorrencia->save();

            if ($response) {
                $recorrencias = DemandaRecorrencia::where('campanha_id', $recorrencia->campanha_id)->get();

                $porcentagem = '';
                foreach ($recorrencias as $key => $item) {
                    $totalRecorrencias = $recorrencias->count();

                    $recorrenciasFinalizadas = $recorrencias->filter(function ($rec) {
                        return $rec->finalizado == '1';
                    })->count();

                    $porcentagemEntregue = $totalRecorrencias > 0 ? ($recorrenciasFinalizadas / $totalRecorrencias) * 100 : 0;

                    $porcentagem = number_format($porcentagemEntregue, 1);
                }

                $countAjuste = DemandaCampanhaRecorrencia::where('id', $recorrencia->campanha_id)
                ->whereHas('recorrencias', function ($query) {
                    $query->whereHas('ajustes', function ($query) {
                        $query->where('entregue', 0);
                    });
                })
                ->count();

                if($porcentagem == 100){
                    $conteudoN =  '('.$campanha->titulo. ')' .' todas as recorrências foram finalizadas';
                    if($countAjuste == 0){
                        $campanha->finalizada = 1;
                        $campanha->save();
                    }
                    Notificacao::where('conteudo', 'like', "%$conteudoN%")->delete();

                    $notificacoesReq = [];
                    foreach ($demandaUsuariosIds as $usuario) {
                        if ($user->id != $usuario) {
                            $notificacao = [
                                'usuario_id' => $usuario,
                                'demanda_id' => $demanda->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'conteudo' => $conteudoN,
                                'visualizada' => '0',
                                'tipo' => 'criada',

                            ];
                            $notificacoesReq[] = $notificacao;
                        }
                    }

                    Notificacao::insert($notificacoesReq);

                }else if($porcentagem < 100 && $recorrencia->finalizado == 1){
                    $notificacoesReq = [];
                    foreach ($demandaUsuariosIds as $usuario) {
                        if ($user->id != $usuario) {
                            $notificacao = [
                                'usuario_id' => $usuario,
                                'demanda_id' => $demanda->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'conteudo' => '('.$campanha->titulo. ')' . ' Finalizada a recorrência de '.  Carbon::parse($recorrencia->data)->locale('pt_BR')->isoFormat('DD/MM/YYYY'),
                                'visualizada' => '0',
                                'tipo' => 'criada',

                            ];
                            $notificacoesReq[] = $notificacao;
                        }
                    }

                    Notificacao::insert($notificacoesReq);
                }

                foreach($demandaUsuariosIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                $actionLink = route('Job', ['id' => $demanda->id]);
                $bodyEmail = 'A recorrência com a data de '. Carbon::parse($recorrencia->data)->locale('pt_BR')->isoFormat('DD/MM/YYYY') .' da campanha "' . $campanha->titulo . '" foi finalizada com sucesso. Acesse pelo link logo abaixo.';
                $titleEmail = 'Recorrência finalizada';

                DemandaAtrasadaRecorrencia::where('recorrencia_id', $recorrencia->id)->delete();
                
                foreach($demanda->demandasUsuario as $item){
                    if($item->notificar_email == 1){
                        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $item) {
                            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                            ->to($item->email)
                            ->subject('Recorrência finalizada');
                        });
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Alterado com sucesso.',
                    'porcentagem' => $porcentagem,
                    'id' => $recorrencia->campanha_id
                ], 200);
            }

        }

        return response()->json([
            'type' => 'error',
            'message' => 'Não foi possível finalizar este job!'
        ], 400);

    }

    public function newDate(Request $request, $id){
        $user = Auth::user();
        $recorrencia = DemandaRecorrencia::with('DemandaCampanhaRecorrencia.demandas')->findOrFail($id);

        if ($recorrencia) {
            $recorrencia->data = $request->data_recorrencia;
            $response = $recorrencia->save();

            if ($response) {
                $demanda = $recorrencia->DemandaCampanhaRecorrencia->demandas;
                DemandaAtrasadaRecorrencia::where('recorrencia_id', $recorrencia->id)->delete();
                
                $conteudo = 'Atualizada nova data na recorrência: '.$recorrencia->DemandaCampanhaRecorrencia->titulo;
                $this->criarNotificacoesRecorrencia($demanda, $user, $conteudo);

                return response()->json([
                    'success' => true,
                    'message' => 'Data alterada com sucesso.'
                ], 200);
            }
        }
    }

    public function startRecorrenciaAjuste(Request $request, $id){
        $user = Auth::User();
        $recorrenciaAjuste = DemandaRecorrenciaAjuste::with('DemandaRecorrencia.DemandaCampanhaRecorrencia.demandas')->findOrFail($id);

        if($recorrenciaAjuste){
            $recorrenciaAjuste->em_pauta = 1;
            $recorrenciaAjuste->status = 'Em pauta';
            $response = $recorrenciaAjuste->save();

            if($response){
                $demanda = $recorrenciaAjuste->DemandaRecorrencia->DemandaCampanhaRecorrencia->demandas;
                // $recorrencia = DemandaRecorrencia::where('id', $recorrenciaAjuste->recorrencia_id)->first();
                // if($recorrencia){
                //     $recorrencia->status = 'Em pauta';
                //     $recorrencia->em_pauta = 1;
                //     $recorrencia->save();
                // }
                $conteudo = '('.$recorrenciaAjuste->DemandaRecorrencia->DemandaCampanhaRecorrencia->titulo. ')'. ' Iniciada a alteração '.($request->keyAlteracao + 1);
                $this->criarNotificacoesRecorrencia($demanda, $user, $conteudo);

                return response()->json([
                    'success' => true,
                    'message' => 'Alteração iniciada com sucesso.'
                ], 200);
            }
        }
    }

    public function deliverRecorrenciaAjuste(Request $request, $id){
        $user = Auth::User();
        $dataAtual = date('Y-m-d');
        $recorrenciaAjuste = DemandaRecorrenciaAjuste::where('id', $request->id)->first();

        $recorrencia = DemandaRecorrencia::where('id', $recorrenciaAjuste->recorrencia_id)->first();
        $campanha = DemandaCampanhaRecorrencia::where('id', $recorrencia->campanha_id)->first();
        $demanda = Demanda::where('id', $campanha->demanda_id)->with('demandaColaboradores')->first();
        $demandaColaboradoresIds[] = $demanda->criador_id;
        $demandaColaboradoresIds = array_merge($demandaColaboradoresIds, $demanda->demandaColaboradores->pluck('id')->toArray());
        $demandaColaboradoresIds = array_unique($demandaColaboradoresIds);

        if($recorrenciaAjuste){
            
            $recorrenciaAjuste->entregue = 1;
            $recorrenciaAjuste->em_pauta = 0;
            
            if($dataAtual > $recorrenciaAjuste->data){
                $recorrenciaAjuste->atrasada = 1;
            }else{
                $recorrenciaAjuste->atrasada = 0;
            }

            $recorrenciaAjuste->status = 'Entregue';
            $recorrenciaAjuste->data_entrega = date('Y-m-d H:i:s');

            $response = $recorrenciaAjuste->save();

            if ($response) {

                $notificacoesReq = [];
                foreach ($demandaColaboradoresIds as $usuario) {
                    if ($user->id != $usuario) {
                        $notificacao = [
                            'usuario_id' => $usuario,
                            'demanda_id' => $demanda->id,
                            'criado' => date('Y-m-d H:i:s'),
                            'conteudo' => '('.$campanha->titulo. ')' . ' Entregue a alteração ('.($request->keyAlteracao + 1). ')',
                            'visualizada' => '0',
                            'tipo' => 'criada',

                        ];
                        $notificacoesReq[] = $notificacao;
                    }
                }

                Notificacao::insert($notificacoesReq);

                foreach($demandaColaboradoresIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                $countAjustes = $recorrencia->whereHas('ajustes', function ($query) {
                    $query->where('entregue', 0);
                })
                ->count();

                if($countAjustes == 0 && $recorrencia->status == 'Em alteração'){
                    $recorrencia->em_alteracao = 0;
                    $recorrencia->status = 'Entregue';
                    $recorrencia->entregue = 1;
                    $recorrencia->data_entrega = date('Y-m-d H:i:s');
                    $recorrencia->finalizado = 0;
                    $recorrencia->save();
                }

                $actionLink = route('Job', ['id' => $demanda->id]);
                $bodyEmail = 'Entregue a alteração com a data de '. Carbon::parse($recorrenciaAjuste->data)->locale('pt_BR')->isoFormat('DD/MM/YYYY') .' da campanha "' . $campanha->titulo . '". Acesse pelo link logo abaixo.';
                $titleEmail = 'Alteração de recorrência entregue';
                
                $usersToNotifyByEmail = User::select('id', 'email', 'nome', 'notificar_email')
                ->where('notificar_email', 1)
                ->where(function ($query) use ($demandaColaboradoresIds) {
                    $query->whereIn('id', $demandaColaboradoresIds);
                })
                ->get();
                
                foreach($usersToNotifyByEmail as $item){
                    if($item->notificar_email == 1){
                        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $item) {
                            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                            ->to($item->email)
                            ->subject('Alteração de recorrência entregue');
                        });
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Alterado com sucesso.',
                ], 200);

            }

        }
        return response()->json([
            'type' => 'error',
            'message' => 'Não foi possível entregar este job!'
        ], 400);
    }

    public function newDateAjuste(Request $request, $id){
        $user = Auth::user();
        $recorrenciaAjuste = DemandaRecorrenciaAjuste::with('DemandaRecorrencia.DemandaCampanhaRecorrencia.demandas')->findOrFail($id);

        if ($recorrenciaAjuste) {
            $recorrenciaAjuste->data = $request->data_recorrencia;
            $response = $recorrenciaAjuste->save();

            if ($response) {
                $demanda = $recorrenciaAjuste->DemandaRecorrencia->DemandaCampanhaRecorrencia->demandas;
                
                $conteudo = 'Atualizada nova data na recorrência: '.$recorrenciaAjuste->DemandaRecorrencia->DemandaCampanhaRecorrencia->titulo;
                $this->criarNotificacoesRecorrencia($demanda, $user, $conteudo);
                
                $recorrencia = DemandaRecorrencia::where('id', $recorrenciaAjuste->recorrencia_id)->first();
                
                if (Carbon::parse($recorrenciaAjuste->data)->gt(Carbon::parse($recorrencia->data))) {
                    if ($recorrencia->finalizado == 0 && $recorrencia->entregue == 0) {
                        $recorrencia->data = $recorrenciaAjuste->data;
                        $recorrencia->save();

                        DemandaAtrasadaRecorrencia::where('recorrencia_id', $recorrencia->id)->delete();

                    }
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Data alterada com sucesso.'
                ], 200);
            }
        }
    }

}