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
use App\Models\QuestionamentoLido;
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
            QuestionamentoLido::where('comentario_id', $comentario->id)->delete();
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
            $comentary = Questionamento::where('id', $id)->with(['lidos' => function($query) {
                $query->where('marcado', 1);
            }])->first();
            $lidosIds = $comentary->lidos->pluck('usuario_id')->toArray();
            $data = [
                'comentary' => $comentary,
                'lidosIds' => $lidosIds,

            ];
            return $data;
        }
        return false;
    }

    //editar via ajax
    public function getComentaryAction(Request $request){
        $user = Auth::User();
        $id = $request->id;
        $validator = Validator::make($request->all(),[
            'newContent' => 'required',
            ],[
                'newContent.required' => 'O campo não pode ser vazio!',
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
            $comentaryUser = QuestionamentoLido::where('comentario_id', $id)
            ->where('marcado', 1)
            ->get();
            $comentaryUserIds = $comentaryUser->pluck('usuario_id')->toArray();
            $isAlteracaoComentario = $comentary->tipo;
            $demanda = Demanda::where('id', $comentary->demanda_id)->where('excluido', null)->with('criador')->with('demandasUsuario')->with('demandaColaboradores')->first();
            $demandaColaboradoresIds = [];
            $demandaColaboradoresIds[] = $demanda->criador_id;
            $demandaColaboradoresIds = array_merge($demandaColaboradoresIds, $demanda->demandaColaboradores->pluck('id')->toArray());
            $demandaColaboradoresIds = array_unique($demandaColaboradoresIds);
            $demandaUsuariosIds = $demanda->demandasUsuario->pluck('id')->toArray();

            if(isset($request->marcar_usuario) && count($request->marcar_usuario) > 0){

                if (strpos($isAlteracaoComentario, 'Alteração') !== false) {
                    $diff = array_diff($request->marcar_usuario, $demandaUsuariosIds);
                    if (!empty($diff)) {
                        return response()->json([
                            'success' => false,
                            'title' => "Ops",
                            'message' => 'Esse usuário não pode ser marcado para essa alteração.'
                        ], 400);
                    }
                }
            }

            $comentary->descricao = $request->newContent;
            $comentary->marcado_usuario_id = null;

            foreach ($demandaColaboradoresIds as $usuario) {
                if($user->id != $usuario){
                    QuestionamentoLido::updateOrCreate(
                        [
                            'usuario_id' => $usuario,
                            'comentario_id' => $comentary->id,
                        ],
                        [
                            'criado' => date('Y-m-d H:i:s'),
                            'marcado' => '0',
                            'visualizada' => '0',
                        ]
                    );

                    Notificacao::updateOrCreate(
                        [
                            'usuario_id' => $usuario,
                            'demanda_id' =>  $comentary->demanda_id,
                            'tipo_referencia' => 'comentario-'.$comentary->id,
                        ],
                        [
                            'criado' => date('Y-m-d H:i:s'),
                            'visualizada' => '0',
                            'conteudo' => 'Comentário editado.',
                            'tipo' => 'observacao',
                        ]
                    );
                }

            }

            foreach ($demandaUsuariosIds as $usuario) {
                if($user->id != $usuario){
                    QuestionamentoLido::updateOrCreate(
                        [
                            'usuario_id' => $usuario,
                            'comentario_id' => $comentary->id,
                        ],
                        [
                            'criado' => date('Y-m-d H:i:s'),
                            'marcado' => '0',
                            'visualizada' => '0',
                        ]
                    );

                    Notificacao::updateOrCreate(
                        [
                            'usuario_id' => $usuario,
                            'demanda_id' =>  $comentary->demanda_id,
                            'tipo_referencia' => 'comentario-'.$comentary->id,
                        ],
                        [
                            'criado' => date('Y-m-d H:i:s'),
                            'visualizada' => '0',
                            'conteudo' => 'Comentário editado.',
                            'tipo' => 'observacao',
                        ]
                    );
                }
            }

            if(!isset($request->marcar_usuario)){
                QuestionamentoLido::where('comentario_id', $comentary->id)
                ->where('marcado', 1)
                ->update(['marcado' => 0]);
            }

            if(isset($request->marcar_usuario) && count($request->marcar_usuario) > 0){
                $usuariosParaDesmarcar = array_diff($comentaryUserIds, $request->marcar_usuario);

                if (!empty($usuariosParaDesmarcar)) {
                    QuestionamentoLido::whereIn('usuario_id', $usuariosParaDesmarcar)
                        ->where('comentario_id', $id)
                        ->where('marcado', 1)
                        ->update(['marcado' => 0]);
                }
                foreach($request->marcar_usuario as $item){

                    if ($item) {
                        QuestionamentoLido::updateOrCreate(
                            [
                                'usuario_id' => $item,
                                'comentario_id' => $comentary->id,
                            ],
                            [
                                'criado' => date('Y-m-d H:i:s'),
                                'marcado' => 1,
                                'visualizada' => '0',
                            ]
                        );

                        Notificacao::updateOrCreate(
                            [
                                'usuario_id' => $item,
                                'demanda_id' =>  $comentary->demanda_id,
                                'tipo_referencia' => 'comentario-'.$comentary->id,
                                'tipo' => 'observacao',
                            ],
                            [
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                                'conteudo' => 'Você foi marcado em um comentário.',
                            ]
                        );

                    }

                }
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
        $demanda = Demanda::where('id', $id)->where('excluido', null)->with('criador')->with('demandasUsuario')->with('demandaColaboradores')->first();
        $demandaColaboradoresIds = [];
        $demandaColaboradoresIds[] = $demanda->criador_id;
        $demandaColaboradoresIds = array_merge($demandaColaboradoresIds, $demanda->demandaColaboradores->pluck('id')->toArray());
        $demandaColaboradoresIds = array_unique($demandaColaboradoresIds);
        $demandaUsuariosIds = $demanda->demandasUsuario->pluck('id')->toArray();

        //PADRAO E-MAIL
        $actionLink = route('Job', ['id' => $demanda->id]);
        $titleEmail = 'Novo comentário no Job '.$demanda->id. ': '.$demanda->titulo;
        $bodyEmailDefault = 'O job '. $demanda->id . ': '.$demanda->titulo .', recebeu uma nova mensagem.'. '<br/>'.  'Acesse pelo link logo abaixo.';

        //PADRAO NOTIFICACAO
        $tipoNotify = '';
        $bodyNotify = '';
        $bodyNotifyMarked = '';
        $typeRefNotify = '';


        $validator = Validator::make($request->all(),[
           'conteudo' => 'required',
           'tipo' => 'required',
            ],[
                'conteudo.required' => 'Não foi possível adicionar o seu comentário.',
                'tipo.required' => 'Preencha o campo tipo.',
            ]
        );

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);

        }

        if(!$validator->fails()){

            $comentario = new Questionamento();
            $comentario->usuario_id = $user->id;
            $comentario->descricao = $conteudo;
            $comentario->demanda_id = $id;
            $comentario->criado = Carbon::now();

            $newTimeLine = new LinhaTempo();
            $newTimeLine->demanda_id = $id;
            $newTimeLine->usuario_id = $user->id;
            $newTimeLine->criado = date('Y-m-d H:i:s');
            $hasObsCount = Questionamento::where('demanda_id', $id)->where('tipo', 'like', '%Observação%')->count();
            $hasEntregueCount = Questionamento::where('demanda_id', $id)->where('tipo', 'like', '%Entregue%')->count();
            $hasFinalizadoCount = Questionamento::where('demanda_id', $id)->where('tipo', 'like', '%Finalizado%')->count();
            $hasAlterationAltCount = Questionamento::where('demanda_id', $id)->where('tipo', 'like', '%Alteração%')->count();
            $hasQuestsCount = Questionamento::where('demanda_id', $id)->where('tipo', 'like', '%Questionamento%')->count();


            if($tipo == 'questionamento'){

                $bodyEmail = 'O job '.$id . ' recebeu um novo questionamento.'. '<br/>'.  'Acesse pelo link logo abaixo.';

                if($hasQuestsCount == 0){
                    $newTimeLine->status = 'Questionamento 1';
                    $newTimeLine->code = 'questionamento';
                    $newTimeLine->save();
                    $comentario->tipo = 'Questionamento 1';
                }else{
                    $newTimeLine->status = 'Questionamento '.($hasQuestsCount + 1);
                    $newTimeLine->code = 'questionamento';
                    $newTimeLine->save();
                    $comentario->tipo = 'Questionamento '.($hasQuestsCount + 1);
                }

                $comentario->cor = '#d4624d';
                $tipoNotify = 'questionamento';
                $bodyNotifyMarked = 'Você foi marcado em um novo questionamento.';
                $bodyNotify = 'Novo questionamento.';


            }

            if($tipo == 'observacao'){
                if($hasObsCount == 0){
                    $comentario->tipo = 'Observação 1';
                    $comentario->cor = '#f9bc0b';
                }else if($hasObsCount > 0){
                    $comentario->tipo = 'Observação '.($hasObsCount + 1);
                    $comentario->cor = '#f9bc0b';
                }

                $tipoNotify = 'observacao';
                $bodyNotifyMarked = 'Você foi marcado em uma nova observação.';
                $bodyNotify = 'Novo comentário: Observação';

            }

            if($tipo == 'entregue'){

                if($hasEntregueCount == 0){
                    $comentario->tipo = 'Entregue 1';
                    $comentario->cor = '#44a2d2';
                }else if($hasEntregueCount > 0){
                    $comentario->tipo = 'Entregue '.($hasEntregueCount + 1);
                    $comentario->cor = '#44a2d2';
                }

                $tipoNotify = 'entregue';
                $bodyNotifyMarked = 'Você foi marcado em um novo comentário: Entregue';
                $bodyNotify = 'Novo comentário: Entregue';

            }

            if($tipo == 'alteracao'){

                if(isset($request->marcar_usuario) && count($request->marcar_usuario) > 0){

                    $diff = array_diff($request->marcar_usuario, $demandaUsuariosIds);
                    if (!empty($diff)) {
                        return response()->json([
                            'success' => false,
                            'title' => "Ops",
                            'message' => 'Esse usuário não pode ser marcado para essa alteração.'
                        ], 400);
                    }
                }

                $conteudoNotificacao = '';

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
                            DemandaAtrasada::where('demanda_id', $demanda->id)->delete();
                        }
                    }else if($countDemandasReabertas > 0){
                        if (strtotime($request->sugeridoComment) > strtotime($demandasReaberta->sugerido)) {
                            // $request->sugeridoComment é maior que $demanda->final
                            $demandasReaberta->sugerido = $request->sugeridoComment;
                            $demandasReaberta->save();
                            DemandaAtrasada::where('demanda_id', $demanda->id)->delete();
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
                    $comentario->tipo = 'Alteração 1';
                    $comentario->cor = '#d56551';
                }else if($hasAlterationAltCount > 0){
                    $comentario->tipo = 'Alteração '.($hasAlterationAltCount + 1);
                    $comentario->cor = '#d56551';
                }

                $tipoNotify = 'criada';
                $bodyNotifyMarked = 'Você foi marcado no comentário: ' . $conteudoNotificacao;
                $bodyNotify = 'Novo comentário: '.$conteudoNotificacao;

                $comentario->save();
                //salvar tempo
                $newTimeJob->save();

                //salvar relacao comentario/pauta

                $comentarioPauta = new ComentarioPauta();
                $comentarioPauta->comentario_id = $comentario->id;
                $comentarioPauta->demandapauta_id = $newTimeJob->id;
                $comentarioPauta->save();

                //countAlteracao
                $newAlteration = new Alteracao();
                $newAlteration->demanda_id = $demanda->id;
                $newAlteration->save();

                $demanda->save();

                $titleEmail = 'Job '. $demanda->id . ': '.$demanda->titulo.', foi ' . strtolower($conteudoNotificacao);
                $bodyEmailDefault = 'O job '. $demanda->id . ': '.$demanda->titulo .', recebeu uma nova alteração.'. '<br/>'.  'Acesse pelo link logo abaixo.';

            }

            if($tipo == 'finalizado'){

                if($hasFinalizadoCount == 0){
                    $comentario->tipo = 'Finalizado 1';
                    $comentario->cor = '#3dbb3d';
                }else if($hasFinalizadoCount > 0){
                    $comentario->tipo = 'Finalizado '.($hasFinalizadoCount + 1);
                    $comentario->cor = '#3dbb3d';
                }

                $newTimeLine->status = 'Finalizado';
                $newTimeLine->save();

                $tipoNotify = 'finalizado';
                $bodyNotifyMarked = 'Você foi marcado em um novo comentário: Finalizado';
                $bodyNotify = 'Novo comentário: Finalizado';

            }

            $comentario->save();
            $typeRefNotify = 'comentario-'.$comentario->id;

            $usuarioComentario = [];
            $notificacoesAg = [];
            $notificacoesCol = [];


            if($request->marcar_usuario && count($request->marcar_usuario) > 0){
                //marcar selecionados

                $marcarUsuarioComentario = [];
                $notificacoes = [];

                foreach($request->marcar_usuario as $item){
                    $comentarioUsuario = [
                        'usuario_id' => $item,
                        'comentario_id' => $comentario->id,
                        'criado' => date('Y-m-d H:i:s'),
                        'marcado' => 1,
                        'visualizada' => '0',
                    ];
                    $marcarUsuarioComentario[] = $comentarioUsuario;

                    $notificacao = [
                        'usuario_id' => $item,
                        'demanda_id' => $demanda->id,
                        'criado' => date('Y-m-d H:i:s'),
                        'visualizada' => '0',
                        'tipo' => $tipoNotify,
                        'tipo_referencia' => $typeRefNotify,
                        'conteudo' => $bodyNotifyMarked,
                    ];

                    $notificacoes[] = $notificacao;
                }

                QuestionamentoLido::insert($marcarUsuarioComentario);
                Notificacao::insert($notificacoes);

                foreach ($demandaColaboradoresIds as $usuario) {
                    if ($user->id != $usuario && !in_array($usuario, $request->marcar_usuario)) {
                        list($comentarioUsuarioColaborador, $notificacao) = $this->criarDadosComentarioNotificacao($usuario, $comentario->id, $demanda->id, $tipoNotify, $typeRefNotify, $bodyNotify);

                        $usuarioComentario[] = $comentarioUsuarioColaborador;
                        $notificacoesCol[] = $notificacao;
                    }
                }

                foreach ($demandaUsuariosIds as $usuario) {
                    if ($user->id != $usuario && !in_array($usuario, $request->marcar_usuario)) {
                        list($comentarioUsuarioAgencia, $notificacao) = $this->criarDadosComentarioNotificacao($usuario, $comentario->id, $demanda->id, $tipoNotify, $typeRefNotify, $bodyNotify);
                        $usuarioComentario[] = $comentarioUsuarioAgencia;
                        $notificacoesAg[] = $notificacao;
                    }
                }


                QuestionamentoLido::insert($usuarioComentario);
                Notificacao::insert($notificacoesAg);
                Notificacao::insert($notificacoesCol);

            }else if(!$request->marcar_usuario){
                foreach ($demandaColaboradoresIds as $usuario) {
                    if ($user->id != $usuario) {
                        list($comentarioUsuarioColaborador, $notificacao) = $this->criarDadosComentarioNotificacao($usuario, $comentario->id, $demanda->id, $tipoNotify, $typeRefNotify, $bodyNotify);

                        $usuarioComentario[] = $comentarioUsuarioColaborador;
                        $notificacoesCol[] = $notificacao;
                    }
                }

                foreach ($demandaUsuariosIds as $usuario) {
                    if ($user->id != $usuario) {
                        list($comentarioUsuarioAgencia, $notificacao) = $this->criarDadosComentarioNotificacao($usuario, $comentario->id, $demanda->id, $tipoNotify, $typeRefNotify, $bodyNotify);
                        $usuarioComentario[] = $comentarioUsuarioAgencia;
                        $notificacoesAg[] = $notificacao;
                    }
                }

                QuestionamentoLido::insert($usuarioComentario);
                Notificacao::insert($notificacoesAg);
                Notificacao::insert($notificacoesCol);
            }


            $response = $comentario->save();

            if($response){

                foreach($demandaColaboradoresIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                foreach($demandaUsuariosIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                $adminIds = User::where('tipo','admin_8')->where('excluido', null)->get();

                foreach($adminIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario->id, $demanda->id);
                }

                $usersToNotifyByEmail = User::select('id', 'email', 'nome')
                ->where('notificar_email', 1)
                ->where(function ($query) use ($demandaColaboradoresIds, $demandaUsuariosIds) {
                    $query->whereIn('id', $demandaColaboradoresIds)
                        ->orWhereIn('id', $demandaUsuariosIds);
                })
                ->get();

                // Envio de e-mails
                foreach ($usersToNotifyByEmail as $item) {
                    if ($user->id != $item->id) {
                        try {
                            EnviarMail::enviarEmail($item->email, $item->nome, $actionLink, $bodyEmailDefault, $titleEmail, $conteudo, $user->nome);
                        } catch (\Exception $e) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Oops, ocorreu um erro ao enviar uma notificação via e-mail, mas sua mensagem foi cadastrada em nosso sistema!'
                            ], 400);
                        }
                    }
                }

            }

        }

        return response()->json([
            'success' => true,
            'message' => 'Comentário adicionado!'
        ], 200);
    }

    private function criarDadosComentarioNotificacao($usuario, $comentarioId, $demandaId, $tipoNotify, $typeRefNotify, $bodyNotify) {
        $comentario = [
            'usuario_id' => $usuario,
            'comentario_id' => $comentarioId,
            'criado' => now(),
            'visualizada' => '0',
        ];

        $notificacao = [
            'usuario_id' => $usuario,
            'demanda_id' => $demandaId,
            'criado' => now(),
            'visualizada' => '0',
            'tipo' => $tipoNotify,
            'tipo_referencia' => $typeRefNotify,
            'conteudo' => $bodyNotify,
        ];

        return [$comentario, $notificacao];
    }
}
