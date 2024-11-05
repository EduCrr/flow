<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Questionamento;
use App\Models\Demanda;
use App\Models\Agencia;
use App\Models\DemandaTempo;
use App\Models\Alteracao;
use App\Models\Notificacao;
use App\Models\ComentarioPauta;
use App\Models\DemandaReaberta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
Use Alert;
use App\Models\DemandaAtrasada;
use App\Models\DemandaOrdemJob;
use App\Models\LinhaTempo;
use App\Models\MarcaColaborador;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Utils\EnviarMail;
use App\Utils\OrdemJob;
use Illuminate\Http\Response;
use PhpParser\Node\Stmt\Return_;

class ComentariosController extends Controller
{

    public function delete($id){
        $user = Auth::User();
        $comentario = Questionamento::find($id);
        $demanda = Demanda::select('id', 'agencia_id', 'em_pauta', 'em_alteracao')->where('id', $comentario->demanda_id)->with('demandasUsuario')->with('demandaColaboradores')->first();
        if($comentario){
            if (stripos($comentario->tipo, 'Questionamento') !== false || stripos($comentario->tipo, 'Observação') !== false || stripos($comentario->tipo, 'Finalizado') !== false || stripos($comentario->tipo, 'Entregue') !== false || stripos($comentario->tipo, 'Mudança') !== false) {
                $comentario->excluido =  date('Y-m-d H:i:s');
                $comentario->save();

                if(stripos($comentario->tipo, 'Questionamento') !== false){
                    $primeira_letra = substr($comentario->tipo, 0, 1);
                    preg_match('/\d+/', $comentario->tipo, $matches);
                    $numero = $matches[0];
                    $newTimeLine = new LinhaTempo();
                    $newTimeLine->demanda_id = $comentario->demanda_id;
                    $newTimeLine->usuario_id = $user->id;
                    $newTimeLine->criado = date('Y-m-d H:i:s');
                    $newTimeLine->code = 'removido';
                    $newTimeLine->status =  'Excluído '.$primeira_letra.$numero;
                    $newTimeLine->save();
                }
                return response()->json([
                    'success' => true,
                    'message' => 'Comentário excluido com sucesso.'
                ], 200);

            } else {
                $idComentarioPauta = ComentarioPauta::where('comentario_id', $comentario->id)->first();
                $demandaTempo = DemandaTempo::find($idComentarioPauta->demandapauta_id);
                if($demandaTempo->finalizado == null){
                    $demandaTempo->delete();

                    $demandasTemposAg = DemandaTempo::where('demanda_id', $demanda->id)->where('agencia_id', $demanda->agencia_id)->where('finalizado', null)->count();
                    if($demandasTemposAg == 0){
                        $demanda->em_alteracao = 0;
                        $demanda->em_pauta = 0;
                        $demanda->save();
                    }

                    $newTimeLine = new LinhaTempo();
                    $newTimeLine->demanda_id = $comentario->demanda_id;
                    $newTimeLine->usuario_id = $user->id;
                    $newTimeLine->criado = date('Y-m-d H:i:s');
                    $newTimeLine->code = 'removido';
                    $newTimeLine->status =  'Excluída '.strtolower($comentario->tipo);
                    $newTimeLine->save();

                    foreach($demanda['demandasUsuario'] as $usuario) {
                        $agenciaNotificacao = new Notificacao();
                        $agenciaNotificacao->demanda_id = $demanda->id;
                        $agenciaNotificacao->usuario_id = $usuario->id;
                        $agenciaNotificacao->conteudo = 'Excluída '.strtolower($comentario->tipo) . ' do job '. $comentario->demanda_id;
                        $agenciaNotificacao->tipo = 'criada';
                        $agenciaNotificacao->criado = date('Y-m-d H:i:s');
                        $agenciaNotificacao->visualizada = '0';
                        $agenciaNotificacao->save();
                    }

                    foreach($demanda['demandaColaboradores'] as $usuario) {
                        $agenciaNotificacao = new Notificacao();
                        $agenciaNotificacao->demanda_id = $demanda->id;
                        $agenciaNotificacao->usuario_id = $usuario->id;
                        $agenciaNotificacao->conteudo = 'Excluída '.strtolower($comentario->tipo) . ' do job '. $comentario->demanda_id;
                        $agenciaNotificacao->tipo = 'criada';
                        $agenciaNotificacao->criado = date('Y-m-d H:i:s');
                        $agenciaNotificacao->visualizada = '0';
                        $agenciaNotificacao->save();
                    }

                    $comentario->excluido =  date('Y-m-d H:i:s');
                    $comentario->save();

                    return response()->json([
                        'success' => true,
                        'message' => 'Comentário excluido com sucesso.'
                    ], 200);

                }else{
                    return response()->json([
                        'success' => false,
                        'message' => 'Não foi possível excluir esse comentário.',
                    ], 400);
                }

            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Não foi possível excluir esse comentário.',
        ], 400);

    }

    public function getComentary($id){

        if($id){
            $comentary = Questionamento::find($id);
            return $comentary;
        }
        return false;
    }

    public function getComentaryAction(Request $request){
        $id = $request->id;
        $validator = Validator::make($request->all(),[
            'newContent' => 'required',
            'marcar_usuario' => 'required',
            ],[
                'newContent.required' => 'O campo não pode ser vazio!',
                'marcar_usuario.required' => 'Marque um usuário.',
            ]
        );

        if($validator->fails()) {
             return response()->json([
                 'success' => false,
                 'message' => $validator->errors()->first(),
             ], 400);
        }

        if($id){
            $comentary = Questionamento::find($id);
            $isAlteracaoComentario = $comentary->tipo;
            $getUser = User::select('id', 'tipo')->where('id', $request->marcar_usuario)->where('tipo', '<>', 'agencia')->count();

            if (strpos($isAlteracaoComentario, 'Alteração') !== false) {
                if($getUser > 0){
                    return response()->json([
                        'success' => false,
                        'title' => "Ops",
                        'message' => 'Esse usuário não pode ser marcado para essa alteração.'
                    ], 400);
                }
            }

            $comentary->descricao = $request->newContent;
            $old = $comentary->marcado_usuario_id;

            if($request->marcar_usuario != ''){
                $comentary->marcado_usuario_id  = $request->marcar_usuario;
                if($old != $request->marcar_usuario){
                    Notificacao::where('demanda_id', $comentary->demanda_id)->where('usuario_id', $old)->where('tipo_referencia', $comentary->tipo)->delete();
                    $colaboradorNotificacao = new Notificacao();
                    $colaboradorNotificacao->usuario_id = $request->marcar_usuario;
                    $colaboradorNotificacao->demanda_id = $comentary->demanda_id;
                    $colaboradorNotificacao->conteudo = 'Você foi marcado em um comentário.';
                    $colaboradorNotificacao->tipo = 'observacao';
                    $colaboradorNotificacao->tipo_referencia = $comentary->tipo;
                    $colaboradorNotificacao->criado = date('Y-m-d H:i:s');
                    $colaboradorNotificacao->visualizada = '0';
                    $colaboradorNotificacao->save();

                    if($getUser > 0){
                        $comentary->visualizada_col = 0;
                        $comentary->visualizada_ag = 1;
                    }else{
                        $comentary->visualizada_ag = 0;
                        $comentary->visualizada_col = 1;
                    }
                    $comentary->marcado_usuario_id = $request->marcar_usuario;
                    $comentary->save();
                }
            }else{
                $comentary->marcado_usuario_id = null;
                Notificacao::where('demanda_id', $comentary->demanda_id)->where('usuario_id', $old)->where('tipo_referencia', $comentary->tipo)->delete();
            }

            $comentary->save();

            return response()->json([
                'success' => true,
                'message' => 'Comentário atualizado!'
            ], 200);
        }else{
            if($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível adicionar o seu comentário!',
                ], 400);
           }
        }
    }

    public function comentaryAction(Request $request, $id){
        $user = Auth::User();
        $conteudo = $request->input('conteudo');
        $tipo = $request->input('tipo');
        $demanda = Demanda::where('id', $id)->where('excluido', null)->with('criador')->with('demandasUsuario')->first();
        $demandaColaboradoresIds = [];

        $isAdminBrand = User::where('id', $request->marcar_usuario)->where('tipo', 'admin')->value('id');
        if ($isAdminBrand) {
            $demandaColaboradoresIds[] = $isAdminBrand;
        }

        $demandaColaboradoresIds = array_merge($demandaColaboradoresIds, $demanda->demandaColaboradores->pluck('id')->toArray());

        if (!in_array($demanda->criador_id, $demandaColaboradoresIds)) {
            $demandaColaboradoresIds[] = $demanda->criador_id;
        }

        $demandaColaboradoresIds = array_unique($demandaColaboradoresIds);

        $demandaUsuariosIds = $demanda->demandasUsuario->pluck('id')->toArray();
        $actionLink = route('Job', ['id' => $demanda->id]);
        $titleEmail = '';
        $bodyEmailDefault = 'O job '. $demanda->id . ': '.$demanda->titulo .', recebeu uma nova mensagem.'. '<br/>'.  'Acesse pelo link logo abaixo.';

        $validator = Validator::make($request->all(),[
           'conteudo' => 'required',
           'tipo' => 'required',
           'marcar_usuario' => 'required'
            ],[
                'conteudo.required' => 'Não foi possível adicionar o seu comentário.',
                'tipo.required' => 'Preencha o campo tipo.',
                'marcar_usuario.required' => 'Marque um usuário.',
            ]
        );

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);

        }

        if(!$validator->fails()){
            if($demanda){
                $typeUser = User::select('id', 'tipo')->where('id', $request->marcar_usuario)->first();
                $demandaComentario = new Questionamento();
                $demandaComentario->usuario_id = $user->id;
                $demandaComentario->descricao = $conteudo;
                if($request->marcar_usuario != ''){
                    $demandaComentario->marcado_usuario_id  = $request->marcar_usuario;
                }
                $demandaComentario->demanda_id = $id;
                $demandaComentario->criado = Carbon::now();

                $newTimeLine = new LinhaTempo();
                $newTimeLine->demanda_id = $id;
                $newTimeLine->usuario_id = $user->id;
                $newTimeLine->criado = date('Y-m-d H:i:s');
                $hasObsCount = Questionamento::where('demanda_id', $id)->where('tipo', 'like', '%Observação%')->count();
                // $hasEntregueCount = Questionamento::where('demanda_id', $id)->where('tipo', 'like', '%Entregue%')->count();
                // $hasFinalizadoCount = Questionamento::where('demanda_id', $id)->where('tipo', 'like', '%Finalizado%')->count();
                $hasAlterationAltCount = Questionamento::where('demanda_id', $id)->where('tipo', 'like', '%Alteração%')->count();
                $hasQuestsCount = Questionamento::where('demanda_id', $id)->where('tipo', 'like', '%Questionamento%')->count();


                if($tipo == 'questionamento'){
                    $bodyEmail = 'O job '.$id . ' recebeu um novo questionamento.'. '<br/>'.  'Acesse pelo link logo abaixo.';
                    //agencia
                    // $hasQuestsCount = LinhaTempo::where('demanda_id', $id)->where('code', 'questionamento')->count();
                    //3
                    if($hasQuestsCount == 0){
                        $newTimeLine->status = 'Questionamento 1';
                        $newTimeLine->code = 'questionamento';
                        $newTimeLine->save();
                        $demandaComentario->tipo = 'Questionamento 1';
                    }else{
                        $newTimeLine->status = 'Questionamento '.($hasQuestsCount + 1);
                        $newTimeLine->code = 'questionamento';
                        $newTimeLine->save();
                        $demandaComentario->tipo = 'Questionamento '.($hasQuestsCount + 1);
                    }

                    $demandaComentario->cor = '#d4624d';
                    $demandaComentario->visualizada_ag = $typeUser->tipo == 'agencia' ? 0 : 1;
                    $demandaComentario->visualizada_col = $typeUser->tipo == 'agencia' ? 1 : 0;

                    //notificacao

                    $notificacoesQuest = [];
                    foreach ($demandaColaboradoresIds as $usuario) {
                        if ($user->id != $usuario) {
                            $notificacao = [
                                'usuario_id' => $usuario,
                                'demanda_id' => $demanda->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                                'tipo' => 'questionamento',
                                'tipo_referencia' => $demandaComentario->tipo

                            ];

                            if ($usuario == $request->marcar_usuario) {
                                $notificacao['conteudo'] = 'Você foi marcado em um novo questionamento.';
                            } else {
                                $notificacao['conteudo'] = 'Novo questionamento.';
                            }

                            $notificacoesQuest[] = $notificacao;
                        }
                    }

                    Notificacao::insert($notificacoesQuest);

                    $notificacoesAgency = [];

                    foreach ($demanda['demandasUsuario'] as $usuario) {
                        if ($user->id != $usuario->id) {
                            $notificacaoAgency = [
                                'usuario_id' => $usuario->id,
                                'demanda_id' => $demanda->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                                'tipo' => 'questionamento',
                                'tipo_referencia' => $demandaComentario->tipo,
                            ];

                            if ($usuario->id == $request->marcar_usuario) {
                                $notificacaoAgency['conteudo'] =  'Você foi marcado em um novo questionamento.';
                            } else {
                                $notificacaoAgency['conteudo'] = 'Novo questionamento.';
                            }

                            $notificacoesAgency[] = $notificacaoAgency;

                        }

                    }

                    Notificacao::insert($notificacoesAgency);

                    $usersToNotifyByEmail = User::select('id', 'email', 'nome')->where('notificar_email', 1)->where('id', $request->marcar_usuario)->first();
                    $titleEmail = 'Novo questionamento no Job '.$demanda->id. ': '.$demanda->titulo;
                    if($usersToNotifyByEmail){
                        $sendEmail = EnviarMail::enviarEmail($usersToNotifyByEmail->email, $usersToNotifyByEmail->nome, $actionLink, $bodyEmail, $titleEmail, $conteudo, $user->nome);
                    }


                }else if($tipo == 'entregue'){

                    //agencia
                    // if($hasEntregueCount == 0){
                        $demandaComentario->tipo = 'Entregue';
                        $demandaComentario->cor = '#44a2d2';
                    // }else if($hasEntregueCount > 0){
                        // $demandaComentario->tipo = 'Entregue '.($hasEntregueCount + 1);
                        // $demandaComentario->cor = '#44a2d2';
                    // }

                    $notificacoesEntregue = [];

                    foreach ($demandaColaboradoresIds as $usuario) {
                        if ($user->id != $usuario) {
                            $notificacaoEntregue = [
                                'usuario_id' => $usuario,
                                'demanda_id' => $demanda->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                                'tipo' => 'entregue',
                                'tipo_referencia' => $demandaComentario->tipo

                            ];

                            if ($usuario == $request->marcar_usuario) {
                                $notificacaoEntregue['conteudo'] = 'Você foi marcado em um novo comentário: Entregue';
                            } else {
                                $notificacaoEntregue['conteudo'] = 'Novo comentário: Entregue';
                            }

                            $notificacoesEntregue[] = $notificacaoEntregue;
                        }
                    }

                    Notificacao::insert($notificacoesEntregue);

                    $notificacoesAgs = [];

                    foreach ($demanda['demandasUsuario'] as $usuario) {
                        if ($user->id != $usuario->id) {
                            $notificacaoEntregueAg = [
                                'usuario_id' => $usuario->id,
                                'demanda_id' => $demanda->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                                'tipo' => 'questionamento',
                                'tipo_referencia' => $demandaComentario->tipo
                            ];

                            if ($usuario->id == $request->marcar_usuario) {
                                $notificacaoEntregueAg['conteudo'] =  'Você foi marcado em um novo questionamento.';
                            } else {
                                $notificacaoEntregueAg['conteudo'] = 'Novo questionamento.';
                            }

                            $notificacoesAgs[] = $notificacaoEntregueAg;

                        }

                    }

                    Notificacao::insert($notificacoesAgs);

                    $demandaComentario->visualizada_ag = $typeUser->tipo == 'agencia' ? 0 : 1;
                    $demandaComentario->visualizada_col = $typeUser->tipo == 'agencia' ? 1 : 0;

                    $usersToNotifyByEmail = User::select('id', 'email', 'nome')->where('notificar_email', 1)->where('id', $request->marcar_usuario)->first();
                    $titleEmail = 'Novo comentário no Job '.$demanda->id. ': '.$demanda->titulo;

                    if($usersToNotifyByEmail){
                        $sendEmail = EnviarMail::enviarEmail($usersToNotifyByEmail->email, $usersToNotifyByEmail->nome, $actionLink, $bodyEmailDefault, $titleEmail, $conteudo, $user->nome);
                    }


                }else if($tipo == 'observacao'){

                    if($hasObsCount == 0){
                        $demandaComentario->tipo = 'Observação 1';
                        $demandaComentario->cor = '#f9bc0b';
                    }else if($hasObsCount > 0){
                        $demandaComentario->tipo = 'Observação '.($hasObsCount + 1);
                        $demandaComentario->cor = '#f9bc0b';
                    }

                    $demandaComentario->visualizada_ag = $typeUser->tipo == 'agencia' ? 0 : 1;
                    $demandaComentario->visualizada_col = $typeUser->tipo == 'agencia' ? 1 : 0;

                    $notificacoesObs = [];

                    foreach ($demandaColaboradoresIds as $usuario) {
                        if ($user->id != $usuario) {
                            $notificacaoObs = [
                                'usuario_id' => $usuario,
                                'demanda_id' => $demanda->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                                'tipo' => 'observacao',
                                'tipo_referencia' => $demandaComentario->tipo

                            ];

                            if ($usuario == $request->marcar_usuario) {
                                $notificacaoObs['conteudo'] = 'Você foi marcado em uma nova observação.';
                            } else {
                                $notificacaoObs['conteudo'] = 'Novo comentário: Observação';
                            }

                            $notificacoesObs[] = $notificacaoObs;
                        }
                    }

                    Notificacao::insert($notificacoesObs);

                    $notificacoesAgs = [];

                    foreach ($demanda['demandasUsuario'] as $usuario) {
                        if ($user->id != $usuario->id) {
                            $notificacaoObservacaoAg = [
                                'usuario_id' => $usuario->id,
                                'demanda_id' => $demanda->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                                'tipo' => 'observacao',
                                'tipo_referencia' => $demandaComentario->tipo
                            ];

                            if ($usuario->id == $request->marcar_usuario) {
                                $notificacaoObservacaoAg['conteudo'] =  'Você foi marcado em uma nova observação.';
                            } else {
                                $notificacaoObservacaoAg['conteudo'] = 'Novo comentário: Observação';
                            }

                            $notificacoesAgs[] = $notificacaoObservacaoAg;
                        }

                    }

                    Notificacao::insert($notificacoesAgs);

                    $colaboradorToNotifyByEmail = User::select('id', 'email', 'nome')->where('notificar_email', 1)->where('id', $request->marcar_usuario)->first();
                    $titleEmail = 'Novo comentário no Job '.$demanda->id. ': '.$demanda->titulo;
                    if($colaboradorToNotifyByEmail){
                        $sendEmail = EnviarMail::enviarEmail($colaboradorToNotifyByEmail->email, $colaboradorToNotifyByEmail->nome, $actionLink, $bodyEmailDefault, $titleEmail, $conteudo, $user->nome);
                    }

                }
                else if($tipo == 'observacaoadm' && ($user->id == $demanda->criador_id || in_array($user->id, $demandaColaboradoresIds) || $user->tipo == 'admin')) {
                    //colaborador

                    if($hasObsCount == 0){
                        $demandaComentario->tipo = 'Observação 1';
                        $demandaComentario->cor = '#f9bc0b';
                    }else if($hasObsCount > 0){
                        $demandaComentario->tipo = 'Observação '.($hasObsCount + 1);
                        $demandaComentario->cor = '#f9bc0b';
                    }

                    // $demandaComentario->visualizada_ag = 0;
                    // if($user->tipo == 'admin' && !in_array($user->id, $demandaColaboradoresIds)){
                    //     $demandaComentario->visualizada_col = 0;
                    // }else{
                    //     $demandaComentario->visualizada_col = 1;
                    // }

                    // $notificacoesAd = [];

                    // $commentColOrAd = User::where('id', $request->marcar_usuario)->where('tipo', '<>', 'agencia')->count();
                    // if($commentColOrAd > 0){
                    //     $demandaComentario->visualizada_ag = 1;
                    //     $demandaComentario->visualizada_col = 0;

                    //     $notificacaoAd = [
                    //         'usuario_id' => $request->marcar_usuario,
                    //         'demanda_id' => $request->id,
                    //         'criado' => date('Y-m-d H:i:s'),
                    //         'visualizada' => '0',
                    //         'tipo' => 'observacao',
                    //         'tipo_referencia' => $demandaComentario->tipo,
                    //         'conteudo' => 'Você foi marcado em uma nova observação.'

                    //     ];

                    //     $notificacoesAd[] = $notificacaoAd;

                    //     Notificacao::insert($notificacoesAd);


                    //     $colaboradorToNotifyByEmail = User::where('notificar_email', 1)->where('id', $request->marcar_usuario)->first();
                    //     if($colaboradorToNotifyByEmail){
                    //         $titleEmail = 'Novo comentário no Job '.$demanda->id. ': '.$demanda->titulo;
                    //         EnviarMail::enviarEmail($colaboradorToNotifyByEmail->email, $colaboradorToNotifyByEmail->nome, $actionLink, $bodyEmailDefault, $titleEmail, $notificacaoAd['conteudo'], $colaboradorToNotifyByEmail->nome);
                    //     }
                    // }
                    // else{
                    //     $demandaComentario->visualizada_ag = 0;
                    //     $demandaComentario->visualizada_col = 1;
                    // }

                    if(in_array($request->marcar_usuario, $demandaColaboradoresIds)){
                        $demandaComentario->visualizada_ag = 1;
                        $demandaComentario->visualizada_col = 0;

                    }else{
                        $demandaComentario->visualizada_ag = 0;
                        $demandaComentario->visualizada_col = 1;
                    }

                    $notificacoesAg = [];

                    foreach ($demanda['demandasUsuario'] as $usuario) {
                        if($user->id != $usuario->id){
                            $notificacaoAgency = [
                                'usuario_id' => $usuario->id,
                                'demanda_id' => $request->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                                'tipo' => 'observacao',
                                'tipo_referencia' => $demandaComentario->tipo,
                            ];

                            if ($usuario->id == $request->marcar_usuario) {
                                $notificacaoAgency['conteudo'] =  'Você foi marcado em uma nova observação.';
                            } else {
                                $notificacaoAgency['conteudo'] = 'Novo comentário: Observação';
                            }

                            $notificacoesAg[] = $notificacaoAgency;
                        }

                    }

                    Notificacao::insert($notificacoesAg);

                    $notificacoesObs = [];

                    foreach ($demandaColaboradoresIds as $usuario) {
                        if ($user->id != $usuario) {
                            $notificacaoObs = [
                                'usuario_id' => $usuario,
                                'demanda_id' => $demanda->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                                'tipo' => 'observacao',
                                'tipo_referencia' => $demandaComentario->tipo,
                            ];

                            if ($usuario == $request->marcar_usuario) {
                                $notificacaoObs['conteudo'] =  'Você foi marcado em uma nova observação.';
                            } else {
                                $notificacaoObs['conteudo'] = 'Novo comentário: Observação';
                            }

                            $notificacoesObs[] = $notificacaoObs;
                        }
                    }

                    Notificacao::insert($notificacoesObs);

                    $colaboradorToNotifyByEmail = User::where('notificar_email', 1)->where('id', $request->marcar_usuario)->first();
                    $bodyEmailAlteration = 'O job '. $demanda->id . ': '.$demanda->titulo .', recebeu uma nova observação.'. '<br/>'.  'Acesse pelo link logo abaixo.';

                    if($colaboradorToNotifyByEmail){
                        $titleEmail = 'Novo comentário no Job '.$demanda->id. ': '.$demanda->titulo;
                        EnviarMail::enviarEmail($colaboradorToNotifyByEmail->email, $colaboradorToNotifyByEmail->nome, $actionLink, $bodyEmailDefault, $titleEmail, $bodyEmailAlteration, $colaboradorToNotifyByEmail->nome);
                    }

                }else if($tipo == 'alteracao' && ($user->id == $demanda->criador_id || in_array($user->id, $demandaColaboradoresIds))) {

                    if(!in_array($request->marcar_usuario, $demandaUsuariosIds)){
                        return response()->json([
                            'success' => false,
                            'title' => "Ops",
                            'message' => 'Esse usuário não pode ser marcado para fazer uma alteração.'
                        ], 400);
                    }

                    $conteudoNotificacao = '';
                    //colaborador
                    // $hasalterationCount = LinhaTempo::where('demanda_id', $id)->where('code', 'alteracao')->count();

                   //criar tempo
                    $newTimeJob = new DemandaTempo();
                    $newTimeJob->demanda_id = $request->id;
                    $newTimeJob->agencia_id = $demanda->agencia_id;
                    $newTimeJob->criado = date('Y-m-d H:i:s');
                    $newTimeJob->aceitar_colaborador = 1;
                    $newTimeJob->code_tempo = 'alteracao';

                    $countDemandasReabertas = DemandaReaberta::where('demanda_id', $demanda->id)->count();
                    $demandasReaberta = DemandaReaberta::where('demanda_id', $demanda->id)->orderByDesc('id')->first();

                    //aumentar data demanda
                    if($request->sugeridoComment){
                        $newTimeJob->sugerido = $request->sugeridoComment;
                        if($countDemandasReabertas == 0){
                            if (strtotime($request->sugeridoComment) > strtotime($demanda->final)) {
                                // $request->sugeridoComment é maior que $demanda->final
                                $demanda->final = $request->sugeridoComment;
                                $demanda->save();
                                $removerDemandasAtrasadas = DemandaAtrasada::where('demanda_id', $demanda->id)->delete();
                            }
                        }else if($countDemandasReabertas > 0){
                            if (strtotime($request->sugeridoComment) > strtotime($demandasReaberta->sugerido)) {
                                // $request->sugeridoComment é maior que $demanda->final
                                $demandasReaberta->sugerido = $request->sugeridoComment;
                                $demandasReaberta->save();
                                $removerDemandasAtrasadas = DemandaAtrasada::where('demanda_id', $demanda->id)->delete();
                            }
                        }

                    }

                    if($hasAlterationAltCount >= 0 && $countDemandasReabertas == 0){

                        $newTimeJob->status = 'Alteração '.($hasAlterationAltCount + 1);
                        $titleEmail = 'Alteração '.($hasAlterationAltCount + 1);

                        $newTimeLine->status = 'Alteração '.($hasAlterationAltCount + 1);
                        $conteudoNotificacao = 'Alteração '.($hasAlterationAltCount + 1).'.';

                        $newTimeLine->code = 'alteracao';
                        $newTimeLine->save();

                        $demanda->em_alteracao = 1;
                        $demanda->entregue = 0;
                        $demanda->entregue_recebido = 0;

                        if($demanda->em_pauta == 0){
                            $demanda->status = 'Pendente';

                        }else if($demanda->em_pauta == 1){
                            $demanda->status = 'Em pauta';
                        }

                        $demanda->save();

                    }

                    //criada alteracao com a demanda reaberta
                    if($hasAlterationAltCount >= 0 && $countDemandasReabertas > 0){
                        $newTimeJob->status = '(Reaberto) alteração '.($hasAlterationAltCount + 1);
                        $titleEmail = '(Reaberto) alteração '.($hasAlterationAltCount + 1);

                        $newTimeLine->status = 'Alteração '.($hasAlterationAltCount + 1);
                        $newTimeLine->code = 'alteracao';
                        $newTimeLine->save();
                        $conteudoNotificacao = 'Alteração '.($hasAlterationAltCount + 1).'.';

                        $demanda->em_alteracao = 1;
                        $demanda->entregue = 0;
                        $demanda->status = 'Pendente';
                        $demanda->entregue_recebido = 0;
                        $demanda->save();

                    }

                    //comentario alteracao

                    if($hasAlterationAltCount == 0){
                        $demandaComentario->tipo = 'Alteração 1';
                        $demandaComentario->cor = '#d56551';
                    }else if($hasAlterationAltCount > 0){
                        $demandaComentario->tipo = 'Alteração '.($hasAlterationAltCount + 1);
                        $demandaComentario->cor = '#d56551';
                    }

                    $demandaComentario->visualizada_ag = 0;
                    $demandaComentario->visualizada_col = 1;

                    $demandaComentario->save();

                    //salvar tempo
                    $newTimeJob->save();


                    //notificacao usuario

                    $notificacoesAgency = [];

                    foreach ($demanda['demandasUsuario'] as $usuario) {
                        $notificacaoAgency = [
                            'usuario_id' => $usuario->id,
                            'demanda_id' => $demanda->id,
                            'conteudo' => $usuario->id == $request->marcar_usuario
                                ? 'Você foi marcado no comentário: ' . $conteudoNotificacao
                                : 'Novo comentário: '.$conteudoNotificacao,
                            'tipo' => 'criada',
                            'criado' => date('Y-m-d H:i:s'),
                            'visualizada' => '0',
                            'tipo_referencia' => $demandaComentario->tipo
                        ];

                        $notificacoesAgency[] = $notificacaoAgency;
                    }

                    Notificacao::insert($notificacoesAgency);


                    //salvar relacao comentario/pauta

                    $comentarioPauta = new ComentarioPauta();
                    $comentarioPauta->comentario_id = $demandaComentario->id;
                    $comentarioPauta->demandapauta_id = $newTimeJob->id;
                    $comentarioPauta->save();

                    //countAlteracao
                    $newAlteration = new Alteracao();
                    $newAlteration->demanda_id = $demanda->id;
                    $newAlteration->save();

                    $demanda->save();

                    $usersToNotifyByEmail = User::select('id', 'email', 'nome')->where('notificar_email', 1)->where('id', $request->marcar_usuario)->first();
                    $titleEmail = 'Job '. $demanda->id . ': '.$demanda->titulo.', foi ' . strtolower($conteudoNotificacao);
                    $bodyEmailAlteration = 'O job '. $demanda->id . ': '.$demanda->titulo .', recebeu uma nova alteração.'. '<br/>'.  'Acesse pelo link logo abaixo.';

                    if($usersToNotifyByEmail){
                        $sendEmail = EnviarMail::enviarEmail($usersToNotifyByEmail->email, $usersToNotifyByEmail->nome, $actionLink, $bodyEmailAlteration, $titleEmail, $conteudo, $user->nome);
                    }

                    $notificacoesAltCol = [];

                    foreach ($demandaColaboradoresIds as $usuario) {
                        if ($user->id != $usuario) {
                            $notificacaoObs = [
                                'usuario_id' => $usuario,
                                'demanda_id' => $demanda->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                                'tipo' => 'criada',
                                'tipo_referencia' => $demandaComentario->tipo,
                                'conteudo' => 'Novo comentário: '.$conteudoNotificacao,

                            ];

                            $notificacoesAltCol[] = $notificacaoObs;
                        }
                    }

                    Notificacao::insert($notificacoesAltCol);


                }else if($tipo == 'finalizado' && ($user->id == $demanda->criador_id || in_array($user->id, $demandaColaboradoresIds))) {
                    //colaborador

                    // if($hasFinalizadoCount == 0){
                        $demandaComentario->tipo = 'Finalizado';
                        $demandaComentario->cor = '#3dbb3d';
                    // }else if($hasFinalizadoCount > 0){
                        // $demandaComentario->tipo = 'Finalizado '.($hasFinalizadoCount + 1);
                        // $demandaComentario->cor = '#3dbb3d';
                    // }

                    $newTimeLine->status = 'Finalizado';
                    $newTimeLine->save();

                    $demandaComentario->visualizada_ag = 0;
                    $demandaComentario->visualizada_col = 1;

                    $notificacoesAgency = [];

                    foreach ($demanda['demandasUsuario'] as $usuario) {
                        $conteudoNotificacao = $usuario->id == $request->marcar_usuario
                            ? 'Você foi marcado em um novo comentário: Finalizado'
                            : 'Novo comentário: Finalizado';

                        $notificacoesAgency[] = [
                            'usuario_id' => $usuario->id,
                            'demanda_id' => $demanda->id,
                            'conteudo' => $conteudoNotificacao,
                            'tipo' => 'finalizado',
                            'criado' => date('Y-m-d H:i:s'),
                            'visualizada' => '0',
                            'tipo_referencia' => $demandaComentario->tipo

                        ];
                    }

                    Notificacao::insert($notificacoesAgency);

                    $usersToNotifyByEmail = User::select('id', 'email', 'nome')->where('notificar_email', 1)->where('id', $request->marcar_usuario)->first();
                    $titleEmail = 'Novo comentário no Job '.$demanda->id. ': '.$demanda->titulo;

                    if($usersToNotifyByEmail){
                        $sendEmail = EnviarMail::enviarEmail($usersToNotifyByEmail->email, $usersToNotifyByEmail->nome, $actionLink, $bodyEmailDefault, $titleEmail, $conteudo, $user->nome);
                    }

                    $notificacoesFinisher = [];

                    foreach ($demandaColaboradoresIds as $usuario) {
                        if ($user->id != $usuario) {
                            $notificacaoFin = [
                                'usuario_id' => $usuario,
                                'demanda_id' => $demanda->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                                'tipo' => 'finalizado',
                                'tipo_referencia' => $demandaComentario->tipo,
                                'conteudo' => 'Novo comentário: Finalizado',
                            ];

                            $notificacoesFinisher[] = $notificacaoFin;
                        }
                    }

                    Notificacao::insert($notificacoesFinisher);

                }

                foreach($demandaColaboradoresIds as $usuario) {
                    $responseOrdem = OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                foreach($demandaUsuariosIds as $usuario) {
                    $responseOrdem = OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                $adminIds = User::where('tipo','admin_8')->where('excluido', null)->get();

                foreach($adminIds as $usuario) {
                    $responseOrdem = OrdemJob::OrdemJobHelper($usuario->id, $demanda->id);
                }

                $demandaComentario->save();

            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível adicionar o seu comentário.'
                ], 400);

            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Comentário adicionado!'
        ], 200);
    }


}
