<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Demanda;
use App\Models\Questionamento;
use App\Models\User;
use App\Models\Marca;
use App\Models\Agencia;
use App\Models\DemandaImagem;
use App\Models\DemandaMarca;
use App\Models\LinhaTempo;
use App\Models\Notificacao;
use App\Models\DemandaReaberta;
use Carbon\Carbon;
use App\Models\DemandaUsuario;
use App\Models\DemandaTempo;
use App\Models\DemandaComplemento;
use App\Models\AgenciaDemandaUsuario;
use Illuminate\Support\Facades\Validator;
Use Alert;
use App\Models\DemandaColaborador;
use App\Models\DemandaOrdem;
use App\Models\DemandaOrdemJob;
use App\Models\MarcaColaborador;
use App\Utils\OrdemJob;
use App\Utils\ValidationUtil;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Response;

class AdminAgenciaController extends Controller
{
    // public function job(Request $request, $id){

    //     $demanda = Demanda::where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->where('id', $id)->with('imagens')->with('criador')->with('demandasReabertas')->with(['prazosDaPauta.agencia', 'prazosDaPauta.comentarios'])->with(['marcas' => function ($query) {
    //     $query->where('excluido', null);
    //     }])->first();

    //     $user = Auth::User();
    //     $getAgencyAdmin = $user->usuariosAgencias()->first();

    //     if($demanda){
    //         $demanda['agencia'] = $demanda->agencia()->with(['agenciasUsuarios' => function ($query) {
    //         $query->where('excluido', null);
    //         }])->first();

    //         $demanda['questionamentos'] = $demanda->questionamentos()->where('excluido', null)->with(['usuario' => function ($query) {
    //         $query->where('excluido', null);
    //         }])->with('respostas.usuario')->get();

    //         $demanda['demandasUsuario'] = $demanda->demandasUsuario()->where('excluido', null)->get();

    //         foreach($demanda['prazosDaPauta'] as $key => $item) {
    //             if($item->finalizado !== null) {
    //                 $iniciado = \Carbon\Carbon::parse($item->iniciado);
    //                 $finalizado = \Carbon\Carbon::parse($item->finalizado);
    //                 $duracao = null;
    //                 $diaAtual = \Carbon\Carbon::now();

    //                 // verifica se a demanda foi criada antes ou depois das 17h
    //                 $iniciadoDepoisDas17h = $iniciado->gte($iniciado->copy()->setHour(17));
    //                 if ($iniciadoDepoisDas17h) {
    //                     // se foi criada depois das 17h, conta o dia seguinte como o primeiro dia útil
    //                     $diasUteis = $iniciado->copy()->addDay()->diffInWeekdays($finalizado, true);
    //                 } else {
    //                     // se foi criada antes das 17h, conta o dia atual como o primeiro dia útil
    //                     $diasUteis = $iniciado->diffInWeekdays($finalizado, true);
    //                 }

    //                 if($diasUteis == 0 || $diasUteis == 1) {
    //                     $duracao = "Menos de 1 dia";
    //                 } else if($diasUteis > 1) {
    //                     $duracao = $diasUteis . " dias";
    //                 }

    //                 $demanda['prazosDaPauta'][$key]->final = $duracao;
    //             } else {
    //                 $demanda['prazosDaPauta'][$key]->final = null;
    //             }
    //         }

    //         $idsAgUser = [];
    //         $showAg = false;

    //         foreach($demanda['demandasUsuario'] as $item){
    //             array_push($idsAgUser, $item->id);
    //         }

    //         $isSend = LinhaTempo::where('demanda_id', $id)->where('status', 'Entregue')->count();
    //         $entregue = Demanda::where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->where('id', $id)->where('entregue', '1')->count();

    //         if(in_array($user->id, $idsAgUser)){
    //             //Ler comentários
    //             $showAg = true;
    //             foreach($demanda['questionamentos'] as $quest){
    //                 if( $quest->visualizada_ag == 0){
    //                     $quest->visualizada_ag = 1;
    //                     $quest->save();
    //                 }

    //                 foreach($quest['respostas'] as $res){
    //                     if( $res->visualizada_ag == 0){
    //                         $res->visualizada_ag = 1;
    //                         $res->save();
    //                     }
    //                 }
    //             }
    //         }else{
    //             $showAg = false;
    //         }

    //         if ($demanda->finalizada == 1) {
    //             $porcentagem = 100;
    //         } else {
    //             // Obter o total de prazosDaPauta finalizados da demanda
    //             $totalFinalizados = $demanda->prazosDaPauta()->whereNotNull('finalizado')->count();

    //             // Obter o total de prazosDaPauta não finalizados da demanda
    //             $totalNaoFinalizados = $demanda->prazosDaPauta()->whereNull('finalizado')->count();

    //             // Calcular a porcentagem com base nos prazosDaPauta finalizados e não finalizados da demanda
    //             $totalPrazos = $totalFinalizados + $totalNaoFinalizados;
    //             if ($totalPrazos == 0) {
    //                 $porcentagem = 0;
    //             } elseif ($totalFinalizados == 0) {
    //                 $porcentagem = 10;
    //             } else {
    //                 $porcentagem = round(($totalFinalizados / $totalPrazos) * 95);
    //             }
    //         }

    //         // Adicionar a porcentagem como um atributo da demanda
    //         $demanda->porcentagem = $porcentagem;
    //         $lineTime = LinhaTempo::where('demanda_id', $id)->with('usuario')->get();
    //         return view('Agencia/job', [
    //             'demanda' => $demanda,
    //             'user' => $user,
    //             'showAg' => $showAg,
    //             'isSend' => $isSend,
    //             'lineTime' => $lineTime,
    //             'entregue' => $entregue,
    //             'getAgencyAdmin' => $getAgencyAdmin
    //         ]);

    //     }else{
    //         return redirect('/index')->with('warning', 'Esse job não está disponível.' );
    //     }

    // }

    // public function jobs(Request $request){
    //     $user = Auth::User();
    //     $agencies = null;
    //     $search = $request->search;
    //     $aprovada = $request->aprovada;
    //     $priority = $request->category_id;
    //     $inTime = $request->in_tyme;
    //     $marca = $request->marca_id;
    //     $colaborador = $request->colaborador_id;
    //     $dateRange = $request->dateRange;
    //     $ordem_filtro = $request->ordem_filtro;
    //     $porpagina = $request->input('porpagina', 15);

    //     $demandas = Demanda::where('etapa_1', 1)
    //     ->where('etapa_2', 1)
    //     ->where('demandas.excluido', null)
    //     ->with(['marcas' => function ($query) {
    //         $query->where('excluido', null);
    //     }])
    //     ->with(['agencia' => function ($query) {
    //         $query->where('excluido', null);
    //     }])
    //     ->with(['demandasReabertas' => function ($query) {
    //         $query->where('excluido', null);
    //         $query->where('finalizado', null);
    //     }])
    //     ->whereHas('demandasUsuarioAdmin', function ($query) use ($user) {
    //         $query->where('usuario_id', $user->id);
    //     })
    //     ->with('demandasUsuario')
    //     ->withCount(['demandasUsuario' => function ($query) use ($user) {
    //         $query->where('usuario_id', $user->id);
    //     }])
    //     ->orderBy('id', 'DESC');

    //     $coluna = $request->query('coluna');
    //     $ordem = $request->query('ordem');

    //     $orderDirection = ($ordem == 'asc') ? 'asc' : 'desc';

    //     if($coluna == 'job'){
    //         $demandas->orderBy('id', $orderDirection);
    //     }

    //     if($coluna == 'titulo'){
    //         $demandas->orderBy('titulo', $orderDirection);
    //     }

    //     if($coluna == 'prioridade'){
    //         $demandas->orderBy('prioridade', $orderDirection);
    //     }

    //     if ($coluna == 'criador') {
    //         $demandas = $demandas->join('usuarios', 'demandas.criador_id', '=', 'usuarios.id')
    //             ->orderBy('usuarios.nome', $orderDirection);
    //     }

    //     if ($coluna == 'marca') {
    //         $demandas = $demandas->join('demandas_marcas', 'demandas.id', '=', 'demandas_marcas.demanda_id')
    //             ->join('marcas', 'demandas_marcas.marca_id', '=', 'marcas.id')
    //             ->orderBy('marcas.nome', $orderDirection);
    //     }

    //     if ($coluna == 'inicial') {
    //         $demandas = $demandas->orderBy('inicio', $orderDirection);
    //     }

    //     if ($coluna == 'entrega') {
    //         $demandas = $demandas->orderBy('final', $orderDirection);
    //     }

    //     if ($coluna == 'status') {
    //         $demandas->orderBy('status', $orderDirection);
    //     }

    //     if($search){
    //         $demandas->where('titulo', 'like', "%$search%");
    //     }

    //     if($inTime != ''){
    //         if($inTime == 2){
    //             $dataAtual = Carbon::now()->toDateString();
    //             $demandas->whereDate('final', '<', $dataAtual)->where('finalizada', 0);
    //         }else{
    //             $demandas->where('atrasada', '=', $inTime)->where('finalizada', 1);
    //         }
    //     }

    //     if ($ordem_filtro) {
    //         if ($ordem_filtro === 'crescente') {
    //             $demandas->orderBy('id', 'ASC');
    //         } elseif ($ordem_filtro === 'decrescente') {
    //             $demandas->orderBy('id', 'DESC');
    //         }elseif ($ordem_filtro === 'alfabetica') {
    //             $demandas->orderBy('titulo', 'ASC');
    //         }
    //     }

    //     if($aprovada){
    //         if($aprovada == 'finalizados'){
    //              $demandas->where('finalizada', '1');
    //         }else if($aprovada == 'em_pauta'){
    //             $demandas->where('em_pauta', '1')->where('finalizada', '0')->where('entregue', '0')->where('pausado', '0');
    //         }else if($aprovada == 'pendentes'){
    //             $demandas->where('em_pauta', '0')->where('finalizada', '0')->where('entregue', '0')->where('pausado', '0');
    //         }else if($aprovada == 'entregue'){
    //             $demandas->where('em_pauta', '0')->where('finalizada', '0')->where('entregue', '1')->where('pausado', '0');
    //         }else if($aprovada == 'recebidos'){
    //             $demandas->where('em_pauta', '0')->where('finalizada', '0')->where('entregue', '0')->where('recebido', 1)->where('entregue_recebido', 0)->where('pausado', '0');
    //         }else if($aprovada == 'pausados'){
    //             $demandas->where('pausado', '1');
    //         }
    //     }

    //     if ($dateRange) {
    //         [$date, $endDate] = explode(' - ', $dateRange);
    //         $date = Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
    //         $endDate = Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d');
    //         $demandas->where(function($query) use ($date, $endDate) {
    //         $query->whereDate('inicio', '>=', $date)
    //             ->whereDate('inicio', '<=', $endDate)
    //             ->orWhereDate('final', '>=', $date)
    //             ->whereDate('final', '<=', $endDate);
    //         });
    //     }else{
    //         $dateRange = '';
    //     }

    //     if($priority){
    //         $demandas->where('prioridade', $priority);
    //     }

    //     if($marca != '0' && $marca){
    //         $demandas->whereHas('marcas', function($query)  use($marca){
    //             $query->where('marcas.id', $marca);
    //             $query->where('marcas.excluido', null);
    //         });
    //     }

    //     if($colaborador != '0' && $colaborador){
    //         $demandas->whereHas('criador', function($query)  use($colaborador){
    //             $query->where('usuarios.id', $colaborador);
    //             $query->where('usuarios.excluido', null );
    //         });
    //     }

    //     $demandas->withCount(['questionamentos as count_questionamentos' => function ($query) {
    //         $query->where('visualizada_ag', 0)->where('excluido', null);
    //     }])
    //     ->withCount(['questionamentos as count_respostas' => function ($query) {
    //         $query->whereHas('respostas', function ($query) {
    //             $query->where('visualizada_ag', 0);
    //         });
    //     }]);


    //     $perPage = $request->input('porpagina', 15);

    //     $demandas = $demandas->paginate($perPage)->withQueryString();


    //     foreach ($demandas as $demanda) {
    //         if ($demanda->finalizada == 1) {
    //             $porcentagem = 100;
    //         } else {
    //             // Obter o total de prazosDaPauta finalizados da demanda
    //             $totalFinalizados = $demanda->prazosDaPauta()->whereNotNull('finalizado')->count();

    //             // Obter o total de prazosDaPauta não finalizados da demanda
    //             $totalNaoFinalizados = $demanda->prazosDaPauta()->whereNull('finalizado')->count();

    //             // Calcular a porcentagem com base nos prazosDaPauta finalizados e não finalizados da demanda
    //             $totalPrazos = $totalFinalizados + $totalNaoFinalizados;
    //             if ($totalPrazos == 0) {
    //                 $porcentagem = 0;
    //             } elseif ($totalFinalizados == 0) {
    //                 $porcentagem = 10;
    //             } else {
    //                 $porcentagem = round(($totalFinalizados / $totalPrazos) * 95);
    //             }
    //         }
    //         // Adicionar a porcentagem como um atributo da demanda
    //         $demanda->porcentagem = $porcentagem;

    //         //ajustar final quando estiver reaberta

    //         $demandasReabertas = $demanda->demandasReabertas;
    //         if ($demandasReabertas->count() > 0) {
    //             $sugerido = $demandasReabertas->sortByDesc('id')->first()->sugerido;
    //             $demanda->final = $sugerido;
    //         }
    //     }

    //     // $brands = Marca::where('excluido', null)->get();
    //     $brands = User::where('id', $user->id)->with('marcas')->first();
    //     // $agencies = Agencia::where('excluido', null)->get();

    //     $reset = true;
    //     $arrayOrdem = null;
    //     $ordemValue = null;


    //     if ($request->ajax()) {
    //         $view = view('ordem-agencia', compact('demandas', 'arrayOrdem', 'ordem', 'reset'))->render();
    //         return response($view)->header('Content-Type', 'text/html');
    //     }

    //     return view('Agencia/jobs', [
    //         'demandas' => $demandas,
    //         'search' => $search,
    //         'priority' => $priority,
    //         'aprovada' => $aprovada,
    //         'inTime' => $inTime,
    //         'brands' => $brands['marcas'],
    //         'marca' => $marca,
    //         'agencies' => $agencies,
    //         'dateRange' => $dateRange,
    //         'ordem_filtro' => $ordem_filtro,
    //         'ordem' => $ordem,
    //         'porpagina' => $porpagina,
    //         'arrayOrdem' => $arrayOrdem,
    //         'ordemValue' => $ordemValue
    //     ]);

    // }

    public function jobs(Request $request){

        $user = Auth::User();

        $demandas = Demanda::where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->where('demandas.excluido', null)
        ->with(['marcas' => function ($query) {
            $query->where('excluido', null);
        }])
        ->with(['agencia' => function ($query) {
            $query->where('excluido', null);
        }])
        ->with(['demandasReabertas' => function ($query) {
            $query->where('excluido', null);
            $query->where('finalizado', null);
        }])
        ->whereHas('demandasUsuarioAdmin', function ($query) use ($user) {
            $query->where('usuario_id', $user->id);
        })
        ->with('demandasUsuario')
        ->withCount(['demandasUsuario' => function ($query) use ($user) {
            $query->where('usuario_id', $user->id);
        }]);

        $search = $request->search;
        $jobId = $request->jobId;
        $aprovada = $request->aprovada;
        $priority = $request->category_id;
        $inTime = $request->in_tyme;
        $marca = $request->marca_id;
        $dateRange = $request->dateRange;
        $colaborador = $request->colaborador_id;
        $ordem_filtro = $request->ordem_filtro;
        $porpagina = $request->input('porpagina', 15);

        $coluna = $request->query('coluna');
        $ordem = $request->query('ordem');

        $orderDirection = ($ordem == 'asc') ? 'asc' : 'desc';

        if($coluna == 'job'){
            $demandas->orderBy('id', $orderDirection);
        }

        if($coluna == 'titulo'){
            $demandas->orderBy('titulo', $orderDirection);
        }

        if($coluna == 'prioridade'){
            $demandas->orderBy('prioridade', $orderDirection);
        }

        if ($coluna == 'criador') {
            $demandas = $demandas->join('usuarios', 'demandas.criador_id', '=', 'usuarios.id')
                ->orderBy('usuarios.nome', $orderDirection);
        }

        if ($coluna == 'marca') {
            $demandas = $demandas->join('demandas_marcas', 'demandas.id', '=', 'demandas_marcas.demanda_id')
                ->join('marcas', 'demandas_marcas.marca_id', '=', 'marcas.id')
                ->orderBy('marcas.nome', $orderDirection);
        }

        if ($coluna == 'inicial') {
            $demandas = $demandas->orderBy('inicio', $orderDirection);
        }

        if ($coluna == 'entrega') {
            $demandas = $demandas->orderBy('final', $orderDirection);
        }

        if ($coluna == 'status') {
            $demandas->orderBy('status', $orderDirection);
        }

        if($search){
            $demandas->where('titulo', 'like', "%$search%");
        }

        if($jobId){
            $demandas->where('demandas.id', $jobId);
        }

        if ($ordem_filtro) {
            if ($ordem_filtro === 'crescente') {
                $demandas->orderBy('id', 'ASC');
            } elseif ($ordem_filtro === 'decrescente') {
                $demandas->orderBy('id', 'DESC');
            }elseif ($ordem_filtro === 'alfabetica') {
                $demandas->orderBy('titulo', 'ASC');
            }
        }else {
            $demandas->leftJoin('demandas_ordem_jobs', function ($join) use ($user) {
                $join->on('demandas.id', '=', 'demandas_ordem_jobs.demanda_id')
                    ->where('demandas_ordem_jobs.usuario_id', '=', $user->id);
            })
            ->select('demandas.*', 'demandas_ordem_jobs.ordem as ordem')
            ->orderByRaw('ISNULL(demandas_ordem_jobs.ordem) ASC, demandas_ordem_jobs.ordem ASC, demandas.id DESC');
        }

        if($inTime != ''){
            if($inTime == 2){
                $dataAtual = Carbon::now()->toDateString();
                $demandas->whereDate('final', '<', $dataAtual)->where('finalizada', 0);
            }else{
                $demandas->where('atrasada', '=', $inTime)->where('finalizada', 1);
            }
        }

        if($aprovada){
            if($aprovada == 'finalizados'){
                 $demandas->where('finalizada', '1');
            }else if($aprovada == 'em_pauta'){
                $demandas->where('em_pauta', '1')->where('finalizada', '0')->where('entregue', '0')->where('pausado', '0');
            }else if($aprovada == 'pendentes'){
                $demandas->where('em_pauta', '0')->where('finalizada', '0')->where('entregue', '0')->where('pausado', '0');
            }else if($aprovada == 'entregue'){
                $demandas->where('em_pauta', '0')->where('finalizada', '0')->where('entregue', '1')->where('pausado', '0');
            }else if($aprovada == 'recebidos'){
                $demandas->where('em_pauta', '0')->where('finalizada', '0')->where('entregue', '0')->where('recebido', 1)->where('entregue_recebido', 0)->where('pausado', '0');
            }else if($aprovada == 'pausados'){
                $demandas->where('pausado', '1');
            }
        }

        if ($dateRange) {
            [$date, $endDate] = explode(' - ', $dateRange);
            $date = Carbon::createFromFormat('d/m/Y', $date)->format('Y-m-d');
            $endDate = Carbon::createFromFormat('d/m/Y', $endDate)->format('Y-m-d');
            $demandas->where(function($query) use ($date, $endDate) {
            $query->whereDate('inicio', '>=', $date)
                ->whereDate('inicio', '<=', $endDate)
                ->orWhereDate('final', '>=', $date)
                ->whereDate('final', '<=', $endDate);
            });
        }

        if($priority){
            if($priority == 1){
                $status = 'Baixa';
            }else if($priority == 5){
                $status = 'Média';
            }else if($priority == 7){
                $status = 'Alta';
            }else if($priority == 10){
                $status = 'Urgente';
            }
            $demandas->where('prioridade', 'like', "%$status%");
        }

        if($marca != '0' && $marca){
            $demandas->whereHas('marcas', function($query)  use($marca){
                $query->where('marcas.id', $marca);
                $query->where('marcas.excluido', null );
            });
        }

        if($colaborador != '0' && $colaborador){
            $demandas->whereHas('criador', function($query)  use($colaborador){
                $query->where('usuarios.id', $colaborador);
                $query->where('usuarios.excluido', null );
            });
        }

        $demandas->withCount(['questionamentos as count_questionamentos' => function ($query) {
            $query->where('visualizada_ag', 0)->where('excluido', null);
        }])
        ->withCount(['questionamentos as count_respostas' => function ($query) {
            $query->whereHas('respostas', function ($query) {
                $query->where('visualizada_ag', 0);
            });
        }]);

        $perPage = $request->input('porpagina', 15);

        $demandas = $demandas->paginate($perPage)->withQueryString();

        $demandas->getCollection()->transform(function ($demanda) use ($user) {
            $demanda->questionamentos->each(function ($questionamento) use ($user) {
                $questionamento->loadCount(['lidos' => function ($query) use ($user) {
                    $query->where('visualizada', 0)->where('usuario_id', $user->id);
                }]);
                $questionamento->hasComentariosNaoLidos = $questionamento->lidos_count > 0;
                unset($questionamento->lidos_count);
            });
            $demanda->hasComentariosNaoLidos = $demanda->questionamentos->contains('hasComentariosNaoLidos', true);
            return $demanda;
        });

        $demandas->getCollection()->transform(function ($demanda) {
            $allDates = collect();

            $demanda->demandaRecorrencias->each(function ($campanhaRecorrencia) use (&$allDates) {
                if ($campanhaRecorrencia->finalizada == 0) {
                    $recorrenciaDates = $campanhaRecorrencia->recorrencias()
                        ->where('entregue', 0)
                        ->where('finalizado', 0)
                        ->pluck('data');

                    // Coleta as datas de `DemandaRecorrenciaAjuste` onde `entregue` é 0
                    $ajusteDates = $campanhaRecorrencia->recorrencias->flatMap(function ($recorrencia) {
                        return $recorrencia->ajustes()->where('entregue', 0)->pluck('data');
                    });

                    // Mescla todas as datas em uma única coleção
                    $allDates = $allDates->merge($recorrenciaDates)->merge($ajusteDates);
                }
            });

            // Determina a data mais recente
            if ($allDates->isNotEmpty()) {
                $mostRecentDate = $allDates->map(function ($date) {
                    return Carbon::parse($date);
                })->min(); // Usa `min()` para encontrar a data mais antiga (mais recente no seu caso)

                $demanda->mostRecentDate = $mostRecentDate->format('Y-m-d');
            } else {
                $demanda->mostRecentDate = null;
            }

            return $demanda;
        });

        foreach ($demandas as $demanda) {
            $demanda->criador->nome = explode(' ', $demanda->criador->nome)[0];
            if ($demanda->finalizada == 1) {
                $porcentagem = 100;
            } else {
                // Obter o total de prazosDaPauta finalizados da demanda
                $totalFinalizados = $demanda->prazosDaPauta()->whereNotNull('finalizado')->count();

                // Obter o total de prazosDaPauta não finalizados da demanda
                $totalNaoFinalizados = $demanda->prazosDaPauta()->whereNull('finalizado')->count();

                // Calcular a porcentagem com base nos prazosDaPauta finalizados e não finalizados da demanda
                $totalPrazos = $totalFinalizados + $totalNaoFinalizados;
                if ($totalPrazos == 0) {
                    $porcentagem = 0;
                } elseif ($totalFinalizados == 0) {
                    $porcentagem = 10;
                } else {
                    $porcentagem = round(($totalFinalizados / $totalPrazos) * 95);
                }
            }
            // Adicionar a porcentagem como um atributo da demanda
            $demanda->porcentagem = $porcentagem;

            //ajustar final quando estiver reaberta

            $demandasReabertas = $demanda->demandasReabertas;
            if ($demandasReabertas->count() > 0) {
                $sugerido = $demandasReabertas->sortByDesc('id')->first()->sugerido;
                $demanda->final = $sugerido;
            }
        }
        
        $colaboradores = User::select('id', 'nome')->whereIn('tipo', ['colaborador', 'admin'])->get();

        $brands = Marca::where('excluido', null)->get();

        $ordemjob = DemandaOrdem::where('usuario_id', $user->id)->first();
        $arrayOrdem = null;
        $ordemValue = null;

        if($ordemjob){
            $arrayOrdem = explode(",", $ordemjob->ordem);
            $ordemValue = $ordemjob->ordem;
        }else{
            $arrayOrdem = null;
            $ordemValue = null;
        }

        $reset = true;

        if ($request->ajax()) {
            $view = view('ordem-agencia', compact('demandas', 'arrayOrdem', 'ordem', 'reset'))->render();
            return response($view)->header('Content-Type', 'text/html');
        }

        return view('Agencia/jobs', [
            'demandas' => $demandas,
            'search' => $search,
            'inTime' => $inTime,
            'aprovada' => $aprovada,
            'priority' => $priority,
            'brands' => $brands,
            'marca'=> $marca,
            'dateRange' => $dateRange,
            'colaboradorActive' => $colaborador,
            'colaboradores' => $colaboradores,
            'arrayOrdem' => $arrayOrdem,
            'ordemValue' => $ordemValue,
            'ordem_filtro' => $ordem_filtro,
            'ordem' => $ordem,
            'porpagina' => $porpagina,
            'jobId' => $jobId
        ]);

    }

    public function create(){
        $user = Auth::User();
        $dataAtual = Carbon::now();

        $userInfos = User::where('id', $user->id)->where('excluido', null)->with(['marcas' => function ($query) {
        $query->where('excluido', null);
        }])->with('usuariosAgencias')->first();

        $users = Agencia::where('id', $userInfos['usuariosAgencias'][0]->id)
        ->with(['agenciasUsuarios' => function($query) {
            $query->where('excluido', null);
        }])->where('excluido', null)
        ->first();

        return view('Agencia/criar', [
            'userInfos' => $userInfos,
            'dataAtual' => $dataAtual,
            'users' => $users,
        ]);

    }

    public function getUserAgency(Request $request) {
        $user = Auth::user();

        $usuarioAgencia = MarcaColaborador::where('marca_id', $request->id)
        ->join('usuarios', 'marcas_colaboradores.usuario_id', '=', 'usuarios.id')
        ->get();

        $usuarios = $usuarioAgencia->map(function ($usuario) {
            return [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
            ];
        });

        return response()->json($usuarios);
    }


    public function createAction(Request $request){
        $user = Auth::User();

        $validator = Validator::make($request->all(),[
                'titulo' => 'required|min:3',
                'colaborador' => 'required',
                'marca' => 'required',
                'inicio' => 'required',
                'final' => 'required',
                'prioridade' => 'required',
                ],[
                'titulo.required' => 'Preencha o campo título.',
                'titulo.min' => 'O campo título deve ter pelo menos 3 caracteres.',
                'marca.required' => 'Preencha o campo marca.',
                'inicio.required' => 'Preencha o campo data inicial.',
                'final.required' => 'Preencha o campo data de entrega.',
                'prioridade.required' => 'Preencha o campo prioridade.',
            ]
        );

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        if($request->inicio > $request->final){

            return response()->json([
                'success' => false,
                'message' => 'A data final não pode ser anterior à data inicial!'
            ],400);
        }

        if(!$validator->fails()){


            $cor = null;
            if($request->prioridade == 'Baixa'){
                $cor = '#3dbb3d';
            }else if($request->prioridade == 'Média'){
                $cor = '#f9bc0b';
            }else if($request->prioridade == 'Alta'){
                $cor = '#fb3232';
            }else if($request->prioridade == 'Urgente'){
                $cor = '#000';
            }

            // $getAgency = $user->usuariosAgencias()->first();

            $newJob = new Demanda();
            $newJob->titulo = $request->titulo;
            $newJob->criador_id = $request->colaborador;
            $newJob->sub_criador_id  = $user->id;
            $newJob->inicio = $request->inicio;
            $newJob->final = $request->final;
            $newJob->prioridade = $request->prioridade;
            $newJob->cor = $cor;
            $newJob->etapa_1 = 1;
            $newJob->agencia_id = '1';
            $newJob->criado = date('Y-m-d H:i:s');
            $newJob->save();


            $demandaMarcas = new DemandaMarca();
            $demandaMarcas->marca_id = $request->marca;
            $demandaMarcas->demanda_id = $newJob->id;
            $demandaMarcas->save();

            $demandaUsuario = new DemandaUsuario();
            $demandaUsuario->usuario_id = $user->id;
            $demandaUsuario->demanda_id = $newJob->id;
            $demandaUsuario->save();

            $demandaAdminAgencia = new AgenciaDemandaUsuario();
            $demandaAdminAgencia->usuario_id = $user->id;
            $demandaAdminAgencia->demanda_id = $newJob->id;
            $demandaAdminAgencia->save();

            return response()->json([
                'success' => true,
                'message' => 'Etapa 1 criada com sucesso!',
                'redirect' => route('Agencia.criar_etapa_2', ['id' => $newJob->id])
            ], 200);

        }

    }

    public function createStage2($id){
        $user = Auth::User();
        $demanda = Demanda::where('id', $id)->with(['marcas' => function ($query) {
        $query->where('excluido', null);
        }])->with('descricoes')->with('demandasUsuario')->first();

        $userInfos = User::where('id', $user->id)->where('excluido', null)->with(['marcas' => function ($query) {
            $query->where('excluido', null);
        }])->with('usuariosAgencias')->first();

        $colaboradorCriador = MarcaColaborador::where('marca_id', $demanda->marcas[0]->id)
        ->join('usuarios', 'marcas_colaboradores.usuario_id', '=', 'usuarios.id')
        ->get();


        if($demanda){
            if($demanda->etapa_2 == 0){
                return view('Agencia/criar-etapa-2', [
                    'demanda' => $demanda,
                    'userInfos' => $userInfos,
                    'colaboradorCriador' => $colaboradorCriador
                ]);
            }else{
                return redirect('/index');
            }
        }
    }

    public function createActionStage2(Request $request, $id){
        $user = Auth::User();
        $data = $request->all();

        $rules = ValidationUtil::yourValidationRules(true, false);
        $messages = ValidationUtil::yourValidationMessages(true, false);

        $validationResult = ValidationUtil::validateData($data, $rules, $messages);

        if ($validationResult !== null) {
            return $validationResult;
        }

        $demanda = Demanda::where('excluido', null)->find($id);

        if($request->prioridade){

            $cor = null;
            if($request->prioridade == 'Baixa'){
                $cor = '#3dbb3d';
            }else if($request->prioridade == 'Média'){
                $cor = '#f9bc0b';
            }else if($request->prioridade == 'Alta'){
                $cor = '#fb3232';
            }else if($request->prioridade == 'Urgente'){
                $cor = '#000';
            }
            $demanda->prioridade = $request->prioridade;
            $demanda->cor = $cor;
        }

        if($request->titulo){
            $demanda->titulo = $request->titulo;
        }

        if($request->drive){
            $demanda->drive = $request->drive;
        }

        if($request->inicio){
            $demanda->inicio = $request->inicio;
        }

        if($request->final){
            $demanda->final = $request->final;
        }

        if($request->colaborador){
            $demanda->criador_id = $request->colaborador;
            $hasUserColaboradorOrdem = DemandaOrdemJob::where('demanda_id', $demanda->id)
            ->where('usuario_id', $request->colaborador)
            ->exists();
            if(!$hasUserColaboradorOrdem){
                $createOrdem = new DemandaOrdemJob();
                $createOrdem->usuario_id = $request->colaborador;
                $createOrdem->demanda_id = $demanda->id;
                $createOrdem->ordem = 0;
                $createOrdem->save();
            }
        }

        $hasUserAgOrdem = DemandaOrdemJob::where('demanda_id', $demanda->id)
        ->where('usuario_id', $user->id)
        ->exists();
        if(!$hasUserAgOrdem){
            $createOrdem = new DemandaOrdemJob();
            $createOrdem->usuario_id = $user->id;
            $createOrdem->demanda_id = $demanda->id;
            $createOrdem->ordem = 0;
            $createOrdem->save();
        }

        if($request->marca){
            $marcasId = $request->marca;

            DemandaMarca::where('demanda_id', $id)
            ->where('marca_id', '<>', $marcasId)
            ->delete();

            $demandaMarca = DemandaMarca::updateOrCreate([
                'marca_id' => $marcasId,
                'demanda_id' => $demanda->id,
            ], [
                'marca_id' => $marcasId,
                'demanda_id' => $demanda->id,
            ]);

        }

        if ($request->hasFile('arquivos')) {
            $arqs = $request->file('arquivos');

            foreach($arqs as $item){
                $extension = $item->extension();
                $file = $item->getClientOriginalName();
                $fileName = pathinfo($file, PATHINFO_FILENAME);
                $photoName = $fileName . '.' . $extension;
                $destImg = public_path('assets/images/files');
                $i = 1;

                while(file_exists($destImg . '/' . $photoName)){
                    $photoName = $fileName . '_' . $i . '.' . $extension;
                    $i++;
                }

                $item->move($destImg, $photoName);

                $newPostPhoto = new DemandaImagem();
                $newPostPhoto->demanda_id =  $demanda->id;
                $newPostPhoto->imagem = $photoName;
                $newPostPhoto->usuario_id = $user->id;
                $newPostPhoto->criado = date('Y-m-d H:i:s');
                $newPostPhoto->save();
            }
        }

        $demanda->etapa_2 = 1;
        $demanda->status = 'Pendente';
        $demanda->save();

        //demanda descricao
        $demandaDes = new DemandaComplemento();
        $demandaDes->demanda_id = $demanda->id;
        $demandaDes->descricao = $request->briefing;
        $demandaDes->metas_objetivos = $request->objetivos;
        $demandaDes->peças = $request->pecas;
        $demandaDes->formato = $request->formato;
        $demandaDes->formato_texto = $request->formatoInput;
        if($request->dimensoes){
            $demandaDes->dimensoes = $request->dimensoes;
        }

        $demandaDes->save();
        $newTimeLine = new LinhaTempo();
        $newTimeLine->demanda_id = $demanda->id;
        $newTimeLine->status = 'Job cadastrado';
        $newTimeLine->code = 'criado';
        $newTimeLine->usuario_id = $user->id;
        $newTimeLine->criado = date('Y-m-d H:i:s');
        $newTimeLine->save();


        //notificar criador

        $criadorNotificacao = new Notificacao();
        $criadorNotificacao->demanda_id = $demanda->id;
        $criadorNotificacao->usuario_id = $demanda->criador_id;
        $criadorNotificacao->conteudo = $user->nome .' criou um job em que você é o colaborador.';
        $criadorNotificacao->criado = date('Y-m-d H:i:s');
        $criadorNotificacao->visualizada = '0';
        $criadorNotificacao->tipo = 'criada';
        $criadorNotificacao->save();

        //notificar usuario

        $usuarioNotificacao = new Notificacao();
        $usuarioNotificacao->demanda_id = $demanda->id;
        $usuarioNotificacao->visualizada = '0';
        $usuarioNotificacao->tipo = 'criada';
        $usuarioNotificacao->usuario_id = $user->id;
        $usuarioNotificacao->criado = date('Y-m-d H:i:s');
        $usuarioNotificacao->conteudo = 'Você criou um novo job.';

        $usuarioNotificacao->save();

        $userAdmin = User::select('id', 'nome')->where('tipo', 'admin')->whereHas('marcasColaborador', function ($query) use ($request) {
            $query->where('marcas.excluido', null)
                ->where('marcas.id', $request->marca);
        })->get();
        
        foreach ($userAdmin as $ad) {
            $hasUserOrdem = DemandaOrdemJob::where('demanda_id', $demanda->id)
            ->where('usuario_id', $ad->id)
            ->exists();
            if (!$hasUserOrdem) {
                $createOrdem = new DemandaOrdemJob();
                $createOrdem->usuario_id = $ad->id;
                $createOrdem->demanda_id = $demanda->id;
                $createOrdem->ordem = 0;
                $createOrdem->save();
            }
        }


        //send e-mail

        $actionLink = route('Job', ['id' => $demanda->id]);
        $bodyEmail = 'Seu novo job foi criado com sucesso. Acesse pelo link logo abaixo.';
        $titleEmail = 'Novo job criado';

        //criador

        $userCriador = User::where('id', $demanda->criador_id)->first();

        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $userCriador->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $userCriador) {
            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
            ->to($userCriador->email)
            ->subject('Novo job criado');
        });

        //agencia

        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $user->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $user) {
            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
            ->to($user->email)
            ->subject('Novo job criado');
        });

        return response()->json([
            'success' => true,
            'message' => 'Job criado com sucesso!',
            'redirect' => route('Agencia.Jobs')
        ], 200);

    }

    public function edit($id){
        $user = Auth::User();
        $demanda = Demanda::where('id', $id)->with(['marcas' => function ($query) {
        $query->where('excluido', null);
        }])->with('descricoes')->with('demandasUsuario')->with('criador')->with('demandaColaboradores')->with('marcasDemandas')->first();

        $colaboradores = MarcaColaborador::where('marca_id', $demanda->marcasDemandas[0]->id)
        ->join('usuarios', 'marcas_colaboradores.usuario_id', '=', 'usuarios.id')
        ->get();

        if($demanda){
            return view('Agencia/editar', [
                'demanda' => $demanda,
                'colaboradores' => $colaboradores
            ]);
        }

        return redirect('/index');

    }

    public function editAction(Request $request, $id){
        $user = Auth::User();

        $data = $request->all();

        $rules = ValidationUtil::yourValidationRules(true, false);
        $messages = ValidationUtil::yourValidationMessages(true, false);

        $validationResult = ValidationUtil::validateData($data, $rules, $messages);

        if ($validationResult !== null) {
            return $validationResult;
        }

        $demanda = Demanda::where('id', $id)->where('excluido', null)->first();

        if($request->titulo){
            $demanda->titulo = $request->titulo;
        }

        if($request->drive){
            $demanda->drive = $request->drive;
        }

        if($request->prioridade){

            $cor = null;
            if($request->prioridade == 'Baixa'){
                $cor = '#3dbb3d';
            }else if($request->prioridade == 'Média'){
                $cor = '#f9bc0b';
            }else if($request->prioridade == 'Alta'){
                $cor = '#fb3232';
            }else if($request->prioridade == 'Urgente'){
                $cor = '#000';
            }
            $demanda->prioridade = $request->prioridade;
            $demanda->cor = $cor;
        }

        if($request->inicio){
            $demanda->inicio = $request->inicio;
        }

        if($request->final){
            $demanda->final = $request->final;
        }

        if ($request->hasFile('arquivos')) {
            $arqs = $request->file('arquivos');
            foreach($arqs as $item){
                $extension = $item->extension();
                $file = $item->getClientOriginalName();
                $fileName = pathinfo($file, PATHINFO_FILENAME);
                $destImg = public_path('assets/images/files');
                $photoName =  '';
                $i = 1;

                while(file_exists($destImg . '/' . $photoName)){
                    $photoName = $fileName . '_' . $i . '.' . $extension;
                    $i++;
                }
                $item->move($destImg, $photoName);

                $newPostPhoto = new DemandaImagem();
                $newPostPhoto->demanda_id =  $demanda->id;
                $newPostPhoto->usuario_id =  $user->id;
                $newPostPhoto->imagem = $photoName;
                $newPostPhoto->criado = date('Y-m-d H:i:s');
                $newPostPhoto->save();
            }
        }

        if($demanda->criador_id != $request->colaborador){

            Notificacao::where('demanda_id', $id)->where('tipo', 'criada-coloborador')->where('usuario_id', $demanda->criador_id )->delete();

            DemandaColaborador::where('demanda_id', $id)->where('usuario_id', $request->colaborador)->delete();
            $demanda->criador_id = $request->colaborador;
            OrdemJob::OrdemJobHelper($request->colaborador, $demanda->id);
            
            $demanda->criador_id = $request->colaborador;

            $colaboradorNotificacao = new Notificacao();
            $colaboradorNotificacao->demanda_id = $demanda->id;
            $colaboradorNotificacao->conteudo = $user->nome.' selecionou você para ser colaborador em um novo job.';
            $colaboradorNotificacao->visualizada = '0';
            $colaboradorNotificacao->tipo = 'criada-coloborador';
            $colaboradorNotificacao->usuario_id = $request->colaborador;
            $colaboradorNotificacao->criado = date('Y-m-d H:i:s');
            $colaboradorNotificacao->save();

        }

        $demandaDes = DemandaComplemento::where('demanda_id', $id)->first();

        if($request->objetivos){
            $demandaDes->metas_objetivos = $request->objetivos;
        }

        if($request->pecas){
            $demandaDes->peças = $request->pecas;
        }

        if($request->formato){
            $demandaDes->formato = $request->formato;
        }

        if($request->formatoInput){
            $demandaDes->formato_texto = $request->formatoInput;
        }

        if($request->dimensoes){
            $demandaDes->dimensoes = $request->dimensoes;
        }

        if($request->briefing){
            $demandaDes->descricao = $request->briefing;
        }

        $demandaDes->save();
        $demanda->save();

        return response()->json([
            'success' => true,
            'message' => 'Job editado.'
        ], 200);

    }

    public function copy($id){
        $user = Auth::User();
        $users = null;
        $demanda = Demanda::where('id', $id)->with(['marcas' => function ($query) {
        $query->where('excluido', null);
        }])->with('descricoes')->with('demandasUsuario')->first();

        $demanda->titulo = str_replace($id, '', $demanda->titulo);
        $marcas = User::where('id', $demanda->criador_id)->with('marcas:id,nome,cor')->get()->pluck('marcas')->flatten();

        $getAgId = $user->usuariosAgencias()->first();
        $users = Agencia::where('id', $getAgId->id)
        ->with(['agenciasUsuarios' => function($query) {
            $query->where('excluido', null);
        }])->where('excluido', null)
        ->first();

        $marcasIds = array();
        $usersIds = array();

        foreach($demanda['marcas'] as $marca){
            array_push($marcasIds, $marca->id);
        }

        foreach($demanda['demandasUsuario'] as $user){
            array_push($usersIds, $user->id);
        }

        $colaboradores = User::with('colaboradoresAgencias')
        ->whereHas('colaboradoresAgencias', function ($query) use ($user) {
            $query->where('agencia_id', $user->usuariosAgencias()->first()->id);
        })
        ->whereIn('tipo', ['colaborador', 'admin'])->with('marcas')
        ->get();

        foreach ($colaboradores as $colaborador) {
            $colaborador->unsetRelation('colaboradoresAgencias');
        }

        if($demanda){
            return view('Agencia/copiar', [
                'demanda' => $demanda,
                'marcas' => $marcas,
                'marcasIds' => $marcasIds,
                'usersIds' => $usersIds,
                'users' => $users,
                'colaboradores' => $colaboradores
            ]);
        }
        return redirect('/index');

    }

    public function copyAction(Request $request){
        $user = Auth::User();
        $getAgency = $user->usuariosAgencias()->first();

        $data = $request->all();

        $rules = ValidationUtil::yourValidationRules(true, false);
        $messages = ValidationUtil::yourValidationMessages(true, false);

        $validationResult = ValidationUtil::validateData($data, $rules, $messages);

        if ($validationResult !== null) {
            return $validationResult;
        }

        $cor = null;
        if($request->prioridade == 'Baixa'){
            $cor = '#3dbb3d';
        }else if($request->prioridade == 'Média'){
            $cor = '#f9bc0b';
        }else if($request->prioridade == 'Alta'){
            $cor = '#fb3232';
        }else if($request->prioridade == 'Urgente'){
            $cor = '#000';
        }

        $newJob = new Demanda();
        $newJob->titulo = $request->titulo;

        if($request->drive){
            $newJob->drive = $request->drive;
        }

        $newJob->criador_id = $request->colaborador;
        $newJob->agencia_id = $getAgency->id;
        $newJob->inicio = $request->inicio;
        $newJob->final = $request->final;
        $newJob->prioridade = $request->prioridade;
        $newJob->cor = $cor;
        $newJob->etapa_1 = 1;
        $newJob->etapa_2 = 1;
        $newJob->stauts = 'Pendente';
        $newJob->cor = $cor;
        $newJob->criado = date('Y-m-d H:i:s');
        $newJob->save();

        // if($request->users){
        //     $usersIds = $request->users;
        //     DemandaUsuario::where('demanda_id', $newJob->id)->whereNotIn('usuario_id', $usersIds)->delete();

        //     foreach($usersIds as $item){
        //         $demandaUsuario = DemandaUsuario::updateOrCreate([
        //             'usuario_id' => $item,
        //             'demanda_id' => $newJob->id,
        //         ], [
        //             'usuario_id' => $item,
        //             'demanda_id' => $newJob->id,
        //         ]);

        //     }
        // }

        foreach($request->users as $item){
            $demandaMarcas = new DemandaUsuario();
            $demandaMarcas->usuario_id = $item;
            $demandaMarcas->demanda_id = $newJob->id;
            $demandaMarcas->save();

            $createOrdem = new DemandaOrdemJob();
            $createOrdem->usuario_id = $item;
            $createOrdem->demanda_id = $newJob->id;
            $createOrdem->ordem = 0;
            $createOrdem->save();
        }

        if($request->marcas){
            $marcasIds = $request->marcas;
            DemandaMarca::where('demanda_id', $newJob->id)->whereNotIn('marca_id', $marcasIds)->delete();

            foreach($marcasIds as $item){
                $demandaMarca = DemandaMarca::updateOrCreate([
                    'marca_id' => $item,
                    'demanda_id' => $newJob->id,
                ], [
                    'marca_id' => $item,
                    'demanda_id' => $newJob->id,
                ]);

            }
        }

        if ($request->hasFile('arquivos')) {
            $arqs = $request->file('arquivos');

            foreach($arqs as $item){
                $extension = $item->extension();
                $file = $item->getClientOriginalName();
                $fileName = pathinfo($file, PATHINFO_FILENAME);
                $photoName = $fileName . '.' . $extension;
                $destImg = public_path('assets/images/files');
                $i = 1;

                while(file_exists($destImg . '/' . $photoName)){
                    $photoName = $fileName . '_' . $i . '.' . $extension;
                    $i++;
                }

                $item->move($destImg, $photoName);

                $newPostPhoto = new DemandaImagem();
                $newPostPhoto->demanda_id =  $newJob->id;
                $newPostPhoto->imagem = $photoName;
                $newPostPhoto->usuario_id = $user->id;
                $newPostPhoto->criado = date('Y-m-d H:i:s');
                $newPostPhoto->save();
            }
        }

        $newTimeLine = new LinhaTempo();
        $newTimeLine->demanda_id = $newJob->id;
        $newTimeLine->status = 'Job cadastrado';
        $newTimeLine->code = 'criado';
        $newTimeLine->usuario_id = $user->id;
        $newTimeLine->criado = date('Y-m-d H:i:s');
        $newTimeLine->save();

        //demanda descricao

        $demandaDes = new DemandaComplemento();
        $demandaDes->demanda_id = $newJob->id;
        $demandaDes->metas_objetivos = $request->objetivos;
        $demandaDes->peças = $request->pecas;
        $demandaDes->formato = $request->formato;
        $demandaDes->formato_texto = $request->formatoInput;
        $demandaDes->descricao = $request->briefing;

        if($request->dimensoes){
            $demandaDes->dimensoes = $request->dimensoes;
        }

        $demandaDes->save();


        //notificar criador

        $criadorNotificacao = new Notificacao();
        $criadorNotificacao->demanda_id = $newJob->id;
        $criadorNotificacao->usuario_id = $newJob->criador_id;
        $criadorNotificacao->conteudo = $user->nome .' criou um job em que você é o colaborador.';
        $criadorNotificacao->criado = date('Y-m-d H:i:s');
        $criadorNotificacao->visualizada = '0';
        $criadorNotificacao->tipo = 'criada';
        $criadorNotificacao->save();

        $createOrdem = new DemandaOrdemJob();
        $createOrdem->usuario_id = $user->id;
        $createOrdem->demanda_id = $newJob->id;
        $createOrdem->ordem = 0;
        $createOrdem->save();

        $userAdmin = User::select('id', 'nome')->where('tipo', 'admin')->whereHas('marcasColaborador', function ($query) use ($request) {
            $query->where('marcas.excluido', null)
                ->where('marcas.id', $request->marca);
        })->get();
        
        foreach ($userAdmin as $ad) {
            $hasUserOrdem = DemandaOrdemJob::where('demanda_id', $newJob->id)
            ->where('usuario_id', $ad->id)
            ->exists();
            if (!$hasUserOrdem) {
                $createOrdem = new DemandaOrdemJob();
                $createOrdem->usuario_id = $ad->id;
                $createOrdem->demanda_id = $newJob->id;
                $createOrdem->ordem = 0;
                $createOrdem->save();
            }
        }

        //send e-mail

        $actionLink = route('Job', ['id' => $newJob->id]);
        $bodyEmail = 'Seu novo job foi criado com sucesso. Acesse pelo link logo abaixo.';
        $titleEmail = 'Novo job criado';

        //notificar usuario

        foreach($request->users as $item){
            $usuarioNotificacao = new Notificacao();
            $usuarioNotificacao->demanda_id = $newJob->id;
            $usuarioNotificacao->visualizada = '0';
            $usuarioNotificacao->tipo = 'criada';
            $usuarioNotificacao->usuario_id = $item;
            $usuarioNotificacao->criado = date('Y-m-d H:i:s');

            if($user->id != $item){
                $usuarioNotificacao->conteudo = $user->nome . ' criou um novo job.';
            }else{
                $usuarioNotificacao->conteudo = 'Você criou um novo job.';
            }

            $usuarioNotificacao->save();
        }

        $demandaAdminAgencia = new AgenciaDemandaUsuario();
        $demandaAdminAgencia->usuario_id = $user->id;
        $demandaAdminAgencia->demanda_id = $newJob->id;
        $demandaAdminAgencia->save();

        //criador email

        // Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $user->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $user) {
        //     $message->from('envios@fmfm.com.br')
        //     ->to($user->email)
        //     ->bcc('agenciacriareof@gmail.com')
        //     ->subject('Novo job criado');
        // });

        //agencia usuario email

        return response()->json([
            'success' => true,
            'message' => 'Job copiado.'
        ], 200);

    }

    // public function reOpenJob(Request $request, $id){
    //     $user = Auth::User();
    //     $newTimeLine = new LinhaTempo();
    //     $newTimeLine->demanda_id = $id;
    //     $newTimeLine->usuario_id = $user->id;
    //     $titleEmail = '';
    //     $newTimeLine->criado = date('Y-m-d H:i:s');


    //     $hasReopenCount = LinhaTempo::where('demanda_id', $id)->where('code', 'reaberto')->count();

    //     if($hasReopenCount == 0){
    //         $newTimeLine->status = "Reaberto 1";
    //         $newTimeLine->code = "reaberto";
    //         $newTimeLine->save();
    //         $titleEmail = "Reaberto 1";

    //     }else{
    //         $newTimeLine->status = "Reaberto ".($hasReopenCount + 1);
    //         $newTimeLine->code = "reaberto";
    //         $newTimeLine->save();
    //         $titleEmail = "Reaberto ".($hasReopenCount + 1);
    //     }
    //     //change select

    //     $demanda = Demanda::where('id', $id)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->with('demandasUsuario')->first();

    //     $demandaReaberta = new DemandaReaberta();
    //     $demandaReaberta->demanda_id = $id;
    //     $demandaReaberta->iniciado = date('Y-m-d H:i:s');
    //     $demandaReaberta->status =  $newTimeLine->status;
    //     $demandaReaberta->sugerido = $request->sugerido_reaberto;
    //     $demandaReaberta->save();

    //     $demanda->em_pauta = 0;
    //     $demanda->finalizada = 0;
    //     $demanda->entregue = 0;
    //     $demanda->recebido = 0;
    //     $demanda->em_alteracao = 0;
    //     $demanda->entregue_recebido = 0;
    //     $demanda->save();

    //     $actionLink = route('Job', ['id' => $id]);
    //     $bodyEmail = 'O job '.$id . ' foi reaberto.'. '<br/>'.  'Acesse pelo link logo abaixo.';

    //     $agencies = Agencia::where('id', $demanda->agencia_id)->with(['agenciasUsuarios' => function ($query) {
    //         $query->where('excluido', null);
    //         $query->select('email', 'nome');
    //     }])->first();

    //     $usuarioNotificacao = new Notificacao();
    //     $usuarioNotificacao->demanda_id = $demanda->id;
    //     $usuarioNotificacao->conteudo = 'Job reaberto.';
    //     $usuarioNotificacao->visualizada = '0';
    //     $usuarioNotificacao->tipo = 'criada';

    //     foreach($demanda['demandasUsuario'] as $item){
    //         $usuarioNotificacao->usuario_id = $item->id;
    //         $usuarioNotificacao->criado = date('Y-m-d H:i:s');
    //         $usuarioNotificacao->save();
    //     }

    //     //mails
    //     // foreach($agencies['agenciasUsuarios'] as $item){
    //     //     Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($item, $titleEmail, $id) {
    //     //         $message->from('envios@fmfm.com.br')
    //     //         ->to($item->email)
    //     //         ->bcc('agenciacriareof@gmail.com')
    //     //         ->subject('O job '. $id . ' foi o reaberto');

    //     //         // $message->from('agenciacriareof@gmail.com');
    //     //         // $message->to($item->email)->subject('O job '. $id . ' alterou o status para: ' . $titleEmail);
    //     //     });
    //     // }

    //     return back()->with('success', 'Job reaberto' );
    // }

    // public function finalize($id){
    //     $user = Auth::User();
    //     $titleEmail = '';

    //     $hasFinalizeCount = LinhaTempo::where('demanda_id', $id)->where('code', 'finalizado')->count();
    //     $demanda = Demanda::where('id', $id)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->with('demandasUsuario')->first();

    //     $lineTime = new LinhaTempo();
    //     $lineTime->demanda_id = $id;
    //     $lineTime->usuario_id = $user->id;
    //     $lineTime->criado = date('Y-m-d H:i:s');

    //     if($hasFinalizeCount == 0){
    //         $lineTime->status = 'Finalizado 1';
    //         $lineTime->code = 'finalizado';
    //         $lineTime->save();
    //         $titleEmail = 'Finalizado 1';
    //     }else{
    //         $lineTime->status = 'Finalizado '.($hasFinalizeCount + 1);
    //         $lineTime->code = 'finalizado';
    //         $lineTime->save();
    //         $titleEmail = 'Finalizado '.($hasFinalizeCount + 1);
    //     }

    //     $actionLink = route('Job', ['id' => $id]);
    //     $bodyEmail = 'O job '.$id . ' foi finalizado com sucesso.'. '<br/>'.  'Acesse pelo link logo abaixo.';

    //     $agencies = Agencia::where('id', $demanda->agencia_id)->with(['agenciasUsuarios' => function ($query) {
    //         $query->where('excluido', null);
    //         $query->select('email', 'nome');
    //     }])->first();

    //     //demandas reabertas
    //     $demandasReabertas = DemandaReaberta::where('demanda_id', $id)->get();

    //     foreach($demandasReabertas as $item){
    //         if($item->finalizado == null){
    //             $item->finalizado = date('Y-m-d H:i:s');
    //             $item->save();
    //         }
    //     }

    //     // // criar a data atual
    //     // $dataAtual = Carbon::now();

    //     // // converter para o fuso horário da América/São_Paulo
    //     // $dataAtual->setTimezone('America/Sao_Paulo');

    //     // // criar a data final
    //     // $dataFinal = Carbon::createFromFormat('Y-m-d H:i:s', $demanda->final);

    //     // // verificar se este trabalho foi reaberto
    //     // $verifyReOpenJob = DemandaReaberta::where('demanda_id', $id)->orderBy('id', 'DESC')->first();

    //     // if ($verifyReOpenJob) {
    //     //     // converter a data final para o fuso horário da América/São_Paulo
    //     //     $dataFinal = Carbon::createFromFormat('Y-m-d H:i:s', $verifyReOpenJob->sugerido);
    //     //     $dataFinal->setTimezone('America/Sao_Paulo');
    //     // }

    //     // // comparar as datas
    //     // if ($dataAtual->greaterThan($dataFinal)) {
    //     //     $demanda->atrasada = 1;
    //     // } else {
    //     //     $demanda->atrasada = 0;
    //     // }

    //     $demanda->finalizada = 1;
    //     $demanda->entregue = 0;
    //     $demanda->em_pauta = 0;
    //     $demanda->save();

    //     //notificar
    //     $usuarioNotificacao = new Notificacao();
    //     $usuarioNotificacao->demanda_id = $demanda->id;
    //     $usuarioNotificacao->conteudo = 'Job finalizado.';
    //     $usuarioNotificacao->visualizada = '0';
    //     $usuarioNotificacao->tipo = 'criada';

    //     foreach($demanda['demandasUsuario'] as $item){
    //         $usuarioNotificacao->usuario_id = $item->id;
    //         $usuarioNotificacao->criado = date('Y-m-d H:i:s');
    //         $usuarioNotificacao->save();
    //     }

    //    //mails

    //     return back()->with('success', 'Job finalizado com sucesso.' );


    // }

    // public function pause($id){
    //     $user = Auth::User();
    //     $titleEmail = 'Congelado';

    //     $demanda = Demanda::where('id', $id)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->with('demandasUsuario')->first();

    //     $actionLink = route('Job', ['id' => $id]);
    //     $bodyEmail = 'O job '.$id . ' foi congelado.'. '<br/>'.  'Acesse pelo link logo abaixo.';

    //     $agencies = Agencia::where('id', $demanda->agencia_id)->with(['agenciasUsuarios' => function ($query) {
    //         $query->where('excluido', null);
    //         $query->select('email', 'nome');
    //     }])->first();

    //     $usuarioNotificacao = new Notificacao();
    //     $usuarioNotificacao->demanda_id = $demanda->id;
    //     $usuarioNotificacao->conteudo = 'Job congelado.';
    //     $usuarioNotificacao->visualizada = '0';
    //     $usuarioNotificacao->tipo = 'criada';

    //     foreach($demanda['demandasUsuario'] as $item){
    //         $usuarioNotificacao->usuario_id = $item->id;
    //         $usuarioNotificacao->criado = date('Y-m-d H:i:s');
    //         $usuarioNotificacao->save();
    //     }

    //     //mails
    //     // foreach($agencies['agenciasUsuarios'] as $item){
    //     //     Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($item, $titleEmail, $id) {
    //     //         $message->from('envios@fmfm.com.br')
    //     //         ->to($item->email)
    //     //         ->bcc('agenciacriareof@gmail.com')
    //     //         ->subject('O job '. $id . ' alterou o status para: ' . $titleEmail);

    //     //         // $message->from('agenciacriareof@gmail.com');
    //     //         // $message->to($item->email)->subject('O job '. $id . ' alterou o status para: ' . $titleEmail);
    //     //     });
    //     // }


    //     $demanda->pausado = 1;
    //     $demanda->save();

    //     return back()->with('success', 'Job pausado com sucesso.' );

    // }

    // public function resume(Request $request, $id){
    //     $user = Auth::User();
    //     $titleEmail = 'Retomado';

    //     $demanda = Demanda::where('id', $id)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->with('demandasUsuario')->first();

    //     $actionLink = route('Job', ['id' => $id]);
    //     $bodyEmail = 'O job '.$id . ' foi retomado com sucesso.'. '<br/>'.  'Acesse pelo link logo abaixo.';

    //     $agencies = Agencia::where('id', $demanda->agencia_id)->with(['agenciasUsuarios' => function ($query) {
    //         $query->where('excluido', null);
    //         $query->select('email', 'nome');
    //     }])->first();


    //     $demandasReabertasCount = DemandaReaberta::where('demanda_id', $id)->count();
    //     $demandasReabertas = DemandaReaberta::where('demanda_id', $id)->orderBy('id', 'desc')->first();

    //     if($demandasReabertasCount == 0){
    //         if($request->newFinalDate != null){
    //             $demanda->final = $request->newFinalDate;
    //         }
    //     }else{
    //         if($request->newFinalDate != null){
    //             $demandasReabertas->sugerido = $request->newFinalDate;
    //         }
    //     }

    //     //pautas com nova datas que n foram concluidas

    //     $demandaPautas = DemandaTempo::where('demanda_id', $id)->where('finalizado', 0)->get();
    //     foreach($demandaPautas as $item){
    //         $item->aceitar_agencia = 0;
    //         $item->aceitar_colaborado = 0;
    //         $item->save();
    //     }

    //     $usuarioNotificacao = new Notificacao();
    //     $usuarioNotificacao->demanda_id = $demanda->id;
    //     $usuarioNotificacao->conteudo = 'Job retomado.';
    //     $usuarioNotificacao->visualizada = '0';
    //     $usuarioNotificacao->tipo = 'criada';

    //     foreach($demanda['demandasUsuario'] as $item){
    //         $usuarioNotificacao->usuario_id = $item->id;
    //         $usuarioNotificacao->criado = date('Y-m-d H:i:s');
    //         $usuarioNotificacao->save();
    //     }

    //     //mails
    //     // foreach($agencies['agenciasUsuarios'] as $item){
    //     //     Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($item, $titleEmail, $id) {
    //     //         $message->from('envios@fmfm.com.br')
    //     //         ->to($item->email)
    //     //         ->bcc('agenciacriareof@gmail.com')
    //     //         ->subject('O job '. $id . ' alterou o status para: ' . $titleEmail);

    //     //         // $message->from('agenciacriareof@gmail.com');
    //     //         // $message->to($item->email)->subject('O job '. $id . ' alterou o status para: ' . $titleEmail);
    //     //     });
    //     // }

    //     $demanda->pausado = 0;
    //     $demanda->save();

    //     return back()->with('success', 'Job retomado com sucesso.' );

    // }

    public function delete($id){

        $demanda = Demanda::where('id', $id)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->first();
        if($demanda){
            $demanda->excluido = date('Y-m-d H:i:s');
            $demanda->save();
            $deleteNotifications = Notificacao::where('demanda_id', $id)->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job excluído com sucesso.'
            ], 200);

        }else{
            return response()->json([
                'success' => false,
                'message' => 'Job não pode ser excluído.',
            ], 400);
        }

    }


    // public function acceptTime(Request $request, $id){
    //     $user = Auth::User();
    //     $demandaPrazo = DemandaTempo::find($id);
    //     $demanda = Demanda::select('id', 'final')->where('id', $demandaPrazo->demanda_id)->with('demandasUsuario')->first();
    //     $countDemandasReabertas = DemandaReaberta::where('demanda_id', $demanda->id)->count();
    //     $demandasReaberta = DemandaReaberta::where('demanda_id', $demanda->id)->orderByDesc('id')->first();
    //     //prazo for maior que prazo final
    //     if($demanda){
    //         if($countDemandasReabertas == 0){
    //             if (strtotime($demandaPrazo->sugerido) > strtotime($demanda->final)) {
    //                 // $request->sugeridoComment é maior que $demanda->final
    //                 $demanda->final = $demandaPrazo->sugerido;
    //                 $demanda->save();
    //             }
    //         }else{
    //             if (strtotime($demandaPrazo->sugerido) > strtotime($demandasReaberta->sugerido)) {
    //                 // $request->sugeridoComment é maior que $demanda->final
    //                 $demandasReaberta->sugerido = $demandaPrazo->sugerido;
    //                 $demandasReaberta->save();
    //             }
    //         }
    //     }

    //     //pegar numero
    //     if (preg_match('/\d+/', $demandaPrazo->status, $matches)) {
    //         $lastNumber = $matches[0];
    //     }

    //     $demandaPrazo->aceitar_colaborador = 1;
    //     $demandaPrazo->save();

    //     $notificacao = new Notificacao();
    //     $notificacao->demanda_id = $demandaPrazo->demanda_id;
    //     $notificacao->visualizada = '0';
    //     if($demandaPrazo->code_tempo == 'em-pauta'){
    //         $notificacao->conteudo = $user->nome . ' aceitou o seu prazo da ' . strtolower($demandaPrazo->status) .'.';
    //     }else if($demandaPrazo->code_tempo == 'alteracao'){
    //         $notificacao->conteudo = $user->nome . ' aceitou o seu prazo da alteração  ' . $lastNumber .'.';
    //     }
    //     $notificacao->tipo = 'criada';

    //     foreach($demanda['demandasUsuario'] as $item){
    //         $notificacao->usuario_id = $item->id;
    //         $notificacao->criado = date('Y-m-d H:i:s');
    //         $notificacao->save();
    //     }

    //     return back()->with('success', 'Prazo aceito.');
    // }

    // public function receiveAlteration(Request $request, $id){
    //     $user = Auth::User();
    //     $demanda = Demanda::where('id', $id)->where('etapa_1', 1)->where('etapa_2', 1)->with('demandasUsuario')->where('excluido', null)->first();
    //     $demandaTempoCount = DemandaTempo::where('demanda_id', $demanda->id)->where('code_tempo', 'alteracao')->count();
    //     if($demanda){
    //         $demanda->entregue_recebido = 1;

    //         $newTimeLine = new LinhaTempo();
    //         $newTimeLine->demanda_id = $id;
    //         $newTimeLine->usuario_id = $user->id;
    //         $newTimeLine->code = 'recebida-alteracao';
    //         if($demandaTempoCount > 0){
    //             $newTimeLine->status = "Alteração recebida";
    //         }else{
    //             $newTimeLine->status = "Pauta recebida";
    //         }
    //         $newTimeLine->criado = date('Y-m-d H:i:s');

    //         $newTimeLine->save();
    //         $demanda->save();


    //         $notificacao = new Notificacao();
    //         $notificacao->demanda_id = $demanda->id;
    //         $notificacao->visualizada = '0';
    //         $notificacao->conteudo = $user->nome . ' recebeu suas pautas.';
    //         $notificacao->tipo = 'criada';

    //         foreach($demanda['demandasUsuario'] as $item){
    //             $notificacao->usuario_id = $item->id;
    //             $notificacao->criado = date('Y-m-d H:i:s');
    //             $notificacao->save();
    //         }

    //         return back()->with('success', 'Alteração recebida.');

    //     }
    // }

    public function stages(){
        $user = Auth::User();
        $demandas = Demanda::where('etapa_1', 1)->where('etapa_2', 0)->where('excluido', null)->with(['agencia' => function ($query) {
            $query->where('excluido', null);
        }])->with('demandasUsuarioAdmin')->get();

        if($demandas){
            return view('Agencia/etapas', [
                'demandas' => $demandas,
           ]);
        }

        return redirect('/index');

    }

    public function deleteStage1($id){
        $demanda = Demanda::where('id', $id)->first();
        if($demanda){
            $demanda->delete();
            return response()->json([
                'success' => true,
                'message' => 'Job removido.',
            ], 400);
        }

        return response()->json([
            'success' => false,
            'message' => 'Esse job não pode ser removido.',
        ], 400);

    }

    public function getBrandsColaborador($id){
        if ($id) {
            $marcas = User::orderBy("nome", "asc")
                ->select('id', 'nome')
                ->where('id', $id)
                ->with('marcas:id,nome,cor')
                ->first()
                ->marcas;
            return response()->json($marcas);
        } else {
            return false;
        }
    }

}
