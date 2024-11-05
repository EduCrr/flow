<?php

namespace App\Http\Controllers;

use App\Models\Demanda;
use App\Models\DemandaAtrasada;
use App\Models\DemandaAtrasadaRecorrencia;
use App\Models\DemandaRecorrencia;
use Illuminate\Support\Facades\Mail;
use App\Models\Notificacao;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use View;

class Controller extends BaseController
{
    protected function criarNotificacoesRecorrencia($demanda, $user, $conteudo){

        $demandaColaboradoresIds = $demanda->demandaColaboradores->pluck('id')->toArray();
        
        if (!in_array($demanda->criador_id, $demandaColaboradoresIds)) {
            $demandaColaboradoresIds[] = $demanda->criador_id;
        }
        
        $demandaColaboradoresIds = array_unique($demandaColaboradoresIds);
        
        $notificacoesReq = [];
        foreach ($demandaColaboradoresIds as $usuario) {
            if ($user->id != $usuario) {
                $notificacao = [
                    'usuario_id' => $usuario,
                    'demanda_id' => $demanda->id,
                    'criado' => date('Y-m-d H:i:s'),
                    'conteudo' => $conteudo,
                    'visualizada' => '0',
                    'tipo' => 'criada',
                ];
                $notificacoesReq[] = $notificacao;
            }
        }
        
        Notificacao::insert($notificacoesReq);
    }

   public function __construct(){

        $demandas = Demanda::select('id', 'final', 'titulo')->where('excluido', null)
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->where('finalizada', 0)
        ->where('entregue', 0)
        ->with(['demandasUsuario' => function ($query) {
            $query->select('demandas_usuarios.id', 'email', 'nome');
        }])
        ->with(['demandasReabertas' => function ($query) {
            $query->where('finalizado', null);
        }])->get();

        if($demandas){
            $dataAtual = date('Y-m-d H:i:s');
            foreach($demandas as $key => $item){
                $demandasReabertas = $item->demandasReabertas;
                if ($demandasReabertas->count() > 0) {
                    $sugerido = $demandasReabertas->sortByDesc('id')->first()->sugerido;
                    $item->final = $sugerido;
                }

                $findAtradaasa = DemandaAtrasada::where('demanda_id', $item->id)->count();

                if($findAtradaasa == 0){
                    if($dataAtual > $item->final){
                        $addAtrasada = new DemandaAtrasada();
                        $addAtrasada->demanda_id = $item->id;
                        $addAtrasada->save();
                        $actionLink = route('Job', ['id' => $item->id]);
                        $bodyEmail = 'A data final da demanda expirou. Por favor, verifique a data de entrega.';
                        $titleEmail = 'O job '.$item->id. ': '.$item->titulo .'. Aviso: Data de entrega da demanda expirou!';
                        foreach($item['demandasUsuario'] as $user){
                        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $user->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($user, $titleEmail) {
                            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                            ->to($user->email)
                            // ->bcc('eduardo.8poroito@gmail.com')
                            ->subject($titleEmail);
                        });
                        }
                    }
                }
            }
        }

        $demandasRecorrentes = DemandaRecorrencia::where('finalizado', 0)
            ->where('entregue', 0)
            ->with('DemandaCampanhaRecorrencia') // Carrega a campanha relacionada
            ->get();

        if ($demandasRecorrentes->isNotEmpty()) {
            $dataAtual = date('Y-m-d');
            foreach ($demandasRecorrentes as $item) {

                // Verifica se a recorrência já foi marcada como atrasada
                $findAtrasada = DemandaAtrasadaRecorrencia::where('recorrencia_id', $item->id)->count();

                // Verifica se a campanha existe antes de continuar
                if ($item->DemandaCampanhaRecorrencia) {
                    $campanha = $item->DemandaCampanhaRecorrencia;
                    $demanda = Demanda::where('id', $campanha->demanda_id)
                        ->with('demandasUsuario')
                        ->first();

                    if ($demanda && $findAtrasada == 0) {
                        if ($dataAtual > $item->data) {
                            // Marca a recorrência como atrasada
                            $addAtrasada = new DemandaAtrasadaRecorrencia();
                            $addAtrasada->recorrencia_id = $item->id;
                            $addAtrasada->save();

                            $actionLink = route('Job', ['id' => $campanha->demanda_id]);
                            $bodyEmail = '('.$campanha->titulo.')'. '. A data final da recorrência expirou. Por favor, verifique a data de entrega.';
                            $titleEmail = 'Aviso: Data de entrega da recorrência expirou!';

                            foreach ($demanda->demandasUsuario as $user) {
                                Mail::send('notify-job', [
                                    'action_link' => $actionLink,
                                    'nome' => $user->nome,
                                    'body' => $bodyEmail,
                                    'titulo' => $titleEmail
                                ], function ($message) use ($user, $titleEmail) {
                                    $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                                        ->to($user->email)
                                        ->subject($titleEmail);
                                });
                            }
                        }
                    }
                }
            }
        }
    }

    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
