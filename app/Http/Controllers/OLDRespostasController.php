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
            ], Response::HTTP_OK);

        }

        return response()->json([
            'success' => false,
            'message' => 'Não foi possível excluir esse comentário.'
        ], Response::HTTP_BAD_REQUEST);

    }

    public function getAnswer(Request $request, $id){

        if($id){
            $response = Resposta::find($id);
            return $response;
        }
        return false;
    }

    public function getAnswerAction(Request $request){
        $id = $request->id;
        if($id){
            $response = Resposta::find($id);
            $response->conteudo = $request->newContent;
            $quest = Questionamento::where('id', $response->questionamento_id)->first();
            $response->marcado_usuario_id  = $quest->usuario_id;

            $response->save();

            return response()->json([
                'success' => true,
                'message' => 'Comentário atualizado!'
            ], Response::HTTP_OK);

        }

    }

    function answerCreate(Request $request, $id){
        $user = Auth::User();

        $validator = Validator::make($request->all(),[
            'newContent' => 'required'
            ],[
                'newContent.required' => 'Não foi possível adicionar a sua resposta.',
            ]
        );

        if($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], Response::HTTP_BAD_REQUEST);

        }

        if(!$validator->fails()){
            if($id){
                $quest = Questionamento::find($id);

                $demanda = Demanda::select('id', 'titulo', 'criador_id')->where('id', $quest->demanda_id)->with('demandaColaboradores')->with('demandasUsuario')->first();
                $demandaColaboradoresIds = $demanda->demandaColaboradores->pluck('id')->toArray();
                $demandasUsuarioIds = $demanda->demandasUsuario->pluck('id')->toArray();
                $demandaColaboradoresIds[] = $demanda->criador_id;

                $numerosQuest = preg_replace("/[^0-9]/", "", $quest->tipo);

                $demandaResposta = new Resposta();
                $demandaResposta->usuario_id = $user->id;
                $demandaResposta->conteudo = $request->newContent;
                $demandaResposta->marcado_usuario_id  = $quest->usuario_id;
                $demandaResposta->questionamento_id = $id;
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

                $agenciaNotificacao = new Notificacao();
                $agenciaNotificacao->demanda_id = $request->demandaId;
                $agenciaNotificacao->conteudo = 'Seu comentário recebeu uma nova resposta.';
                $agenciaNotificacao->criado = date('Y-m-d H:i:s');
                $agenciaNotificacao->visualizada = '0';
                $agenciaNotificacao->tipo = 'observacao';
                $agenciaNotificacao->tipo_referencia =  'resposta-'.$quest->id;
                $agenciaNotificacao->usuario_id = $quest->usuario_id;
                $agenciaNotificacao->save();

                $notificacoesQuest = [];
                foreach ($demandaColaboradoresIds as $usuario) {
                    if ($user->id != $usuario) {
                        $notificacao = [
                            'usuario_id' => $usuario,
                            'demanda_id' => $demanda->id,
                            'criado' => date('Y-m-d H:i:s'),
                            'visualizada' => '0',
                            'conteudo' => 'Novo comentário: Resposta',
                            'tipo' => 'observacao',
                            'tipo_referencia' => 'resposta-'.$quest->id,

                        ];

                        $notificacoesQuest[] = $notificacao;
                    }
                }

                Notificacao::insert($notificacoesQuest);

                $notificacoesAgency = [];

                foreach ($demanda['demandasUsuario'] as $usuario) {
                    if ($usuario->id != $quest->usuario_id) {
                        $notificacaoAgency = [
                            'usuario_id' => $usuario->id,
                            'demanda_id' => $demanda->id,
                            'criado' => date('Y-m-d H:i:s'),
                            'visualizada' => '0',
                            'conteudo' => 'Novo comentário: Resposta',
                            'tipo' => 'observacao',
                            'tipo_referencia' => 'resposta-'.$quest->id,
                        ];

                        $notificacoesAgency[] = $notificacaoAgency;

                    }

                }

                Notificacao::insert($notificacoesAgency);

                if( $quest->visualizada_col == 0 ){
                    $quest->visualizada_col = 1;
                    $quest->save();
                }

                $actionLink = route('Job', ['id' => $demanda->id]);
                $titleEmail = 'Nova resposta no Job '. $demanda->id . ': '.$demanda->titulo;
                $bodyEmail = 'O job '. $demanda->id . ' recebeu uma nova mensagem.'. '<br/>'.  'Acesse pelo link logo abaixo.';

                $sendEmail = EnviarMail::enviarEmail($quest->usuario->email, $quest->usuario->nome, $actionLink, $bodyEmail, $titleEmail, $request->newContent, $user->nome);

                foreach($demandaColaboradoresIds as $usuario) {
                    $responseOrdem = OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                foreach($demandasUsuarioIds as $usuario) {
                    $responseOrdem = OrdemJob::OrdemJobHelper($usuario, $demanda->id);
                }

                $adminIds = User::where('notificar_email', 1)->whereNotIn('id', $demandaColaboradoresIds)->where('tipo','admin')->get();

                foreach($adminIds as $usuario) {
                    $responseOrdem = OrdemJob::OrdemJobHelper($usuario->id, $demanda->id);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Resposta adicionada!'
                ], Response::HTTP_OK);

            }
        }
        return response()->json([
            'success' => false,
            'message' => 'Não foi possível adicionar o sua resposta.'
        ], Response::HTTP_BAD_REQUEST);

    }

}
