<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Resposta;
use App\Models\Notificacao;
use App\Models\Questionamento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\LinhaTempo;
Use Alert;
use App\Models\Demanda;
use App\Models\QuestionamentoLido;
use App\Models\User;
use App\Utils\EnviarMail;
use App\Utils\OrdemJob;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

class RespostasController extends Controller
{
    public function delete(Request $request, $id){

        $resposta = Resposta::find($id);
        if($resposta){
            $resposta->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comentário excluido com sucesso.'
            ], 200);

        }

        return response()->json([
            'success' => false,
            'message' => 'Não foi possível excluir esse comentário.'
        ], 400);

    }

    public function getAnswer(Request $request, $id){

        if($id){
            $response = Resposta::find($id);
            return $response;
        }
        return false;
    }

    public function getAnswerAction(Request $request){
        $user = Auth::User();

        $id = $request->id;
        $validator = Validator::make($request->all(),[
            'newContent' => 'required'
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
            $response = Resposta::find($id);
            $response->conteudo = $request->newContent;
            $response->save();

            $quest = Questionamento::where('id', $response->questionamento_id)->first();

            $demanda = Demanda::where('id', $quest->demanda_id)->where('excluido', null)->with('criador')->with('demandasUsuario')->with('demandaColaboradores')->first();
            $demandaColaboradoresIds = [];
            $demandaColaboradoresIds[] = $demanda->criador_id;
            $demandaColaboradoresIds = array_merge($demandaColaboradoresIds, $demanda->demandaColaboradores->pluck('id')->toArray());
            $demandaColaboradoresIds = array_unique($demandaColaboradoresIds);
            $demandaUsuariosIds = $demanda->demandasUsuario->pluck('id')->toArray();

            foreach ($demandaColaboradoresIds as $usuario) {
                if($user->id != $usuario){
                    QuestionamentoLido::updateOrCreate(
                        [
                            'usuario_id' => $usuario,
                            'comentario_id' => $quest->id,
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
                            'demanda_id' =>  $quest->demanda_id,
                            'tipo_referencia' => 'resposta-'.$id,
                        ],
                        [
                            'criado' => date('Y-m-d H:i:s'),
                            'visualizada' => '0',
                            'conteudo' => 'Resposta editada.',
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
                            'comentario_id' => $quest->id,
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
                            'demanda_id' =>  $quest->demanda_id,
                            'tipo_referencia' => 'resposta-'.$id,
                        ],
                        [
                            'criado' => date('Y-m-d H:i:s'),
                            'visualizada' => '0',
                            'conteudo' => 'Resposta editada.',
                            'tipo' => 'observacao',
                        ]
                    );
                }
            }


            return response()->json([
                'success' => true,
                'message' => 'Resposta atualizada!'
            ], 200);

        }

    }

    function answerCreate(Request $request, $id){
        $user = Auth::User();

        $validator = Validator::make($request->all(),[
            'newContent' => 'required'
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

        if(!$validator->fails()){
            if($id){
                $quest = Questionamento::find($id);

                $demanda = Demanda::where('id', $quest->demanda_id)->with('demandaColaboradores')->with('demandasUsuario')->first();
                $demandaColaboradoresIds = $demanda->demandaColaboradores->pluck('id')->toArray();
                $demandasUsuarioIds = $demanda->demandasUsuario->pluck('id')->toArray();
                $demandaColaboradoresIds[] = $demanda->criador_id;

                $numerosQuest = preg_replace("/[^0-9]/", "", $quest->tipo);

                $demandaResposta = new Resposta();
                $demandaResposta->usuario_id = $user->id;
                $demandaResposta->conteudo = $request->newContent;
                $demandaResposta->questionamento_id = $id;
                $demandaResposta->visualizada_ag = 1;
                $demandaResposta->criado = Carbon::now();

                $demandaResposta->save();

                if (strpos($quest->tipo, "Questionamento") !== false) {
                    $newTimeLine =  new LinhaTempo();
                    $newTimeLine->code = 'resposta';
                    $newTimeLine->usuario_id = $user->id;
                    $newTimeLine->demanda_id = $request->demandaId;
                    $newTimeLine->criado = date('Y-m-d H:i:s');
                    $newTimeLine->status = 'Resposta Q' . $numerosQuest;
                    $newTimeLine->save();
                }

                $notificacoesQuest = [];
                $marcarUsuarioColComentario = [];

                foreach ($demandaColaboradoresIds as $usuario) {
                    if ($user->id != $usuario) {
                        $notificacao = [
                            'usuario_id' => $usuario,
                            'demanda_id' => $demanda->id,
                            'criado' => date('Y-m-d H:i:s'),
                            'visualizada' => '0',
                            'conteudo' => 'Novo comentário: Resposta',
                            'tipo' => 'observacao',
                            'tipo_referencia' => 'resposta-'.$demandaResposta->id,

                        ];

                        $notificacoesQuest[] = $notificacao;

                        $comentarioUsuario = [
                            'usuario_id' => $usuario,
                            'comentario_id' => $quest->id,
                            'criado' => date('Y-m-d H:i:s'),
                            'marcado' => 0,
                            'visualizada' => '0',
                            'tipo' => 'Resposta',
                        ];

                        $marcarUsuarioColComentario[] = $comentarioUsuario;

                    }
                }

                Notificacao::insert($notificacoesQuest);
                QuestionamentoLido::insert($marcarUsuarioColComentario);

                $notificacoesAgency = [];
                $marcarUsuarioAgComentario = [];

                foreach ($demandasUsuarioIds as $usuario) {
                    if ($user->id != $usuario) {
                        $notificacaoAgency = [
                            'usuario_id' => $usuario,
                            'demanda_id' => $demanda->id,
                            'criado' => date('Y-m-d H:i:s'),
                            'visualizada' => '0',
                            'conteudo' => 'Novo comentário: Resposta',
                            'tipo' => 'observacao',
                            'tipo_referencia' => 'resposta-'.$demandaResposta->id,
                        ];

                        $notificacoesAgency[] = $notificacaoAgency;

                        $comentarioUsuario = [
                            'usuario_id' => $usuario,
                            'comentario_id' => $quest->id,
                            'criado' => date('Y-m-d H:i:s'),
                            'marcado' => 0,
                            'visualizada' => '0',
                            'tipo' => 'Resposta',
                        ];

                        $marcarUsuarioAgComentario[] = $comentarioUsuario;

                    }

                }

                Notificacao::insert($notificacoesAgency);
                QuestionamentoLido::insert($marcarUsuarioAgComentario);

                foreach($demandaColaboradoresIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                foreach($demandasUsuarioIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                $adminIds = User::where('notificar_email', 1)->whereNotIn('id', $demandaColaboradoresIds)->where('tipo','admin')->get();

                foreach($adminIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario->id, $demanda->id);
                }

                $actionLink = route('Job', ['id' => $demanda->id]);
                $titleEmail = 'Nova resposta no Job '. $demanda->id . ': '.$demanda->titulo;
                $bodyEmail = 'O job '. $demanda->id . ' recebeu uma nova mensagem.'. '<br/>'.  'Acesse pelo link logo abaixo.';

                try {
                    EnviarMail::enviarEmail($quest->usuario->email, $quest->usuario->nome, $actionLink, $bodyEmail, $titleEmail, $request->newContent, $user->nome);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Oops, ocorreu um erro ao enviar uma notificação via e-mail, mas sua mensagem foi cadastrada em nosso sistema!'
                    ], 400);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Resposta adicionada!'
                ], 200);

            }
        }
        return response()->json([
            'success' => false,
            'message' => 'Não foi possível adicionar o sua resposta.'
        ], 400);

    }

}
