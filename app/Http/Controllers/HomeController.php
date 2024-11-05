<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Demanda;
use App\Models\Marca;
use App\Models\Notificacao;
use App\Models\Comentario;
use App\Models\DemandaUsuario;
use App\Models\Agencia;
use App\Models\DemandaOrdem;
use App\Models\Questionamento;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;

class HomeController extends Controller
{
    private function performAjaxRequest(Request $request) {
        $user = Auth::User();

        $coluna = $request->query('coluna');
        $ordem = $request->query('ordem');

        $demandas = Demanda::where('demandas.excluido', null)
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->where('finalizada', 0)
        ->where(function ($query) use ($user) {
            $query->whereHas('demandasUsuario', function ($query) use ($user) {
                $query->where('usuario_id', $user->id);
            });
        })
        ->with(['marcas' => function ($query) {
            $query->where('excluido', null);
        }])
        ->with(['agencia' => function ($query) {
            $query->where('excluido', null);
        }])
        ->with(['demandasReabertas' => function ($query) {
            $query->where('finalizado', null);
        }])->with('criador')->with('subCriador');

        if($coluna == '' && $ordem == ''){
            $demandas->leftJoin('demandas_ordem_jobs', function ($join) use ($user) {
                $join->on('demandas.id', '=', 'demandas_ordem_jobs.demanda_id')
                    ->where('demandas_ordem_jobs.usuario_id', '=', $user->id);
            })
            ->select('demandas.*', 'demandas_ordem_jobs.ordem as ordem')
            ->orderByRaw('ISNULL(demandas_ordem_jobs.ordem) ASC, demandas_ordem_jobs.ordem ASC, demandas.id DESC');
        }

        $demandas->withCount(['questionamentos as count_questionamentos' => function ($query) use($user) {
            $query->where('visualizada_ag', 0)->where('excluido', null)->where('marcado_usuario_id', $user->id);
        }])
        ->withCount(['questionamentos as count_respostas' => function ($query)  use($user) {
            $query->whereHas('respostas', function ($query) use($user)  {
                $query->where('visualizada_ag', 0)->where('marcado_usuario_id', $user->id);
            });
        }]);

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
            // $demandas = $demandas->join('usuarios', 'demandas.criador_id', '=', 'usuarios.id')
            //     ->orderBy('usuarios.nome', $orderDirection);

            $demandas = $demandas->join('usuarios', function ($join) {
                $join->on('usuarios.id', '=', \DB::raw("CASE WHEN demandas.sub_criador_id IS NOT NULL THEN demandas.sub_criador_id ELSE demandas.criador_id END"));
            })->orderBy('usuarios.nome', $orderDirection);

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

        //data mais recente

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
                })->min(); 

                $demanda->mostRecentDate = $mostRecentDate->format('Y-m-d');
            } else {
                $demanda->mostRecentDate = null;
            }

            return $demanda;
        });

        foreach($demandas as $key => $item){
            $item->criador->nome = explode(' ', $item->criador->nome)[0];
            if ($item->finalizada == 1) {
                $porcentagem = 100;
            } else {
                // Obter o total de prazosDaPauta finalizados da demanda
                $totalFinalizados = $item->prazosDaPauta()->whereNotNull('finalizado')->count();

                // Obter o total de prazosDaPauta não finalizados da demanda
                $totalNaoFinalizados = $item->prazosDaPauta()->whereNull('finalizado')->count();

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
            $item->porcentagem = $porcentagem;

            $demandasReabertas = $item->demandasReabertas;
            if ($demandasReabertas->count() > 0) {
                $sugerido = $demandasReabertas->sortByDesc('id')->first()->sugerido;
                $item->final = $sugerido;
            }
        }

        return $demandas;
    }

    public function homeIndex(Request $request){
        $user = Auth::User();
        $events = [];

        $coluna = $request->query('coluna');
        $ordem = $request->query('ordem');

        $demandas = $this->performAjaxRequest($request);

        $demandasEvents = Demanda::where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->with(['demandasReabertas' => function ($query) {
            $query->where('finalizado', null);
        }])->where(function ($query) use ($user) {
            $query->whereHas('demandasUsuario', function ($query) use ($user) {
                $query->where('usuario_id', $user->id);
            });
        })->where('entregue', '0')->where('finalizada', '0')->where('pausado', 0)->get();

        $demandasEvents->transform(function ($demanda) {
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
                })->min(); 

                $demanda->mostRecentDate = $mostRecentDate->format('Y-m-d');
            } else {
                $demanda->mostRecentDate = null;
            }

            return $demanda;
        });


        if($demandasEvents != null){
            foreach($demandasEvents as $key => $demanda){
                $demandasReabertas = $demanda->demandasReabertas;

                if ($demandasReabertas->count() > 0) {
                    $sugerido = $demandasReabertas->sortByDesc('id')->first()->sugerido;
                    $demanda->final = $sugerido;
                }

                $title = 'Job (' .$demanda->id  .') Data de entrega: '.Carbon::createFromFormat('Y-m-d H:i:s', $demanda->final)->format('d/m');

                if($demanda->mostRecentDate){
                    $demanda->final = $demanda->mostRecentDate;
                    $title = 'Job (' .$demanda->id  .') Data de entrega: '.Carbon::createFromFormat('Y-m-d', $demanda->final)->format('d/m');

                }
                
                $events[] = [
                    'title' => $title,
                    'start' => $demanda->final,
                    'end' => $demanda->final,
                    'allDay' => true,
                    'url' => route('Job', ['id' => $demanda->id]),
                    'color' => $demanda->cor,
                ];

            }
        }else{
            $events = [];
        }

        $dataAtual = date('Y-m-d H:i:s');

        $emPautaCount = Demanda::where(function ($query) use ($user) {
            $query->whereHas('demandasUsuario', function ($query) use ($user) {
                $query->where('usuario_id', $user->id);
            });
        })->where('em_pauta', '1')->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->count();

        $entregueCount = Demanda::where(function ($query) use ($user) {
            $query->whereHas('demandasUsuario', function ($query) use ($user) {
                $query->where('usuario_id', $user->id);
            });
        })->where('entregue', '1')->where('finalizada', '0')->where('pausado', 0)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->count();

        $atrasadoCount = Demanda::select('id', 'final', 'finalizada', 'entregue', 'etapa_1', 'etapa_2', 'excluido', 'criador_id')
        ->where(function ($query) use ($user) {
            $query->whereHas('demandasUsuario', function ($query) use ($user) {
                $query->where('usuario_id', $user->id);
            });
        })
        ->where('finalizada', '0')
        ->where('entregue', '0')
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->where('excluido', null)
        ->where(function ($query){
            $query->where(function ($query){
                $query->where('recorrente', 0)
                      ->where('final', '<', date('Y-m-d H:i:s'));
            })
            ->orWhere(function ($query){
                $query->where('recorrente', 1)
                ->whereHas('demandaRecorrencias', function ($subQuery){
                $subQuery->where('finalizada', 0)
                    ->where(function ($subSubQuery){
                        $subSubQuery->whereHas('recorrencias', function ($query){
                            $query->where('entregue', 0)
                                ->where('finalizado', 0)
                                ->where('data', '<',  date('Y-m-d'));
                        })->orWhereHas('recorrencias.ajustes', function ($query) {
                            $query->where('entregue', 0)
                                ->where('data', '<',  date('Y-m-d'));
                        });
                    });
                });
            });
        })
        ->get();

        foreach ($atrasadoCount as $key => $demanda) {
            $demandasReabertas = $demanda->demandasReabertas;
            if ($demandasReabertas->count() > 0) {
                $sugerido = $demandasReabertas->sortByDesc('id')->first()->sugerido;
                $demanda->final = $sugerido;
            }
        }

        $atrasadoCount = $atrasadoCount->count();
        $creators = User::select('id', 'nome')->where('tipo', 'colaborador')->orWhere('tipo','admin')->where('excluido', null)->get();
        $brands = Marca::select('id', 'nome')->where('excluido', null)->get();
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

        $reset = false;

        if ($request->ajax()) {
            $view = view('ordem-agencia', compact('demandas', 'arrayOrdem', 'ordem', 'reset'))->render();
            return response($view)->header('Content-Type', 'text/html');
        }

        return view('index', [
            'demandas' => $demandas,
            'events' => $events,
            'ordem' => $ordem,
            'creators' => $creators,
            'brands' => $brands,
            'arrayOrdem' => $arrayOrdem,
            'ordemValue' => $ordemValue,
            'emPautaCount' => $emPautaCount,
            'entregueCount' => $entregueCount,
            'atrasadoCount' => $atrasadoCount
        ]);


    }
}
