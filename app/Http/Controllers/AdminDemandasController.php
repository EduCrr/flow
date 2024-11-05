<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Demanda;
use App\Models\User;
use App\Models\Agencia;
use App\Models\Marca;
use App\Models\InformacaoUsuario;
use App\Models\Estado;
use App\Models\Cidade;
use App\Models\MarcaUsuario;
use App\Models\AgenciaUsuario;
use App\Models\AgenciaColaborador;
use App\Models\UsuarioLog;
use Carbon\Carbon;
use App\Models\LinhaTempo;
use App\Models\DemandaTempo;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\DemandasExport;
use App\Exports\DemandasExportJobs;
use App\Exports\DemandasExportPrazos;
use App\Models\DemandaMarca;
use App\Models\AdminAgencia;
use App\Models\DemandaOrdem;
use App\Models\DemandaUsuario;
use App\Models\MarcaColaborador;
use App\Utils\AplyFilters;
use Carbon\CarbonInterval;
use Carbon\CarbonHolidays\Brazil;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AdminDemandasController extends Controller
{
    //FAZ AJAX NA INDEX DO ADMIN, ESTÁ SENDO USADO EM 2 PUBLICS LOGO ABAIXO
    private function performAjaxRequest(Request $request) {
        $user = Auth::User();

        $coluna = $request->query('coluna');
        $ordem = $request->query('ordem');

        $marcasC = MarcaColaborador::where('usuario_id', $user->id)->pluck('marca_id')->toArray();

        $demandas = Demanda::where('demandas.excluido', null)
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->where('finalizada', 0)
        ->whereHas('marcas', function ($query) use ($marcasC) {
            $query->where('marcas.excluido', null)
                ->whereIn('marcas.id', $marcasC);
        })
        ->with(['agencia' => function ($query) {
            $query->where('excluido', null);
        }])
        ->with(['demandasReabertas' => function ($query) {
            $query->where('finalizado', null);
            $query->where('excluido', null);
        }])
        ->with('demandasUsuario')
        ->with(['demandaColaboradores' => function ($query) {
            $query->select('demandas_colaboradores.id');
        }])
        ->with('criador')
        ->with('subCriador');

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

        $demandas->withCount(['prazosDaPauta as count_prazosDaPauta' => function ($query) use ($user) {
            $query->where(function ($query) use ($user) {
                $query->where('demandas.criador_id', $user->id)
                    ->where('aceitar_colaborador', 0)
                    ->where('finalizado', null);
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

        foreach ($demandas as $key => $item) {
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

    public function index(Request $request){
        $user = Auth::User();

        $coluna = $request->query('coluna');
        $ordem = $request->query('ordem');

        $demandas = $this->performAjaxRequest($request);
        $dataAtual = date('Y-m-d H:i:s');

        $marcasC = MarcaColaborador::where('usuario_id', $user->id)->pluck('marca_id')->toArray();

        $emPautaCount = Demanda::where('em_pauta', '1')->where('finalizada', 0)->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->whereHas('marcas', function ($query) use ($marcasC) {
            $query->where('marcas.excluido', null)
                ->whereIn('marcas.id', $marcasC);
        })->count();
        $entregueCount =  Demanda::where('entregue', '1')->where('finalizada', '0')->where('pausado', 0)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->whereHas('marcas', function ($query) use ($marcasC) {
            $query->where('marcas.excluido', null)
                ->whereIn('marcas.id', $marcasC);
        })->count();
        $atrasadoCount = Demanda::select('id', 'final', 'finalizada', 'entregue', 'etapa_1', 'etapa_2', 'excluido', 'criador_id')
        ->where('finalizada', '0')
        ->where('entregue', '0')
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->where('excluido', null)
        ->where(function ($query) use($marcasC){
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
                                    ->where('data', '<', date('Y-m-d'));
                            })->orWhereHas('recorrencias.ajustes', function ($query) {
                                $query->where('entregue', 0)
                                    ->where('data', '<', date('Y-m-d'));
                            });
                        });
                });
            })
            ->whereHas('marcas', function ($query) use ($marcasC) {
                $query->where('marcas.excluido', null)
                    ->whereIn('marcas.id', $marcasC);
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
        $currentYear = date('Y');
        $jobsPerMonths = [];

        // $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        // for ($i = 1; $i <= 12; $i++) {
        //     $month = $meses[$i - 1];
        //     $jobsCriados = Demanda::where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->whereYear('criado', Carbon::now()->year)->whereMonth('criado', $i)->whereHas('marcas', function ($query) use ($user) {
        //         $query->where('marcas.excluido', null)
        //             ->where('marcas.id', $user->marca);
        //     })->count();

        //     $jobsPerMonths[] = [
        //         'month' => $month,
        //         'jobs' => $jobsCriados,
        //     ];
        // }

        //média em meses
        $currentYear = date('Y');

        $meses = [
            'Jan' => 'Jan',
            'Feb' => 'Fev',
            'Mar' => 'Mar',
            'Apr' => 'Abr',
            'May' => 'Mai',
            'Jun' => 'Jun',
            'Jul' => 'Jul',
            'Aug' => 'Ago',
            'Sep' => 'Set',
            'Oct' => 'Out',
            'Nov' => 'Nov',
            'Dec' => 'Dez',
        ];

        for ($month = 1; $month <= 12; $month++) {
            $demandasCriadas[$month] = Demanda::select('id', 'criado', 'finalizada')->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->whereYear('criado', $currentYear)->whereMonth('criado', $month)->whereHas('marcas', function ($query) use ($marcasC) {
                $query->where('marcas.excluido', null)
                    ->whereIn('marcas.id', $marcasC);
            })->count();
            $demandasFinalizadas[$month] = Demanda::select('id', 'criado', 'finalizada')->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->whereYear('criado', $currentYear)->whereMonth('criado', $month)->where('finalizada', 1)->whereHas('marcas', function ($query) use ($marcasC) {
                $query->where('marcas.excluido', null)
                    ->whereIn('marcas.id', $marcasC);
            })->count();
        }

        //demandas infos
        $demandasMesesCriadas = [];
        foreach ($demandasCriadas as $indice => $array) {
            if (!empty($array)) {
                $demandasMesesCriadas[] = [
                    'mes' => $meses[Carbon::createFromFormat('!m', $indice)->format('M')],
                    'criadas' => $array
                ];
            } else {
                $demandasMesesCriadas[] = [
                    'mes' => $meses[Carbon::createFromFormat('!m', $indice)->format('M')],
                    'criadas' => 0
                ];
            }
        }

        $demandaMesesFinalizadas = [];

        foreach ($demandasFinalizadas as $indice => $array) {
            if (!empty($array)) {
                $demandaMesesFinalizadas[] = [
                    'mes' => $meses[Carbon::createFromFormat('!m', $indice)->format('M')],
                    'finalizadas' => $array
                ];
            } else {
                $demandaMesesFinalizadas[] = [
                    'mes' => $meses[Carbon::createFromFormat('!m', $indice)->format('M')],
                    'finalizadas' => 0
                ];
            }
        }


        $resultadosDemanda = [];
        //juntar criadas e finalizadas
        foreach($demandasMesesCriadas as $c){
            foreach($demandaMesesFinalizadas as $f){
                if($c['mes'] == $f['mes']){
                    $resultadosDemanda[] = [
                        "mes" => $c['mes'],
                        'criadas' => $c['criadas'],
                        'finalizadas' => $f['finalizadas']
                    ];
                }
            }
        }

        $events = array();
        $demandasEvents = Demanda::select('titulo', 'inicio', 'final', 'id', 'cor')->where('etapa_1', 1)->where('etapa_2', 1)->where('entregue', '0')->where('finalizada', '0')->where('excluido', null)->with(['demandasReabertas' => function ($query) {
            $query->where('excluido', null);
            $query->where('finalizado', null);
        }])->where('pausado', 0)->whereHas('marcas', function ($query) use ($marcasC) {
            $query->where('marcas.excluido', null)
                ->whereIn('marcas.id', $marcasC);
        })->get();

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

        $users = MarcaColaborador::whereIn('marca_id', $marcasC)
        ->join('usuarios', 'marcas_colaboradores.usuario_id', '=', 'usuarios.id')
        ->select('usuarios.*')
        ->distinct()
        ->get();

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
            $view = view('ordem-admin', compact('demandas', 'arrayOrdem', 'ordem', 'reset'))->render();
            return response($view)->header('Content-Type', 'text/html');
        }

        return view('Admin/index', [
            'demandas' => $demandas,
            'emPautaCount' => $emPautaCount,
            'entregueCount' => $entregueCount,
            'atrasadoCount' => $atrasadoCount,
            'jobsPerMonths' => $jobsPerMonths,
            'events' => $events,
            'ordem' => $ordem,
            'arrayOrdem' => $arrayOrdem,
            'ordemValue' => $ordemValue,
            'users' => $users,
            'resultadosDemanda' => $resultadosDemanda,
        ]);
    }


    public function chart(Request $request) {
        $user = Auth::User();
        $marcasC = MarcaColaborador::where('usuario_id', $user->id)->pluck('marca_id')->toArray();
        $usuario = $request->input('usuario');
        $dataGrafico = $request->input('dataGrafico');
        $dataAtual = date('Y-m-d H:i:s');

        $emPautaQuery = Demanda::where('em_pauta', '1')
        ->where('finalizada', 0)
        ->where('excluido', null)
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)->whereHas('marcas', function ($query) use ($marcasC) {
            $query->where('marcas.excluido', null)
                ->whereIn('marcas.id', $marcasC);
        });

        $entregueQuery = Demanda::where('entregue', '1')
        ->where('finalizada', '0')
        ->where('pausado', 0)
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->where('excluido', null)->whereHas('marcas', function ($query) use ($marcasC) {
            $query->where('marcas.excluido', null)
                ->whereIn('marcas.id', $marcasC);
        });

        $atrasadoQuery = Demanda::select('id', 'final', 'finalizada', 'entregue', 'etapa_1', 'etapa_2', 'excluido', 'criador_id')
        ->where('finalizada', '0')
        ->where('entregue', '0')
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->where('excluido', null)
        ->with(['demandasReabertas' => function ($query) {
            $query->where('finalizado', null);
            $query->where('excluido', null);
        }])->where('final', '<', $dataAtual)->whereHas('marcas', function ($query) use ($marcasC) {
            $query->where('marcas.excluido', null)
                ->whereIn('marcas.id', $marcasC);
        });


        if($usuario){
            AplyFilters::applyFilters($emPautaQuery, null, null, $usuario);
            AplyFilters::applyFilters($entregueQuery, null, null, $usuario);
            AplyFilters::applyFilters($atrasadoQuery, null, null, $usuario);
        }

        if($dataGrafico){
            AplyFilters::applyFilters($emPautaQuery, null, null, null, $dataGrafico);
            AplyFilters::applyFilters($entregueQuery, null, null, null, $dataGrafico);
            AplyFilters::applyFilters($atrasadoQuery, null, null, null, $dataGrafico);
        }

        $emPautaCount = $emPautaQuery->count();
        $entregueCount = $entregueQuery->count();
        $atrasadoQuery = $atrasadoQuery->get();

        foreach ($atrasadoQuery as $key => $demanda) {
            $demandasReabertas = $demanda->demandasReabertas;
            if ($demandasReabertas->count() > 0) {
                $sugerido = $demandasReabertas->sortByDesc('id')->first()->sugerido;
                $demanda->final = $sugerido;
            }
        }

        $atrasadoCount = $atrasadoQuery->count();

        $data = [
            'emPautaCount' => $emPautaCount,
            'entregueCount' => $entregueCount,
            'atrasadoCount' => $atrasadoCount,
        ];

        return response()->json($data);
    }


    public function jobs(Request $request){
        $user = Auth::User();
        $search = $request->search;
        $jobId = $request->jobId;
        $aprovada = $request->aprovada;
        $inTime = $request->in_tyme;
        $priority = $request->category_id;
        $endDate = $request->endDateInput;
        $dateRange = $request->dateRange;
        $colaborador = $request->colaborador_id;
        $ordem_filtro = $request->ordem_filtro;
        $porpagina = $request->input('porpagina', 15);
        $marcasC = MarcaColaborador::where('usuario_id', $user->id)->pluck('marca_id')->toArray();

        $coluna = $request->query('coluna');
        $ordem = $request->query('ordem');


        $demandas = Demanda::where('demandas.excluido', null)
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->with(['agencia' => function ($query) {
            $query->where('excluido', null);
        }])
        ->with(['demandasReabertas' => function ($query) {
            $query->where('finalizado', null);
            $query->where('excluido', null);
        }])
        ->whereHas('marcas', function ($query) use ($marcasC) {
            $query->where('marcas.excluido', null)
                ->whereIn('marcas.id', $marcasC);
        })
        ->with(['demandaColaboradores' => function ($query) {
            $query->select('demandas_colaboradores.id');
        }])
        ->with('criador');


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


        if($colaborador != '0' && $colaborador){
            $demandas->whereHas('criador', function($query)  use($colaborador){
                $query->where('usuarios.id', $colaborador);
                $query->where('usuarios.excluido', null );
            });
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

        // $demandas->with('demandasUsuario')->withCount(['questionamentos as count_questionamentos' => function ($query)  use($user){
        //     $query->where('visualizada_col', 0)
        //         ->where('excluido', null)
        //         ->where(function ($query)  use($user) {
        //             $query->where('tipo', 'like', '%Questionamento%')
        //                 ->orWhere('tipo', 'like', '%Observação%')
        //                 ->orWhere('tipo', 'like', '%Entregue%')
        //                 ->orWhere('tipo', 'like', '%Mudança%');
        //         })->where('usuario_id', '!=', $user->id );
        // }]);

        // $demandas->withCount(['questionamentos as count_respostas' => function ($query)  use($user) {
        //     $query->whereHas('respostas', function ($query)  use($user) {
        //         $query->where('visualizada_ag', 0)->where('usuario_id', '!=', $user->id );
        //     });
        // }]);

        // $demandas->withCount(['questionamentos as count_questionamentos_ag' => function ($query)  use($user){
        //     $query->where('visualizada_ag', 0)
        //         ->where('excluido', null)
        //         ->where(function ($query)  use($user) {
        //             $query->where('tipo', 'like', '%Alteração%')
        //             ->orWhere('tipo', 'like', '%Observação%')
        //             ->orWhere('tipo', 'like', '%Mudança%');
        //         })->where('usuario_id', '!=', $user->id );
        // }]);

        $demandas->withCount(['questionamentos as count_comentarios' => function ($query)  use($user){
            $query->where('visualizada_col', 0)
                ->where('excluido', null)
                ->where('marcado_usuario_id', $user->id);
        }]);

        $demandas->withCount(['prazosDaPauta as count_prazosDaPauta' => function ($query) use ($user) {
            $query->where(function ($query) use ($user) {
                $query->where('demandas.criador_id', $user->id)
                    ->where('aceitar_colaborador', 0)
                    ->where('finalizado', null);
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

        foreach($demandas as $key => $demanda){
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

            $demandasReabertas = $demanda->demandasReabertas;
            if ($demandasReabertas->count() > 0) {
                $sugerido = $demandasReabertas->sortByDesc('id')->first()->sugerido;
                $demanda->final = $sugerido;
            }
        }

        $colaboradores =  MarcaColaborador::whereIn('marca_id', $marcasC)
        ->join('usuarios', 'marcas_colaboradores.usuario_id', '=', 'usuarios.id')
        ->select('usuarios.*')
        ->distinct()
        ->get();

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
            $view = view('ordem-admin', compact('demandas', 'arrayOrdem', 'ordem', 'reset'))->render();
            return response($view)->header('Content-Type', 'text/html');
        }

        return view('Admin/jobs', [
            'demandas' => $demandas,
            'search' => $search,
            'inTime' => $inTime,
            'priority' => $priority,
            'aprovada' => $aprovada,
            'endDate' => $endDate,
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

    public function getCityByStatesAdmin($id){
        if($id){
            $empData['data'] = Cidade::orderby("nome","asc")
               ->select('id','nome')
               ->where('estado_id',$id)
               ->get();

           return response()->json($empData);
       }else{
           return false;
       }
    }


    public function stages(){
        $user = Auth::User();
        $marcasC = MarcaColaborador::where('usuario_id', $user->id)->pluck('marca_id')->toArray();

        $demandas = Demanda::where('etapa_1', 1)->where('etapa_2', 0)->where('excluido', null)->with(['agencia' => function ($query) {
            $query->where('excluido', null);
        }])->whereHas('marcas', function ($query) use ($marcasC) {
            $query->where('marcas.excluido', null)
                ->whereIn('marcas.id', $marcasC);
        })->get();

        if($demandas){
            return view('Admin/etapas', [
                'demandas' => $demandas,
           ]);
        }

        return redirect('/admin');

    }
}
