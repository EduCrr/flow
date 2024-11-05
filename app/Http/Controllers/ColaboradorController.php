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
use App\Models\DemandaTempo;
use App\Models\DemandaComplemento;
use Illuminate\Support\Facades\Validator;
Use Alert;
use App\Models\DemandaAtrasada;
use App\Models\DemandaColaborador;
use App\Models\DemandaOrdem;
use App\Models\DemandaOrdemJob;
use App\Models\DemandaUsuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Response;
use App\Utils\ValidationUtil;
use App\Mail\NotifyJobMail;
use App\Models\BriefingLido;
use App\Models\MarcaUsuario;

class ColaboradorController extends Controller
{
    private function performAjaxRequest(Request $request) {
        $user = Auth::User();

        $coluna = $request->query('coluna');
        $ordem = $request->query('ordem');

        $demandas = Demanda::where(function ($query) use ($user) {
            $query->where('criador_id', $user->id)
            ->orWhereHas('demandaColaboradores', function ($query) use ($user) {
                $query->where('usuario_id', $user->id);
            })
            ->orWhereHas('demandasUsuario', function ($query) use ($user) {
                $query->where('usuario_id', $user->id);
            });
        })
        ->where('etapa_1', 1)
        ->where('finalizada', 0)
        ->where('etapa_2', 1)
        ->with(['marcas' => function ($query) {
            $query->where('excluido', null);
        }])
        ->with(['agencia' => function ($query) {
            $query->where('excluido', null);
        }])
        ->with(['demandasReabertas' => function ($query) {
            $query->where('finalizado', null)->where('excluido', null);
        }])
        ->with(['demandaColaboradores' => function ($query) {
            $query->select('demandas_colaboradores.id');
        }])
        ->with('demandasUsuario')
        ->where('demandas.excluido', null);


        if($coluna == '' && $ordem == ''){
            $demandas->leftJoin('demandas_ordem_jobs', function ($join) use ($user) {
                $join->on('demandas.id', '=', 'demandas_ordem_jobs.demanda_id')
                    ->where('demandas_ordem_jobs.usuario_id', '=', $user->id);
            })
            ->select('demandas.*', 'demandas_ordem_jobs.ordem as ordem')
            ->orderByRaw('ISNULL(demandas_ordem_jobs.ordem) ASC, demandas_ordem_jobs.ordem ASC, demandas.id DESC');
        }

        $demandas->with(['questionamentos' => function ($query) use($user) {
            $query->with('usuario')
                ->withCount(['lidos as count_comentarios' => function ($query) use($user){
                    $query->where('visualizada', 0)->where('usuario_id', $user->id);
                }]);
        }]);

        $demandas->withCount(['prazosDaPauta as count_prazosDaPauta' => function ($query) {
            $query->where('aceitar_colaborador', 0)->where('finalizado', null);
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

        if ($coluna == 'agencia') {
            $demandas = $demandas->join('agencias', 'demandas.agencia_id', '=', 'agencias.id')
                ->orderBy('agencias.nome', $orderDirection);
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
                })->min(); // Usa `min()` para encontrar a data mais antiga (mais recente no seu caso)

                $demanda->mostRecentDate = $mostRecentDate->format('Y-m-d');
            } else {
                $demanda->mostRecentDate = null;
            }

            return $demanda;
        });

        foreach($demandas as $key => $item){
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

    public function index(Request $request){
        $user = Auth::User();

        $coluna = $request->query('coluna');
        $ordem = $request->query('ordem');

        $demandas = $this->performAjaxRequest($request);

        $events = array();
        $demandasEvents = Demanda::select('titulo', 'inicio', 'final', 'id', 'cor')->where('etapa_1', 1)->where('etapa_2', 1)->where(function ($query) use ($user) {
            $query->where('criador_id', $user->id)
                ->orWhereHas('demandaColaboradores', function ($query) use ($user) {
                    $query->where('usuario_id', $user->id);
                });
        })->where('entregue', '0')->where('finalizada', '0')->where('excluido', null)->with(['demandasReabertas' => function ($query) {
            $query->where('excluido', null);
            $query->where('finalizado', null);
        }])->where('pausado', 0)->get();

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
            $query->where('criador_id', $user->id)
                ->orWhereHas('demandaColaboradores', function ($query) use ($user) {
                    $query->where('usuario_id', $user->id);
                })->orWhereHas('demandasUsuario', function ($query) use ($user) {
                    $query->where('usuario_id', $user->id);
                });
        })->where('em_pauta', '1')->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->count();

        $entregueCount = Demanda::where(function ($query) use ($user) {
            $query->where('criador_id', $user->id)
                ->orWhereHas('demandaColaboradores', function ($query) use ($user) {
                    $query->where('usuario_id', $user->id);
                })->orWhereHas('demandasUsuario', function ($query) use ($user) {
                    $query->where('usuario_id', $user->id);
                });
        })->where('entregue', '1')->where('finalizada', '0')->where('pausado', 0)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->count();

        $atrasadoCount = Demanda::select('id', 'final', 'finalizada', 'entregue', 'etapa_1', 'etapa_2', 'excluido', 'criador_id')
        ->where(function ($query) use ($user) {
            $query->where('criador_id', $user->id)
                ->orWhereHas('demandaColaboradores', function ($query) use ($user) {
                    $query->where('usuario_id', $user->id);
                })
                ->orWhereHas('demandasUsuario', function ($query) use ($user) {
                    $query->where('usuario_id', $user->id);
                });
        })
        ->where('finalizada', '0')
        ->where('entregue', '0')
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->where('excluido', null)
        ->where(function ($query) {
            $query->where(function ($query) {
                $query->where('recorrente', 0)
                      ->where('final', '<', date('Y-m-d H:i:s'));
            })
            ->orWhere(function ($query) {
                $query->where('recorrente', 1)
                      ->whereHas('demandaRecorrencias', function ($subQuery) {
                          $subQuery->where('finalizada', 0)
                              ->where(function ($subSubQuery) {
                                  $subSubQuery->whereHas('recorrencias', function ($query) {
                                      $query->where('entregue', 0)
                                            ->where('finalizado', 0)
                                            ->where('data', '<', date('Y-m-d'));
                                  })->orWhereHas('recorrencias.ajustes', function ($query){
                                      $query->where('entregue', 0)
                                            ->where('data', '<', date('Y-m-d'));
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
        $agencys = Agencia::select('id', 'nome')->where('excluido', null)->get();
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

        if ($request->ajax()) {
            $view = view('ordem-colaborador', compact('demandas', 'arrayOrdem', 'ordem'))->render();
            return response($view)->header('Content-Type', 'text/html');
        }

        return view('Dashboard/index', [
            'demandas' => $demandas,
            'events' => $events,
            'ordem' => $ordem,
            'creators' => $creators,
            'brands' => $brands,
            'agencys'=> $agencys,
            'arrayOrdem' => $arrayOrdem,
            'ordemValue' => $ordemValue,
            'emPautaCount' => $emPautaCount,
            'entregueCount' => $entregueCount,
            'atrasadoCount' => $atrasadoCount
        ]);
    }

    // public function ordemColaborador(Request $request){
    //     $user = Auth::User();

    //     $coluna = $request->query('coluna');
    //     $ordem = $request->query('ordem');
    //     $porpagina = $request->porpagina;
    //     $search = $request->query('search');
    //     $dateRange = $request->query('dateRange');
    //     $category_id = $request->query('category_id');
    //     $aprovada = $request->query('aprovada');
    //     $agencia_id = $request->query('agencia_id');
    //     $in_tyme = $request->query('in_tyme');
    //     $ordem_filtro = $request->query('ordem_filtro');

    //     $demandas = $this->performAjaxRequest($request);
    //     $demandas->setPath(url('/dashboard'));

    //     $ordemjob = DemandaOrdem::where('usuario_id', $user->id)->first();
    //     $arrayOrdem = null;

    //     if($ordemjob){
    //         $arrayOrdem = explode(",", $ordemjob->ordem);
    //     }else{
    //         $arrayOrdem = null;
    //     }

    //     $view = view('ordem-colaborador', compact('demandas', 'arrayOrdem', 'ordem'))->render();
    //     return response($view)->header('Content-Type', 'text/html');
    // }

    // public function changeCategory(Request $request){
    //     $user = Auth::User();

    //     $demandas = Demanda::where('criador_id', $user->id)->with(['marcas' => function ($query) {
    //     $query->where('excluido', null);
    //     }])->with(['agencia' => function ($query) {
    //         $query->where('excluido', null);
    //     }])->with(['demandasReabertas' => function ($query) {
    //         $query->where('excluido', null);
    //         $query->where('finalizado', null);
    //     }])->withCount(['questionamentos as count_questionamentos' => function ($query) {
    //         $query->where('visualizada_col', 0)->where('excluido', null);
    //     }])->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->orderBy('id', 'DESC');


    //     if($request->category_id == 'pendentes'){
    //         $demandas->where('em_pauta', '0')->where('finalizada', '0')->where('entregue', '0')->where('pausado', 0)->take(15);
    //         }else if($request->category_id == 'em_pauta'){
    //             $demandas->where('em_pauta', '1')->where('finalizada', '0')->where('entregue', '0')->where('pausado', 0)->take(15);
    //         }else if($request->category_id == 'pausados'){
    //         $demandas->where('pausado', '1')->take(15);
    //         }
    //         else if($request->category_id == 'entregue'){
    //         $demandas->where('entregue', '1')->where('finalizada', '0')->where('pausado', 0)->take(15);
    //         }

    //     $demandas = $demandas->get();

    //     foreach($demandas as $key => $item){
    //         $demandasReabertas = $item->demandasReabertas;
    //         if ($demandasReabertas->count() > 0) {
    //             $sugerido = $demandasReabertas->sortByDesc('id')->first()->sugerido;
    //             $item->final = $sugerido;
    //         }
    //     }

    //     return view('demandas-categorias', [
    //         'demandas' => $demandas,
    //     ]);
    // }

    public function jobs(Request $request){
        $user = Auth::User();

        $search = $request->search;
        $jobId = $request->jobId;
        $aprovada = $request->aprovada;
        $category_id = $request->category_id;
        $inTime = $request->in_tyme;
        $dateRange = $request->dateRange;
        $ordem_filtro = $request->ordem_filtro;
        $coluna = $request->query('coluna');
        $ordem = $request->query('ordem');

        $demandas = Demanda::where('etapa_1', 1)->where('etapa_2', 1)->where('demandas.excluido', null)->where(function ($query) use ($user) {
            $query->where('criador_id', $user->id)
                ->orWhereHas('demandaColaboradores', function ($query) use ($user) {
                    $query->where('usuario_id', $user->id);
                })
                ->orWhereHas('demandasUsuario', function ($query) use ($user) {
                    $query->where('usuario_id', $user->id);
                });
        })->with(['marcas' => function ($query) {
        $query->where('excluido', null);
        }])->with(['agencia' => function ($query) {
        $query->where('excluido', null);
        }])->with(['demandasReabertas' => function ($query) {
            $query->where('excluido', null);
            $query->where('finalizado', null);
        }])
        ->with(['demandaColaboradores' => function ($query) {
            $query->select('demandas_colaboradores.id');
        }]);

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
            } elseif ($ordem_filtro === 'alfabetica') {
                $demandas->orderBy('titulo', 'ASC');
            }
        } else {
            if($coluna == '' && $ordem == ''){
                $demandas->leftJoin('demandas_ordem_jobs', function ($join) use ($user) {
                    $join->on('demandas.id', '=', 'demandas_ordem_jobs.demanda_id')
                        ->where('demandas_ordem_jobs.usuario_id', '=', $user->id);
                })
                ->select('demandas.*', 'demandas_ordem_jobs.ordem as ordem')
                ->orderByRaw('ISNULL(demandas_ordem_jobs.ordem) ASC, demandas_ordem_jobs.ordem ASC, demandas.id DESC');
            }

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
        }else{
            $dateRange = '';
        }

        if($category_id){
            if($category_id == 1){
                $status = 'Baixa';
            }else if($category_id == 5){
                $status = 'Média';
            }else if($category_id == 7){
                $status = 'Alta';
            }else if($category_id == 10){
                $status = 'Urgente';
            }
            $demandas->where('prioridade', 'like', "%$status%");
        }


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

        $demandas->with(['questionamentos' => function ($query) use($user) {
            $query->with('usuario')
                ->withCount(['lidos as count_comentarios' => function ($query) use($user){
                    $query->where('visualizada', 0)->where('usuario_id', $user->id);
                }]);
        }]);

        $demandas->withCount(['prazosDaPauta as count_prazosDaPauta' => function ($query) {
            $query->where('aceitar_colaborador', 0)
                ->where('finalizado', null);
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
            $view = view('ordem-colaborador', compact('demandas', 'arrayOrdem', 'ordem', 'reset'))->render();
            return response($view)->header('Content-Type', 'text/html');
        }

        return view('Dashboard/jobs', [
            'demandas' => $demandas,
            'search' => $search,
            'priority' => $category_id,
            'aprovada' => $aprovada,
            'inTime' => $inTime,
            'dateRange' => $dateRange,
            'arrayOrdem' => $arrayOrdem,
            'ordemValue' => $ordemValue,
            'ordem_filtro' => $ordem_filtro,
            'ordem' => $ordem,
            'jobId' => $jobId
        ]);

    }

    public function create(){
        $user = Auth::User();
        $dataAtual = Carbon::now();

        $userInfos = User::where('id', $user->id)->where('excluido', null)->with(['marcasColaborador' => function ($query) {
        $query->where('excluido', null);
        }])->with(['colaboradoresAgencias' => function ($query) {
            $query->where('excluido', null);
            }])->first();


        // $moreColaborador = User::select('id', 'nome', 'tipo')
        //     ->where('id', '!=', $user->id)
        //     ->where(function ($query) {
        //         $query->where('tipo', 'colaborador')
        //             ->orWhere('tipo', 'admin');
        //     })
        //     ->get();


        return view('Dashboard/criar', [
            'userInfos' => $userInfos,
            'dataAtual' => $dataAtual,
            // 'moreColaborador' => $moreColaborador
        ]);

    }

    public function createAction(Request $request){
        $user = Auth::User();
        $validator = Validator::make($request->all(),[
            'titulo' => 'required|min:3',
            'inicio' => 'required',
            'marcasColaboradores' => 'required',
            'final' => 'required',
            'prioridade' => 'required',
            ],[
            'titulo.required' => 'Preencha o campo título.',
            'titulo.min' => 'O campo título deve ter pelo menos 3 caracteres.',
            'inicio.required' => 'Preencha o campo data inicial.',
            'marcasColaboradores.required' => 'Preencha o campo marca.',
            'final.required' => 'Preencha o campo data de entrega.',
            'prioridade.required' => 'Preencha o campo prioridade.',
        ]
    );

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], Response::HTTP_BAD_REQUEST);
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

            if($request->moreColaboradores){
                $intersectionUsers = array_intersect($request->users, $request->moreColaboradores);

                if (!empty($intersectionUsers)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Você não pode adicionar o mesmo usuário como (colaborador) e como (usuário responsável)!'
                    ], Response::HTTP_BAD_REQUEST);
                }
            }


            if($request->inicio > $request->final){

                return response()->json([
                    'success' => false,
                    'message' => 'A data final não pode ser anterior à data inicial!'
                ], Response::HTTP_BAD_REQUEST);
            }


            $newJob = new Demanda();
            $newJob->titulo = $request->titulo;
            $newJob->criador_id = $user->id;
            $newJob->sub_criador_id  = null;
            $newJob->agencia_id = '1';
            $newJob->inicio = $request->inicio;
            $newJob->final = $request->final;
            $newJob->prioridade = $request->prioridade;
            $newJob->cor = $cor;
            $newJob->etapa_1 = 1;
            $newJob->criado = date('Y-m-d H:i:s');
            $newJob->save();


            $demandaMarcas = new DemandaMarca();
            $demandaMarcas->marca_id = $request->marcasColaboradores;
            $demandaMarcas->demanda_id = $newJob->id;
            $demandaMarcas->save();

            $marcasUser = MarcaUsuario::where('marca_id', $request->marcasColaboradores)->first();

            $demandaUser = new DemandaUsuario();
            $demandaUser->usuario_id = $marcasUser->usuario_id;
            $demandaUser->demanda_id = $newJob->id;
            $demandaUser->save();

            return response()->json([
                'success' => true,
                'message' => 'Etapa 1 criada com sucesso!',
                'redirect' => route('Job.criar_etapa_2', ['id' => $newJob->id])
            ], Response::HTTP_OK);

        }

    }

    public function createStage2($id){
        $user = Auth::User();
        $demanda = Demanda::where('id', $id)->with(['marcas' => function ($query) {
        $query->where('excluido', null);
        }])->with('descricoes')->with('demandasUsuario')->first();


        $marcas = $user->marcas()->whereNull('excluido')->get();
        $marcasColaboradores = $user->marcasColaborador()->whereNull('excluido')->get();
        $marcasIds = $demanda->marcas->pluck('id')->toArray();

        // $colaboradoresIds = $demanda->demandaColaboradores->pluck('id')->toArray();
        $agencia = Agencia::where('id', $demanda->agencia_id)->where('excluido', null)->first();

        $filteredUsuarios = $agencia->agenciasUsuarios->filter(function ($usuario) use ($user) {
            return $usuario->id !== $user->id;
        });

        $usuarios = $filteredUsuarios->map(function ($usuario) {
            return [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
            ];
        });

        // $usersIds = array();
        // foreach($demanda['demandasUsuario'] as $user){
        //     array_push($usersIds, $user->id);
        // }

        $usersIds = $demanda->demandasUsuario->pluck('id')->toArray();

        if($demanda){
            if($demanda->etapa_2 == 0){
                return view('Dashboard/criar-etapa-2', [
                    'demanda' => $demanda,
                    'marcas' => $marcas,
                    'marcasIds' => $marcasIds,
                    'agencia' => $agencia,
                    'usuarios' => $usuarios,
                    'usersIds' => $usersIds,
                    'marcasColaboradores' => $marcasColaboradores,
                ]);
            }else{
                return redirect('/dashboard');
            }
        }

    }

    public function createActionStage2(Request $request, $id){
        $user = Auth::User();

        $data = $request->all();
        $usersCriadores = [];

        $rules = ValidationUtil::yourValidationRules(false, true);
        $messages = ValidationUtil::yourValidationMessages(false, true);

        $validationResult = ValidationUtil::validateData($data, $rules, $messages);

        if ($validationResult !== null) {
            return $validationResult;
        }

        $demanda = Demanda::where('excluido', null)->with('demandaColaboradores')->with('marcas')->find($id);

        // if($request->moreColaboradores){
        //     $intersectionUsers = array_intersect($request->users, $request->moreColaboradores);

        //     if (!empty($intersectionUsers)) {
        //         return response()->json([
        //             'success' => false,
        //             'message' => 'Você não pode adicionar o mesmo usuário como (colaborador) e como (usuário responsável)!'
        //         ], Response::HTTP_BAD_REQUEST);
        //     }
        // }

        if($request->inicio > $request->final){

            return response()->json([
                'success' => false,
                'message' => 'A data final não pode ser anterior à data inicial!'
            ], Response::HTTP_BAD_REQUEST);
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
            $demanda->cor = $cor;
            $demanda->prioridade = $request->prioridade;
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
        $criadorNotificacao->usuario_id = $user->id;
        $criadorNotificacao->conteudo = 'Novo job foi criado.';
        $criadorNotificacao->criado = date('Y-m-d H:i:s');
        $criadorNotificacao->visualizada = '0';
        $criadorNotificacao->tipo = 'criada';
        $criadorNotificacao->save();

        $demandaUsuario = $demanda->demandasUsuario->first();

        $usuarioNotificacao = new Notificacao();
        $usuarioNotificacao->demanda_id = $demanda->id;
        $usuarioNotificacao->conteudo = 'Novo job foi criado.';
        $usuarioNotificacao->visualizada = '0';
        $usuarioNotificacao->tipo = 'criada';
        $usuarioNotificacao->usuario_id = $demandaUsuario->id;
        $usuarioNotificacao->criado = date('Y-m-d H:i:s');
        $usuarioNotificacao->save();

        $hasUserCriadorOrdem = DemandaOrdemJob::where('demanda_id', $demanda->id)
        ->where('usuario_id', $user->id)
        ->exists();
        if(!$hasUserCriadorOrdem){
            $createOrdemCriador = new DemandaOrdemJob();
            $createOrdemCriador->usuario_id = $user->id;
            $createOrdemCriador->demanda_id = $demanda->id;
            $createOrdemCriador->ordem = 0;
            $createOrdemCriador->save();
        }

        $hasUserAgOrdem = DemandaOrdemJob::where('demanda_id', $demanda->id)
        ->where('usuario_id', $demandaUsuario->id)
        ->exists();
        if(!$hasUserAgOrdem){
            $createOrdemUser = new DemandaOrdemJob();
            $createOrdemUser->usuario_id = $demandaUsuario->id;
            $createOrdemUser->demanda_id = $demanda->id;
            $createOrdemUser->ordem = 0;
            $createOrdemUser->save();
        }
       
        $demanda = Demanda::where('excluido', null)->with('demandaColaboradores')->with('marcas')->find($id);
        $userAdmin = User::select('id', 'nome')->where('tipo', 'admin')->whereHas('marcasColaborador', function ($query) use ($demanda) {
            $query->where('marcas.excluido', null)
                ->where('marcas.id', $demanda->marcas[0]['id']);
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

        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $user->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $user) {
            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
            ->to($user->email)
            ->subject('Novo job criado');
        });

        //agencia

        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $demandaUsuario->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $demandaUsuario) {
            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
            ->to($demandaUsuario->email)
            ->subject('Novo job criado');
        });

        return response()->json([
            'success' => true,
            'message' => 'Job criado com sucesso!',
            'redirect' => route('Job', ['id' => $demanda->id])
        ], Response::HTTP_OK);

    }

    public function edit($id){
        $user = Auth::User();
        $demanda = Demanda::where('id', $id)->where(function ($query) use ($user) {
            $query->where('criador_id', $user->id)
                ->orWhereHas('demandaColaboradores', function ($query) use ($user) {
                    $query->where('usuario_id', $user->id);
                });
        })->with('imagens')->with(['marcas' => function ($query) {
        $query->where('excluido', null);
        }])->with('descricoes')->with('demandaColaboradores')->with('demandasUsuario')->first();


        if($demanda == null){
            return redirect('/dashboard');
        }

        $marcas = $user->marcas()->whereNull('excluido')->get();

        $marcasIds = $demanda->marcas->pluck('id')->toArray();

        // $agencia = Agencia::where('id', $demanda->agencia_id)->where('excluido', null)->first();

        // $filteredUsuarios = $agencia->agenciasUsuarios->filter(function ($usuario) use ($user) {
        //     return $usuario->id !== $user->id;
        // });

        // $usuarios = $filteredUsuarios->map(function ($usuario) {
        //     return [
        //         'id' => $usuario->id,
        //         'nome' => $usuario->nome,
        //     ];
        // });

        $colaboradoresIds = $demanda->demandaColaboradores->pluck('id')->toArray();

        $moreColaborador = User::select('id', 'nome', 'tipo')
        ->where('id', '!=', $user->id)
        ->where(function ($query) {
            $query->where('tipo', 'colaborador')
                ->orWhere('tipo', 'admin');
        })
        ->whereDoesntHave('usuarioDemandas', function ($query) use ($demanda) {
            $query->where('demanda_id', $demanda->id);
        })
        ->get();


        $usersIds = $demanda->demandasUsuario->pluck('id')->toArray();

        if($demanda){
            return view('Dashboard/editar', [
                'demanda' => $demanda,
                'marcas' => $marcas,
                'marcasIds' => $marcasIds,
                // 'agencia' => $agencia,
                // 'usuarios' => $usuarios,
                'usersIds' => $usersIds,
                'colaboradoresIds' => $colaboradoresIds,
                'moreColaborador' => $moreColaborador
            ]);
        }
        return redirect('/dashboard');

    }

    public function editAction(Request $request, $id){
        $user = Auth::User();
        $data = $request->all();

        $rules = ValidationUtil::yourValidationRules(false, true);
        $messages = ValidationUtil::yourValidationMessages(false, true);

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

        if ($request->briefing && $request->briefing !== $demandaDes->descricao) {
            BriefingLido::where('demanda_id', $demanda->id)->delete();
            Notificacao::where('demanda_id', $demanda->id)->where('tipo', 'briefing')->where('tipo_referencia', 'demanda-briefing-'.$demanda->id)->delete();
            $demandaDes->descricao = $request->briefing;
        }

        $demandaDes->save();
        $demanda->save();

        return response()->json([
            'success' => true,
            'message' => 'Job editado'
        ], Response::HTTP_OK);

    }

    public function copy($id){
        $user = Auth::User();
        $demanda = Demanda::where('id', $id)->where(function ($query) use ($user) {
            $query->where('criador_id', $user->id)
                ->orWhereHas('demandaColaboradores', function ($query) use ($user) {
                    $query->where('usuario_id', $user->id);
                })->orWhereHas('demandasUsuario', function ($query) use ($user) {
                    $query->where('usuario_id', $user->id);
                });
        })->with(['marcas' => function ($query) {
        $query->where('excluido', null);
        }])->with('descricoes')->first();


        $marcasC = $user->marcasColaborador()->whereNull('excluido')->get();

        if($demanda){
            return view('Dashboard/copiar', [
                'demanda' => $demanda,
                'marcasC' => $marcasC
            ]);
        }
        return redirect('/dashboard');

    }

    public function copyAction(Request $request){
        $user = Auth::User();

        $data = $request->all();

        $rules = ValidationUtil::yourValidationRules(false, true);
        $messages = ValidationUtil::yourValidationMessages(false, true);

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
        $newJob->titulo = $newJob->id . ' '.$request->titulo;
        $newJob->titulo = $request->titulo;

        if($request->drive){
            $newJob->drive = $request->drive;
        }

        $newJob->criador_id = $user->id;
        $newJob->agencia_id = '1';
        $newJob->inicio = $request->inicio;
        $newJob->final = $request->final;
        $newJob->prioridade = $request->prioridade;
        $newJob->cor = $cor;
        $newJob->status = 'Pendente';
        $newJob->etapa_1 = 1;
        $newJob->etapa_2 = 1;
        $newJob->criado = date('Y-m-d H:i:s');
        $newJob->save();

        $newTimeLine = new LinhaTempo();
        $newTimeLine->demanda_id = $newJob->id;
        $newTimeLine->status = 'Job cadastrado';
        $newTimeLine->code = 'criado';
        $newTimeLine->usuario_id = $user->id;
        $newTimeLine->criado = date('Y-m-d H:i:s');
        $newTimeLine->save();

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

        $demandaMarcas = new DemandaMarca();
        $demandaMarcas->marca_id = $request->marcasColaboradores;
        $demandaMarcas->demanda_id = $newJob->id;
        $demandaMarcas->save();

        $marcasUser = MarcaUsuario::where('marca_id', $request->marcasColaboradores)->first();

        $demandaUser = new DemandaUsuario();
        $demandaUser->usuario_id = $marcasUser->usuario_id;
        $demandaUser->demanda_id = $newJob->id;
        $demandaUser->save();

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
        $criadorNotificacao->usuario_id = $user->id;
        $criadorNotificacao->conteudo = 'Novo job foi criado.';
        $criadorNotificacao->criado = date('Y-m-d H:i:s');
        $criadorNotificacao->visualizada = '0';
        $criadorNotificacao->tipo = 'criada';
        $criadorNotificacao->save();

        //send e-mail

        $actionLink = route('Job', ['id' => $newJob->id]);
        $bodyEmail = 'Seu novo job foi criado com sucesso. Acesse pelo link logo abaixo.';
        $titleEmail = 'Novo job criado';

        //notificar agencia

        $usuarioNotificacao = new Notificacao();
        $usuarioNotificacao->demanda_id = $newJob->id;
        $usuarioNotificacao->conteudo = 'Novo job foi criado.';
        $usuarioNotificacao->visualizada = '0';
        $usuarioNotificacao->tipo = 'criada';
        $usuarioNotificacao->usuario_id = $marcasUser->usuario_id;;
        $usuarioNotificacao->criado = date('Y-m-d H:i:s');
        $usuarioNotificacao->save();

        //criador

        $hasCriadorOrdem = DemandaOrdemJob::where('demanda_id', $newJob->id)
        ->where('usuario_id', $user->id)
        ->exists();
        if(!$hasCriadorOrdem){
            $createOrdemCriador = new DemandaOrdemJob();
            $createOrdemCriador->usuario_id = $user->id;
            $createOrdemCriador->demanda_id = $newJob->id;
            $createOrdemCriador->ordem = 0;
            $createOrdemCriador->save();
        }

        $hasUserAgOrdem = DemandaOrdemJob::where('demanda_id', $newJob->id)
        ->where('usuario_id', $marcasUser->usuario_id)
        ->exists();
        if(!$hasUserAgOrdem){
            $createOrdemUser = new DemandaOrdemJob();
            $createOrdemUser->usuario_id = $marcasUser->usuario_id;
            $createOrdemUser->demanda_id = $newJob->id;
            $createOrdemUser->ordem = 0;
            $createOrdemUser->save();
        }

        $userAdmin = User::select('id', 'nome')->where('tipo', 'admin')->whereHas('marcasColaborador', function ($query) use ($request) {
            $query->where('marcas.excluido', null)
                ->where('marcas.id', $request->marcasColaboradores);
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

        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $user->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $user) {
            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
            ->to($user->email)
            // ->bcc('eduardo.8poroito@gmail.com')
            ->subject('Novo job criado');
        });

        //agencia

        $userAg = User::where('id', $marcasUser->usuario_id)->first();

        Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $userAg->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($request, $userAg) {
            $message->from('envios@fmfm.com.br', 'Flow 8poroito')
            ->to($userAg->email)
            // ->bcc('eduardo.8poroito@gmail.com')
            ->subject('Novo job criado');
        });

        return response()->json([
            'success' => true,
            'message' => 'Job copiado.'
        ], Response::HTTP_OK);

    }

    public function reOpenJob(Request $request, $id){
        $user = Auth::User();
        $newTimeLine = new LinhaTempo();
        $newTimeLine->demanda_id = $id;
        $newTimeLine->usuario_id = $user->id;

        $newTimeLine->criado = date('Y-m-d H:i:s');

        $hasReopenCount = LinhaTempo::where('demanda_id', $id)->where('code', 'reaberto')->count();
        //10
        if($hasReopenCount == 0){
            $newTimeLine->status = "Reaberto 1";
            $newTimeLine->code = "reaberto";
            $newTimeLine->save();

        }else{
            $newTimeLine->status = "Reaberto ".($hasReopenCount + 1);
            $newTimeLine->code = "reaberto";
            $newTimeLine->save();
        }
        //change select

        $demanda = Demanda::where('id', $id)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->with('demandasUsuario')->first();
        $titleEmail = 'Reaberto o job '.$demanda->id. ': '. $demanda->titulo;

        $demandaReaberta = new DemandaReaberta();
        $demandaReaberta->demanda_id = $id;
        $demandaReaberta->iniciado = date('Y-m-d H:i:s');
        $demandaReaberta->status =  $newTimeLine->status;
        $demandaReaberta->sugerido = $request->sugerido_reaberto;
        $demandaReaberta->save();

        $demanda->em_pauta = 0;
        $demanda->status = 'Pendente';
        $demanda->finalizada = 0;
        $demanda->entregue = 0;
        $demanda->recebido = 0;
        $demanda->em_alteracao = 0;
        $demanda->entregue_recebido = 0;
        $demanda->save();

        $actionLink = route('Job', ['id' => $id]);
        $bodyEmail = 'O job '.$id . ' foi reaberto.'. '<br/>'.  'Acesse pelo link logo abaixo.';

        foreach($demanda['demandasUsuario'] as $item){
            $usuarioNotificacao = new Notificacao();
            $usuarioNotificacao->demanda_id = $demanda->id;
            $usuarioNotificacao->conteudo = 'Job reaberto.';
            $usuarioNotificacao->visualizada = '0';
            $usuarioNotificacao->tipo = 'reaberto';
            $usuarioNotificacao->usuario_id = $item->id;
            $usuarioNotificacao->criado = date('Y-m-d H:i:s');
            $usuarioNotificacao->save();

            if($item->notificar_email == 1){
                Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($item, $titleEmail, $id) {
                    $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                    ->to($item->email)
                    // ->bcc('eduardo.8poroito@gmail.com')
                    ->subject($titleEmail);
                });
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Job reaberto.'
        ], Response::HTTP_OK);

    }

    public function finalize($id){
        $user = Auth::User();

        $hasFinalizeCount = LinhaTempo::where('demanda_id', $id)->where('code', 'finalizado')->count();
        $demanda = Demanda::where('id', $id)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->with('demandasUsuario')->first();

        $lineTime = new LinhaTempo();
        $lineTime->demanda_id = $id;
        $lineTime->usuario_id = $user->id;
        $lineTime->criado = date('Y-m-d H:i:s');
        $titleEmail = 'Finalizado o job '.$demanda->id. ': '. $demanda->titulo;

        if($hasFinalizeCount == 0){
            $lineTime->status = 'Finalizado 1';
            $lineTime->code = 'finalizado';
            $lineTime->save();
        }else{
            $lineTime->status = 'Finalizado '.($hasFinalizeCount + 1);
            $lineTime->code = 'finalizado';
            $lineTime->save();
        }

        $actionLink = route('Job', ['id' => $id]);
        $bodyEmail = 'O job '.$id . ' foi finalizado com sucesso.'. '<br/>'.  'Acesse pelo link logo abaixo.';

        //demandas reabertas
        $demandasReabertas = DemandaReaberta::where('demanda_id', $id)->get();

        foreach($demandasReabertas as $item){
            if($item->finalizado == null){
                $item->finalizado = date('Y-m-d H:i:s');
                $item->save();
            }
        }

        $demanda->finalizada = 1;
        $demanda->status = "Finalizado";
        $demanda->entregue = 0;
        $demanda->em_pauta = 0;
        $demanda->save();

        //notificar

        foreach($demanda['demandasUsuario'] as $item){
            $usuarioNotificacao = new Notificacao();
            $usuarioNotificacao->demanda_id = $demanda->id;
            $usuarioNotificacao->conteudo = 'Job finalizado.';
            $usuarioNotificacao->visualizada = '0';
            $usuarioNotificacao->tipo = 'finalizado';
            $usuarioNotificacao->usuario_id = $item->id;
            $usuarioNotificacao->criado = date('Y-m-d H:i:s');
            $usuarioNotificacao->save();

            if($item->notificar_email == 1){
                Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($item, $titleEmail, $id) {
                    $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                    ->to($item->email)
                    // ->bcc('eduardo.8poroito@gmail.com')
                    ->subject($titleEmail);
                });
            }
        }

        $removeOrdem = DemandaOrdemJob::where('demanda_id', $demanda->id)->delete();
        $removerDemandasAtrasadas = DemandaAtrasada::where('demanda_id', $demanda->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job finalizado com sucesso.'
        ], Response::HTTP_OK);

    }

    //congelar job
    public function pause($id){
        $user = Auth::User();


        $demanda = Demanda::where('id', $id)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->with('demandasUsuario')->first();
        $titleEmail = 'Congelado o job '.$demanda->id. ': '. $demanda->titulo;
        $actionLink = route('Job', ['id' => $id]);
        $bodyEmail = 'O job '.$id . ' foi congelado.'. '<br/>'.  'Acesse pelo link logo abaixo.';

        foreach($demanda['demandasUsuario'] as $item){
            $usuarioNotificacao = new Notificacao();
            $usuarioNotificacao->demanda_id = $demanda->id;
            $usuarioNotificacao->conteudo = $user->nome. ' congelou o job.';
            $usuarioNotificacao->visualizada = '0';
            $usuarioNotificacao->tipo = 'congelado';
            $usuarioNotificacao->usuario_id = $item->id;
            $usuarioNotificacao->criado = date('Y-m-d H:i:s');
            $usuarioNotificacao->save();


            if($item->notificar_email == 1){
                Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($item, $titleEmail, $id) {
                    $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                    ->to($item->email)
                    ->subject($titleEmail);
                });
            //     $jobMail = new NotifyJobMail($actionLink, $item->nome, $bodyEmail, $titleEmail, $subject);
            //     Mail::to($item->email)->queue($jobMail);
            }
        }

        $removerDemandasAtrasadas = DemandaAtrasada::where('demanda_id', $demanda->id)->delete();

        $newTimeLine = new LinhaTempo();
        $newTimeLine->demanda_id = $demanda->id;
        $newTimeLine->status = 'Job congelado';
        $newTimeLine->code = 'congelado';
        $newTimeLine->usuario_id = $user->id;
        $newTimeLine->criado = date('Y-m-d H:i:s');
        $newTimeLine->save();

        $demanda->pausado = 1;
        $demanda->status = 'Congelado';
        $demanda->save();

        return response()->json([
            'success' => true,
            'message' => 'Job congelado com sucesso.'
        ], Response::HTTP_OK);

    }

    //retornar job
    public function resume(Request $request, $id){
        $user = Auth::User();
        $demanda = Demanda::where('id', $id)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->with('demandasUsuario')->first();
        $titleEmail = 'Retomado o job '.$demanda->id. ': '. $demanda->titulo;

        $actionLink = route('Job', ['id' => $id]);
        $bodyEmail = 'O job '.$id . ' foi retomado com sucesso.'. '<br/>'.  'Acesse pelo link logo abaixo.';

        $demandasReabertasCount = DemandaReaberta::where('demanda_id', $id)->count();
        $demandasReabertas = DemandaReaberta::where('demanda_id', $id)->orderBy('id', 'desc')->first();

        if($demandasReabertasCount == 0){
            if($request->newFinalDate != null){
                $demanda->final = $request->newFinalDate;
            }
        }else{
            if($request->newFinalDate != null){
                $demandasReabertas->sugerido = $request->newFinalDate;
            }
        }

        //pautas com nova datas que n foram concluidas

        $demandaPautas = DemandaTempo::where('demanda_id', $id)->where('finalizado', 0)->get();
        foreach($demandaPautas as $item){
            $item->aceitar_agencia = 0;
            $item->aceitar_colaborado = 0;
            $item->save();
        }

        foreach($demanda['demandasUsuario'] as $item){
            $usuarioNotificacao = new Notificacao();
            $usuarioNotificacao->demanda_id = $demanda->id;
            $usuarioNotificacao->conteudo = $user->nome .' retomou o job.';
            $usuarioNotificacao->visualizada = '0';
            $usuarioNotificacao->tipo = 'criada';
            $usuarioNotificacao->usuario_id = $item->id;
            $usuarioNotificacao->criado = date('Y-m-d H:i:s');
            $usuarioNotificacao->save();

            if($item->notificar_email == 1){
                Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $item->nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($item, $titleEmail, $id) {
                    $message->from('envios@fmfm.com.br', 'Flow 8poroito')
                    ->to($item->email)
                    ->subject($titleEmail);
                });
            }
        }

        $newTimeLine = new LinhaTempo();
        $newTimeLine->demanda_id = $demanda->id;
        $newTimeLine->status = 'Job retomado';
        $newTimeLine->code = 'criada';
        $newTimeLine->usuario_id = $user->id;
        $newTimeLine->criado = date('Y-m-d H:i:s');
        $newTimeLine->save();

        $demanda->pausado = 0;

        if($demanda->em_pauta == 0 && $demanda->finalizada == 0 && $demanda->entregue == '0'){
            $demanda->status = 'Pendente';

        }else if($demanda->em_pauta == 1){
            $demanda->status = 'Em pauta';

        }else if($demanda->entregue == 1 ){
            $demanda->status = 'Entregue';

        }else if($demanda->finalizada == 1){
            $demanda->status = 'Finalizado';
        }

        $demanda->save();

        return response()->json([
            'success' => true,
            'message' => 'Job retomado com sucesso.'
        ], Response::HTTP_OK);

    }

    public function delete($id)
    {
        $user = Auth::user();
        $demanda = Demanda::where('id', $id)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->first();

        if ($demanda) {
            $demanda->excluido = date('Y-m-d H:i:s');
            $demanda->save();
            $deleteNotifications = Notificacao::where('demanda_id', $id)->delete();

            if ($user->tipo == 'colaborador') {
                return response()->json([
                    'success' => true,
                    'message' => 'Job excluído com sucesso.',
                    'redirect' => route('dashboard')
                ], Response::HTTP_OK);
            } elseif ($user->tipo == 'admin') {
                return response()->json([
                    'success' => true,
                    'message' => 'Job excluído com sucesso.',
                    'redirect' => route('Admin')
                ], Response::HTTP_OK);
            }
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Job não pode ser excluído.'
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    public function getJobsByDate(Request $request){
        $date = Carbon::createFromFormat('Y-m-d\TH:i', $request->final);
        $brand = $request->brand;
        $final = $date->toDateString();

        $demanda = Demanda::whereDate('final', $final)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->whereHas('marcas', function ($query) use ($brand) {
            $query->where('marcas.excluido', null)
                ->where('marcas.id', $brand);
        })->count();

        //DATA.JS

        if($demanda >= 4){
           return response()->json($demanda);
       }else{
           return null;
       }
    }

    public function acceptTime(Request $request, $id){
        $user = Auth::User();
        $demandaPrazo = DemandaTempo::find($id);
        $demanda = Demanda::select('id', 'final')->where('id', $demandaPrazo->demanda_id)->with('demandasUsuario')->first();
        $countDemandasReabertas = DemandaReaberta::where('demanda_id', $demanda->id)->count();
        $demandasReaberta = DemandaReaberta::where('demanda_id', $demanda->id)->orderByDesc('id')->first();
        //prazo for maior que prazo final
        if($demanda){
            if($countDemandasReabertas == 0){
                if (strtotime($demandaPrazo->sugerido) > strtotime($demanda->final)) {
                    // $request->sugeridoComment é maior que $demanda->final
                    $demanda->final = $demandaPrazo->sugerido;
                    $demanda->save();
                    $removerDemandasAtrasadas = DemandaAtrasada::where('demanda_id', $demanda->id)->delete();
                }
            }else{
                if (strtotime($demandaPrazo->sugerido) > strtotime($demandasReaberta->sugerido)) {
                    // $request->sugeridoComment é maior que $demanda->final
                    $demandasReaberta->sugerido = $demandaPrazo->sugerido;
                    $demandasReaberta->save();
                    $removerDemandasAtrasadas = DemandaAtrasada::where('demanda_id', $demanda->id)->delete();
                }
            }
        }

        //pegar numero
        if (preg_match('/\d+/', $demandaPrazo->status, $matches)) {
            $lastNumber = $matches[0];
        }

        $demandaPrazo->aceitar_colaborador = 1;
        $demandaPrazo->save();
        $content = '';

        if($demandaPrazo->code_tempo == 'em-pauta'){
           $content = $user->nome . ' aceitou o prazo da ' . strtolower($demandaPrazo->status).' do job '.$demanda->id.'.';
        }else if($demandaPrazo->code_tempo == 'alteracao'){
           $content = $user->nome . ' aceitou o prazo da alteração  ' . $lastNumber.' do job '.$demanda->id.'.';
        }

        foreach($demanda['demandasUsuario'] as $item){
            $notificacao = new Notificacao();
            $notificacao->demanda_id = $demandaPrazo->demanda_id;
            $notificacao->criado = date('Y-m-d H:i:s');
            $notificacao->visualizada = '0';
            $notificacao->tipo = 'criada';
            $notificacao->usuario_id = $item->id;
            $notificacao->criado = date('Y-m-d H:i:s');
            $notificacao->conteudo = $content;
            $notificacao->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Prazo aceito.'
        ], 200);

    }

    public function receiveAlteration(Request $request, $id){
        $user = Auth::User();
        $demanda = Demanda::where('id', $id)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->with('demandasUsuario')->first();
        $demandaTempoCount = DemandaTempo::where('demanda_id', $demanda->id)->where('code_tempo', 'alteracao')->count();
        if($demanda){
            $demanda->entregue_recebido = 1;

            $newTimeLine = new LinhaTempo();
            $newTimeLine->demanda_id = $id;
            $newTimeLine->usuario_id = $user->id;
            $newTimeLine->code = 'recebida-alteracao';
            if($demandaTempoCount > 0){
                $newTimeLine->status = "Alteração recebida";
            }else{
                $newTimeLine->status = "Pauta recebida";
            }
            $newTimeLine->criado = date('Y-m-d H:i:s');

            $newTimeLine->save();
            $demanda->save();

            foreach($demanda['demandasUsuario'] as $item){
                $notificacao = new Notificacao();
                $notificacao->demanda_id = $demanda->id;
                $notificacao->criado = date('Y-m-d H:i:s');
                $notificacao->visualizada = '0';
                $notificacao->conteudo = $user->nome . ' recebeu suas pautas.';
                $notificacao->tipo = 'criada';
                $notificacao->usuario_id = $item->id;
                $notificacao->criado = date('Y-m-d H:i:s');
                $notificacao->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Alteração recebida.'
            ], Response::HTTP_OK);

        }
    }

    public function stages(){
        $user = Auth::User();
        $demandas = Demanda::where('criador_id', $user->id)->where('etapa_1', 1)->where('etapa_2', 0)->where('excluido', null)->with(['agencia' => function ($query) {
            $query->where('excluido', null);
        }])->get();

        if($demandas){
            return view('Dashboard/etapas', [
                'demandas' => $demandas,
           ]);
        }

        return redirect('/dashboard');

    }

    public function deleteStage1($id)
    {
        $demanda = Demanda::where('id', $id)->first();
        if($demanda){
            $demanda->delete();

            return response()->json([
                'success' => true,
                'message' => 'Job removido.'
            ], Response::HTTP_OK);

        }

        return response()->json([
            'success' => false,
            'message' => 'Esse job não pode ser removido.'
        ], Response::HTTP_BAD_REQUEST);

    }


    public function getUserAgency(Request $request) {
        $user = Auth::user();

        $agencia = Agencia::findOrFail($request->id);

        $filteredUsuarios = $agencia->agenciasUsuarios->filter(function ($usuario) use ($user) {
            return $usuario->id !== $user->id;
        });

        $usuarios = $filteredUsuarios->map(function ($usuario) {
            return [
                'id' => $usuario->id,
                'nome' => $usuario->nome,
            ];
        });

        return response()->json($usuarios);
    }

}
