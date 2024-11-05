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

class Admin8poroitoController extends Controller
{
    //FAZ AJAX NA INDEX DO ADMIN, ESTÁ SENDO USADO EM 2 PUBLICS LOGO ABAIXO
    private function performAjaxRequest(Request $request) {
        $user = Auth::User();

        $coluna = $request->query('coluna');
        $ordem = $request->query('ordem');

        $demandas = Demanda::where('demandas.excluido', null)
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->where('finalizada', 0)
        ->with(['marcas' => function ($query) {
            $query->where('excluido', null);
        }])
        ->with(['agencia' => function ($query) {
            $query->where('excluido', null);
        }])
        ->with(['demandasReabertas' => function ($query) {
            $query->where('finalizado', null);
            $query->where('excluido', null);
        }])
        ->with('demandasUsuario')
        ->with('criador')->with('subCriador');

        if($coluna == '' && $ordem == ''){
            $demandas->leftJoin('demandas_ordem_jobs', function ($join) use ($user) {
                $join->on('demandas.id', '=', 'demandas_ordem_jobs.demanda_id')
                    ->where('demandas_ordem_jobs.usuario_id', '=', $user->id);
            })
            ->select('demandas.*', 'demandas_ordem_jobs.ordem as ordem')
            ->orderByRaw('ISNULL(demandas_ordem_jobs.ordem) ASC, demandas_ordem_jobs.ordem ASC, demandas.id DESC');
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

            $demandas->orderBy(function($query) {
                $query->select('usuarios.nome')
                      ->from('usuarios')
                      ->whereColumn('demandas.criador_id', 'usuarios.id')
                      ->orWhereColumn('demandas.sub_criador_id', 'usuarios.id')
                      ->limit(1);
            }, $orderDirection);

            // $demandas->orderBy(function($query) {
            //     $query->select(\DB::raw("CASE WHEN demandas.sub_criador_id IS NOT NULL THEN subcriador.nome ELSE criador.nome END"))
            //           ->from('usuarios as criador')
            //           ->leftJoin('usuarios as subcriador', 'demandas.sub_criador_id', '=', 'subcriador.id')
            //           ->whereColumn('criador.id', 'demandas.criador_id')
            //           ->orWhereColumn('subcriador.id', 'demandas.sub_criador_id')
            //           ->limit(1);
            // }, $orderDirection);

        }

        if ($coluna == 'marca') {
            $demandas->orderBy(function($query) {
                $query->select('marcas.nome')
                      ->from('marcas')
                      ->join('demandas_marcas', 'marcas.id', '=', 'demandas_marcas.marca_id')
                      ->whereColumn('demandas.id', 'demandas_marcas.demanda_id')
                      ->limit(1);
            }, $orderDirection);
        }
        if ($coluna == 'agencia') {
            $demandas->orderBy(function($query) {
                $query->select('agencias.nome')
                      ->from('agencias')
                      ->whereColumn('demandas.agencia_id', 'agencias.id')
                      ->limit(1);
            }, $orderDirection);
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

        // $finalizadosCount =  Demanda::where('finalizada', '1')->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->count();
        // $pendentesCount = Demanda::where('finalizada', '0')->where('em_pauta', '0')->where('entregue', '0')->where('em_alteracao', '0')->where('finalizada', '0')->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->count();


        $emPautaCount = Demanda::where('em_pauta', '1')->where('finalizada', 0)->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->count();
        $entregueCount =  Demanda::where('entregue', '1')->where('finalizada', '0')->where('pausado', 0)->where('etapa_1', 1)->where('etapa_2', 1)->where('excluido', null)->count();
        $atrasadoCount = Demanda::select('id', 'final', 'finalizada', 'entregue', 'etapa_1', 'etapa_2', 'excluido', 'criador_id')
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

        $currentYear = date('Y');
        $logsCountByMonth = [];

        for ($month = 1; $month <= 12; $month++) {
            $logsCountByMonth[$month] = UsuarioLog::whereYear('criado', $currentYear)->whereMonth('criado', $month)->count();
        }

        $jobsPerMonths = [];

        // $meses = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
        // for ($i = 1; $i <= 12; $i++) {
        //     $month = $meses[$i - 1];
        //     $jobsCriados = Demanda::where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->whereYear('criado', Carbon::now()->year)->whereMonth('criado', $i)->count();

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
            $demandasCriadas[$month] = Demanda::select('id', 'criado', 'finalizada')->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->whereYear('criado', $currentYear)->whereMonth('criado', $month)->count();
            $demandasFinalizadas[$month] = Demanda::select('id', 'criado', 'finalizada')->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->whereYear('criado', $currentYear)->whereMonth('criado', $month)->where('finalizada', 1)->count();
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
        $demandasEvents = Demanda::select('titulo', 'inicio', 'final', 'id', 'cor')->where('etapa_1', 1)->where('etapa_2', 1)
        ->where('entregue', '0')->where('finalizada', '0')->where('excluido', null)->with(['demandasReabertas' => function ($query) {
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

        $creators = User::select('id', 'nome')->where('tipo', 'colaborador')->orWhere('tipo','admin')->where('excluido', null)->get();
        $brands = Marca::select('id', 'nome')->where('excluido', null)->get();
        $agencys = Agencia::select('id', 'nome')->where('excluido', null)->get();
        $users = User::select('id', 'nome')->where('excluido', null)->get();
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
            $view = view('ordem-admin8', compact('demandas', 'arrayOrdem', 'ordem', 'reset'))->render();
            return response($view)->header('Content-Type', 'text/html');
        }

        return view('Admin8/index', [
            'demandas' => $demandas,
            'emPautaCount' => $emPautaCount,
            'entregueCount' => $entregueCount,
            'atrasadoCount' => $atrasadoCount,
            'logsCountByMonth' => $logsCountByMonth,
            'jobsPerMonths' => $jobsPerMonths,
            'events' => $events,
            'ordem' => $ordem,
            'creators' => $creators,
            'brands' => $brands,
            'agencys'=> $agencys,
            'arrayOrdem' => $arrayOrdem,
            'ordemValue' => $ordemValue,
            'users' => $users,
            'resultadosDemanda' => $resultadosDemanda,

        ]);
    }

    // public function ordemAdmin(Request $request){
    //     $user = Auth::User();

    //     $coluna = $request->query('coluna');
    //     $ordem = $request->query('ordem');
    //     $marca = $request->query('marca_id');
    //     $search = $request->query('search');
    //     $dateRange = $request->query('dateRange');
    //     $category_id = $request->query('category_id');
    //     $aprovada = $request->query('aprovada');
    //     $agencia_id = $request->query('agencia_id');
    //     $colaborador_id = $request->query('colaborador_id');
    //     $in_tyme = $request->query('in_tyme');
    //     $ordem_filtro = $request->query('ordem_filtro');

    //     $porpagina = $request->porpagina;

    //     $demandas = $this->performAjaxRequest($request);
    //     $demandas->setPath(url('/admin'));

    //     $ordemjob = DemandaOrdem::where('usuario_id', $user->id)->first();
    //     $arrayOrdem = null;


    //     if($ordemjob){
    //         $arrayOrdem = explode(",", $ordemjob->ordem);
    //     }else{
    //         $arrayOrdem = null;
    //     }

    //     $view = view('ordem-admin8', compact('demandas', 'arrayOrdem', 'ordem'))->render();
    //     return response($view)->header('Content-Type', 'text/html');

    // }



    public function chart(Request $request) {
        $marca = $request->input('marca');
        $agencia = $request->input('agencia');
        $usuario = $request->input('usuario');
        $dataGrafico = $request->input('dataGrafico');
        $dataAtual = date('Y-m-d H:i:s');

        $emPautaQuery = Demanda::where('em_pauta', '1')
        ->where('finalizada', 0)
        ->where('excluido', null)
        ->where('etapa_1', 1)
        ->where('etapa_2', 1);

        $entregueQuery = Demanda::where('entregue', '1')
        ->where('finalizada', '0')
        ->where('pausado', 0)
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->where('excluido', null);

        $atrasadoQuery = Demanda::select('id', 'final', 'finalizada', 'entregue', 'etapa_1', 'etapa_2', 'excluido', 'criador_id')
        ->where('finalizada', '0')
        ->where('entregue', '0')
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->where('excluido', null)
        ->with(['demandasReabertas' => function ($query) {
            $query->where('finalizado', null);
            $query->where('excluido', null);
        }])->where('final', '<', $dataAtual);



        if($marca){
            AplyFilters::applyFilters($emPautaQuery, $marca);
            AplyFilters::applyFilters($entregueQuery, $marca);
            AplyFilters::applyFilters($atrasadoQuery, $marca);
        }

        if($agencia){
            AplyFilters::applyFilters($emPautaQuery, null, $agencia);
            AplyFilters::applyFilters($entregueQuery, null, $agencia);
            AplyFilters::applyFilters($atrasadoQuery, null, $agencia);
        }

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

            return response()->json($data);
    }

    public function jobs(Request $request){
        $user = Auth::User();
        $search = $request->search;
        $jobId = $request->jobId;
        $aprovada = $request->aprovada;
        $inTime = $request->in_tyme;
        $priority = $request->category_id;
        $marca = $request->marca_id;
        $endDate = $request->endDateInput;
        $agencia = $request->agencia_id;
        $dateRange = $request->dateRange;
        $colaborador = $request->colaborador_id;
        $ordem_filtro = $request->ordem_filtro;
        $porpagina = $request->input('porpagina', 15);

        $coluna = $request->query('coluna');
        $ordem = $request->query('ordem');


        $demandas = Demanda::where('demandas.excluido', null)
        ->where('etapa_1', 1)
        ->where('etapa_2', 1)
        ->with(['marcas' => function ($query) {
            $query->where('excluido', null);
        }])
        ->with(['agencia' => function ($query) {
            $query->where('excluido', null);
        }])
        ->with(['demandasReabertas' => function ($query) {
            $query->where('finalizado', null);
            $query->where('excluido', null);
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

            $demandas->orderBy(function($query) {
                $query->select('usuarios.nome')
                      ->from('usuarios')
                      ->whereColumn('demandas.criador_id', 'usuarios.id')
                      ->orWhereColumn('demandas.sub_criador_id', 'usuarios.id')
                      ->limit(1);
            }, $orderDirection);

        }

        if ($coluna == 'marca') {
            $demandas->orderBy(function($query) {
                $query->select('marcas.nome')
                      ->from('marcas')
                      ->join('demandas_marcas', 'marcas.id', '=', 'demandas_marcas.marca_id')
                      ->whereColumn('demandas.id', 'demandas_marcas.demanda_id')
                      ->limit(1);
            }, $orderDirection);
        }

        if ($coluna == 'agencia') {
            $demandas->orderBy(function($query) {
                $query->select('agencias.nome')
                      ->from('agencias')
                      ->whereColumn('demandas.agencia_id', 'agencias.id')
                      ->limit(1);
            }, $orderDirection);
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

        if($marca != '0' && $marca){
            $demandas->whereHas('marcas', function($query)  use($marca){
                $query->where('marcas.id', $marca);
                $query->where('marcas.excluido', null);
            });
        }

        if($agencia != '0' && $agencia){
            $demandas->whereHas('agencia', function($query)  use($agencia){
                $query->where('agencias.id', $agencia);
                $query->where('agencias.excluido', null );
            });
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


        $perPage = $request->input('porpagina', 15);

        $demandas = $demandas->paginate($perPage)->withQueryString();

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

        $brands = Marca::where('excluido', null)->get();

        $agencies = Agencia::where('excluido', null)->get();

        $colaboradores = User::select('id', 'nome')->whereIn('tipo', ['colaborador', 'admin'])->get();

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
            $view = view('ordem-admin8', compact('demandas', 'arrayOrdem', 'ordem', 'reset'))->render();
            return response($view)->header('Content-Type', 'text/html');
        }

        return view('Admin8/jobs', [
            'demandas' => $demandas,
            'search' => $search,
            'inTime' => $inTime,
            'priority' => $priority,
            'aprovada' => $aprovada,
            'brands' => $brands,
            'marca' => $marca,
            'agencies' => $agencies,
            'endDate' => $endDate,
            'agencia' => $agencia,
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

    public function agency(){
        return view('Admin8/Agencia/adicionar', [
        ]);
    }

    public function agencyCreate(Request $request){

        $validator = Validator::make($request->all(),[
            'nome' => 'required|min:3',
            'logo' => 'mimes:jpg,jpeg,png,bmp'

            ],[
                'nome.required' => 'Preencha o campo nome.',
                'logo.mimes' => 'Somente imagens jpeg, jpg, png e bmp são permitidas',
            ]
        );

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        if(!$validator->fails()){
            $createAgency = new Agencia();
            $createAgency->nome = $request->nome;
            $createAgency->criado = date('Y-m-d H:i:s');

            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $extension = $request->file('logo')->extension();

                $dest = public_path('assets/images/agency');
                $photoName = md5(time().rand(0,9999)).'.'.$extension;

                $img = Image::make($logo->getRealPath());
                $img->fit(128, 128)->save($dest.'/'.$photoName);

                $createAgency->logo = $photoName;

            }

            $createAgency->save();

            return response()->json([
                'success' => true,
                'message' => 'Agência criada com sucesso.'
            ], 200);

        }

    }

    public function brand(){

        return view('Admin8/Marca/adicionar', [

        ]);
    }


    public function brandCreate(Request $request){

        $validator = Validator::make($request->all(),[
            'nome' => 'required|min:3',
            'logo' => 'mimes:jpg,jpeg,png,bmp'
            ],[
                'nome.required' => 'Preencha o campo nome.',
                'logo.mimes' => 'Somente imagens jpeg, jpg, png e bmp são permitidas',
            ]
        );

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        if(!$validator->fails()){
            $createBrand = new Marca();
            $createBrand->nome = $request->nome;
            $createBrand->criado = date('Y-m-d H:i:s');
            $createBrand->cor = $request->cor;

            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $extension = $request->file('logo')->extension();

                $dest = public_path('assets/images/brands');
                $photoName = md5(time().rand(0,9999)).'.'.$extension;

                $img = Image::make($logo->getRealPath());
                $img->save($dest.'/'.$photoName);

                $createBrand->logo = $photoName;

            }

            $createBrand->save();


            return response()->json([
                'success' => true,
                'message' => 'Marca criada com sucesso.'
            ], 200);

        }

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

    public function user(){
        $agencias = Agencia::where('excluido', null)->get();
        $marcas = Marca::where('excluido', null)->get();
        $estados = Estado::all();
        return view('Admin8/Usuario/adicionar', [
            'agencias' => $agencias,
            'marcas' => $marcas,
            'estados' => $estados
        ]);
    }

    public function userCreate(Request $request){

        $validator = Validator::make($request->all(), [
            'nome' => 'required',
            'email' => 'required|email|unique:usuarios',
            'tipo' => 'required',
            'password' => 'nullable|min:3|confirmed',
            'password_confirmation' => 'nullable|min:3',
            'estado_id' => 'required',
            'cidade_id' => 'required',
        ]);

        $commonMessages = [
            'nome.required' => 'Preencha o campo nome.',
            'email.required' => 'Preencha o campo email.',
            'email.unique' => 'Este endereço de e-mail já está sendo usado.',
            'tipo.required' => 'Preencha o campo tipo.',
            'password.min' => 'A senha deve ter pelo menos 3 caracteres.',
            'password_confirmation.min' => 'As senhas devem ser iguais.',
            'password.confirmed' => 'As senhas devem ser iguais.',
            'estado_id.required' => 'Preencha o campo estado.',
            'cidade_id.required' => 'Preencha o campo cidade.',
        ];

        if($request->marcas){
            $marcasCount = MarcaUsuario::whereIn('marca_id', $request->marcas)->count();
            if($marcasCount > 0){
                return response()->json([
                    'success' => false,
                    'message' => 'Já existe um usuário que pertence a essa marca.',
                ], 404);
            }
        }


        if ($request->tipo == 'colaborador' || $request->tipo == 'admin' ) {
            $validator->addRules([
                'marcaColaborador' => 'required',
            ]);

            $validator->setCustomMessages([
                'marcaColaborador.required' => 'Preencha o campo marca.',
            ]);
        }elseif($request->tipo == 'agencia'){
            $validator->addRules([
                'marcas' => 'required',
            ]);

            $validator->setCustomMessages([
                'marcas.required' => 'Preencha o campo marcas.',
            ]);
        }

        $validator->setCustomMessages($commonMessages);

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 404);
        }

        if(!$validator->fails()){

            $createUser = new User();
            $createUser->nome = $request->nome;
            $createUser->email = $request->email;
            $createUser->tipo = $request->tipo;

            //VERIFICAR ALTERAÇAO AQUI

            $createUser->criado = date('Y-m-d H:i:s');

            if($request->password && $request->password_confirmation){
                if($request->password == $request->password_confirmation){
                    $newPassword = Hash::make($request->password);
                    $createUser->password = $newPassword;
                }
            }

            if ($request->hasFile('logo')) {
                $logo = $request->file('logo');
                $extension = $request->file('logo')->extension();

                $dest = public_path('assets/images/users');
                $photoName = md5(time().rand(0,9999)).'.'.$extension;

                $img = Image::make($logo->getRealPath());
                $img->fit(128, 128)->save($dest.'/'.$photoName);
                $createUser->avatar = $photoName;

            }

            $createUser->save();

            if($request->tipo == 'colaborador' || $request->tipo == 'admin'){
                // $createUser->marca = $request->singleMarca;
                foreach($request->marcaColaborador as $item){
                    $brandsColaborador = new MarcaColaborador();
                    $brandsColaborador->marca_id = $item;
                    $brandsColaborador->usuario_id = $createUser->id;
                    $brandsColaborador->save();
                }
            }

            $createInfoUser = new InformacaoUsuario();
            $createInfoUser->usuario_id = $createUser->id;
            $createInfoUser->cidade_id = $request->cidade_id;
            $createInfoUser->estado_id = $request->estado_id;
            $createInfoUser->save();


            if($request->tipo == 'agencia'){

                if($request->adminAg == true){
                    $adminAg = new AdminAgencia();
                    $adminAg->usuario_id =  $createUser->id;
                    $adminAg->save();
                }

                foreach($request->marcas as $item){
                    $brandsUser = new MarcaUsuario();
                    $brandsUser->marca_id = $item;
                    $brandsUser->usuario_id = $createUser->id;
                    $brandsUser->save();
                }

                $agencyUser = new AgenciaUsuario();
                $agencyUser->usuario_id = $createUser->id;
                $agencyUser->agencia_id = '1';
                $agencyUser->save();
            }


            return response()->json([
                'success' => true,
                'message' => 'Usuário criado com sucesso.'
            ], 200);

        }

    }

    public function agencysAll(Request $request){
        $search = $request->search;

        $agencias = Agencia::where('excluido', null);

        if($search){
            $agencias->where('nome', 'like', "%$search%");
        }

        $agencias = $agencias->paginate(25)->withQueryString();
        return view('Admin8/Agencia/index', [
            'agencias' => $agencias,
            'search' => $search,
        ]);
    }

    public function brandsAll(Request $request){
        $search = $request->search;

        $marcas = Marca::where('excluido', null);

        if($search){
            $marcas->where('nome', 'like', "%$search%");
        }

        $marcas = $marcas->paginate(25)->withQueryString();


        return view('Admin8/Marca/index', [
            'marcas' => $marcas,
            'search' => $search,
        ]);
    }

    public function usersAll(Request $request){
        $search = $request->search;

        $usuarios = User::where('excluido', null)->where('tipo', '!=', 'ghost');

        if($search){
            $usuarios->where('nome', 'like', "%$search%");
        }

        $usuarios = $usuarios->paginate(25)->withQueryString();

        return view('Admin8/Usuario/index', [
            'usuarios' => $usuarios,
            'search' => $search,
        ]);
    }

    public function agencyEdit($id){

        $agencia = Agencia::where('id', $id)->where('excluido', null)->first();

        return view('Admin8/Agencia/agencia', [
            'agencia' => $agencia
        ]);
    }

    public function brandEdit($id){
        $marca = Marca::where('id', $id)->where('excluido', null)->first();

        return view('Admin8/Marca/marca', [
            'marca' => $marca
        ]);
    }

    public function userEdit($id){
        $usuario = User::where('id', $id)->where('excluido', null)->with('marcas')->with('usuariosAgencias')->with('colaboradoresAgencias')->withCount(['adminUserAgencia as count_userAg' => function ($query) {
            $query->where('excluido', null);
        }])->first();

        $idsBrands = $usuario->marcas->pluck('id')->toArray();
        $idsBrandsCol = $usuario->marcasColaborador->pluck('id')->toArray();

        $cidades = Cidade::select('id', 'nome', 'estado_id')->where('estado_id', $usuario['estado'][0]->id)->get();
        $estados = Estado::all();
        $marcas = Marca::where('excluido', null)->get();

        return view('Admin8/Usuario/usuario', [
            'user' => $usuario,
            'estados' => $estados,
            'cidades' => $cidades,
            'marcas' => $marcas,
            'idsBrands' => $idsBrands,
            'idsBrandsCol' => $idsBrandsCol
        ]);
    }

    public function agencyEditAction(Request $request, $id){

        $validator = Validator::make($request->all(),[
            'nome' => 'required|min:3',
            'logo' => 'mimes:jpg,jpeg,png,bmp'

            ],[
                'nome.required' => 'Preencha o campo nome.',
                'logo.mimes' => 'Somente imagens jpeg, jpg, png e bmp são permitidas',
            ]
        );

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        if(!$validator->fails()){

            $agencia = Agencia::where('id', $id)->where('excluido', null)->first();

            if($id){

                if($request->nome){
                    $agencia->nome = $request->nome;
                }

                if ($request->hasFile('logo')) {
                    File::delete(public_path("/assets/images/agency/".$agencia->logo));
                    $dest = public_path('assets/images/agency');
                    $extension = $request->file('logo')->extension();
                    $photoName = md5(time().rand(0,9999)).'.'.$extension;

                    $img = Image::make($request->logo->getRealPath());
                    $img->fit(128, 128)->save($dest.'/'.$photoName);
                    $agencia->logo = $photoName;
                }

                $agencia->save();
            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Agência não foi encontrada.'
                ], 200);
            }
            return response()->json([
                'success' => true,
                'message' => 'Agência editada com sucesso.'
            ], 200);

        }
    }

    public function brandEditAction(Request $request, $id){
         $validator = Validator::make($request->all(),[
            'nome' => 'required|min:3',
            'cor' => 'required',
            'logo' => 'mimes:jpg,jpeg,png,bmp'

            ],[
                'nome.required' => 'Preencha o campo nome.',
                'cor.required' => 'Preencha o campo cor.',
                'logo.mimes' => 'Somente imagens jpeg, jpg, png e bmp são permitidas',
            ]
        );

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }

        if(!$validator->fails()){

            $marca = Marca::where('id', $id)->where('excluido', null)->first();

            if($id){

                if($request->nome){
                    $marca->nome = $request->nome;
                }

                if($request->cor){
                    $marca->cor = $request->cor;
                }

                if ($request->hasFile('logo')) {
                    File::delete(public_path("/assets/images/brands/".$marca->logo));
                    $dest = public_path('assets/images/brands');
                    $extension = $request->file('logo')->extension();
                    $photoName = md5(time().rand(0,9999)).'.'.$extension;

                    $img = Image::make($request->logo->getRealPath());
                    $img->save($dest.'/'.$photoName);
                    $marca->logo = $photoName;
                }

                $marca->save();

            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Essa marca não foi encontrada.'
                ], 400);

            }

            return response()->json([
                'success' => true,
                'message' => 'Marca editada com sucesso.'
            ], 200);

        }
    }

    public function userEditAction(Request $request, $id){
        $user = User::where('id', $id)->where('excluido', null)->first();
        // $errorDemanda = false;
        // $verifyErrorMarca = '';
        // $verifyErrorAg = '';
        $validator = Validator::make($request->all(), [
            'nome' => 'required|min:3',
            'password' => 'nullable|min:3|confirmed',
            'password_confirmation' => 'nullable|min:3',
            'estado_id' => 'required',
            'cidade_id' => 'required',
            'avatar' => 'mimes:jpg,jpeg,png',
        ]);

        $commonMessages = [
            'nome.required' => 'Preencha o campo nome.',
            'nome.min' => 'O campo nome deve ter pelo menos 3 caracteres.',
            'password.min' => 'A senha deve ter pelo menos 3 caracteres.',
            'password_confirmation.min' => 'As senhas devem ser iguais.',
            'password.confirmed' => 'As senhas devem ser iguais.',
            'estado_id.required' => 'Preencha o campo estado.',
            'cidade_id.required' => 'Preencha o campo cidade.',
            'avatar.mimes' => 'Somente imagens jpeg, jpg e png são permitidas.',
        ];

        if ($user->tipo == 'admin' || $user->tipo == 'colaborador') {
            $validator->addRules([
                'marcaColaborador' => 'required',
            ]);

            $validator->setCustomMessages([
                'marcaColaborador.required' => 'Preencha o campo marca.',
            ]);
        }

        if ($user->tipo == 'agencia') {
            $validator->addRules([
                'marcas' => 'required',
            ]);

            $validator->setCustomMessages([
                'marcas.required' => 'Preencha o campo marcas.',
            ]);
        }


        $validator->setCustomMessages($commonMessages);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);
        }
        else{

            $infoUser = InformacaoUsuario::where('usuario_id', $user->id)->first();

            if($user){
                if($request->nome){
                    $user->nome = $request->nome;
                    $user->save();
                }

                if($request->password && $request->password_confirmation){
                    if($request->password == $request->password_confirmation){
                        $newPassword = Hash::make($request->password);
                        $user->password = $newPassword;
                        $user->save();
                    }
                }

                if($request->estado_id){
                    $infoUser->estado_id = $request->estado_id;
                    $infoUser->save();
                }

                if($request->cidade_id){
                    $infoUser->cidade_id = $request->cidade_id;
                    $infoUser->save();
                }

                if ($request->hasFile('avatar')) {
                    $avatar = $request->file('avatar');
                    $extension = $request->file('avatar')->extension();
                    if($user->avatar !== 'default.jpg'){
                        File::delete(public_path("/assets/images/users/".$user->avatar));
                    }
                    $dest = public_path('assets/images/users');
                    $photoName = md5(time().rand(0,9999)).'.'.$extension;

                    $img = Image::make($avatar->getRealPath());
                    $img->fit(128, 128)->save($dest.'/'.$photoName);

                    $user->avatar = $photoName;
                    $user->save();

                }

                if($user->tipo == 'agencia'){
                    if($request->marcas){
                        $marcasCount = MarcaUsuario::whereIn('marca_id', $request->marcas)->where('usuario_id', '!=', $user->id)->count();
                        if($marcasCount > 0){
                            return response()->json([
                                'success' => false,
                                'message' => 'Já existe um usuário que pertence a essa marca.',
                            ], 404);
                        }

                        $marcasAtuais = MarcaUsuario::where('usuario_id', $user->id)->pluck('marca_id')->toArray();

                        $marcasParaRemover = array_diff($marcasAtuais, $request->marcas);

                        MarcaUsuario::where('usuario_id', $user->id)
                            ->whereIn('marca_id', $marcasParaRemover)
                            ->delete();

                        foreach($request->marcas as $item){
                            $brandsUser = MarcaUsuario::updateOrCreate([
                            'marca_id' => $item,
                            'usuario_id' => $user->id
                            ], [
                                'marca_id' => $item,
                                'usuario_id' => $user->id
                            ]);

                        }

                        //admin agencia fazendo verificação no input switch
                        $adminAg = AdminAgencia::where('usuario_id', $user->id)->first();
                        if($request->adminAg == false && $adminAg){
                            $adminAg->excluido = date('Y-m-d H:i:s');
                            $adminAg->save();
                        }else if($request->adminAg == true && $adminAg){
                            $adminAg->excluido = null;
                            $adminAg->save();
                        }else{
                            $newAdminAg = new AdminAgencia();
                            $newAdminAg->usuario_id = $user->id;
                            $newAdminAg->save();
                        }

                    }
                }

                if($user->tipo == 'colaborador' || $user->tipo == 'admin'){
                    if($request->marcaColaborador){
                        $marcasAtuais = MarcaColaborador::where('usuario_id', $user->id)->pluck('marca_id')->toArray();

                        $marcasParaRemover = array_diff($marcasAtuais, $request->marcaColaborador);

                        MarcaColaborador::where('usuario_id', $user->id)
                            ->whereIn('marca_id', $marcasParaRemover)
                            ->delete();

                        foreach($request->marcaColaborador as $item){
                            $brandsCol = MarcaColaborador::updateOrCreate([
                            'marca_id' => $item,
                            'usuario_id' => $user->id
                            ], [
                                'marca_id' => $item,
                                'usuario_id' => $user->id
                            ]);
                        }
                    }
                }

                return response()->json([
                        'success' => true,
                        'message' => 'Editado com sucesso.'
                ], 200);


                // $agencyUser = AgenciaUsuario::where('usuario_id', $user->id)->first();
                // $userHasUnfinishedDemands = $user->usuarioDemandas()
                // ->where('finalizada', 0)
                // ->where('excluido', null)
                // ->exists();

                // if (!$userHasUnfinishedDemands) {
                //     // Se todas as demandas do usuário estão finalizadas, adicione o código para criar ou atualizar $ageUser.
                //     //remover agencia
                //     if($request->agencia == null){
                //         AgenciaUsuario::where('usuario_id', $user->id)->delete();
                //     }else{
                //         $ageUser = AgenciaUsuario::updateOrCreate([
                //             'usuario_id' => $user->id
                //         ], [
                //             'agencia_id' => $request->agencia,
                //             'usuario_id' => $user->id,
                //         ]);
                //     }

                // }else if($userHasUnfinishedDemands && $request->agencia != $agencyUser->agencia_id ) {
                //     $verifyErrorAg = 'error';
                // }


                // if($user->tipo == 'colaborador' || $user->tipo == 'admin'){

                //     //verificacao e jobs

                //     if($request->marcas){
                //         $verifyErrorMarca = $this->helpUserAdminAge($user->id, $request->marcas);
                //     }

                //     if($request->agencias_colaboradores){
                //         $colaboradorAgencia = AgenciaColaborador::select('agencia_id')->where('usuario_id', $user->id)->get();

                //         foreach($colaboradorAgencia as $item){
                //             // Verifica se há demandas associadas à agência
                //             $hasDemandas = Demanda::where('criador_id', $user->id)->where('excluido', null)->where('agencia_id', $item->agencia_id)->exists();

                //             // Verifica se a agência não está presente em $request->agencias_colaboradores e também não está na cláusula whereNotIn em agencia_id
                //             if (!$hasDemandas && !in_array($item->agencia_id, $request->agencias_colaboradores)) {
                //                 // Remove a agência não presente em $request->agencias_colaboradores
                //                 $agencia = AgenciaColaborador::where('usuario_id', $user->id)->where('agencia_id', $item->agencia_id)->delete();
                //             } else if ($hasDemandas && !in_array($item->agencia_id, $request->agencias_colaboradores)) {
                //                 $errorDemanda = true; // Marca a ocorrência do erro
                //             }
                //         }

                //         foreach($request->agencias_colaboradores as $ag){
                //             $brandsUser = AgenciaColaborador::updateOrCreate([
                //                 'agencia_id' => $ag,
                //                 'usuario_id' => $user->id
                //             ], [
                //                 'agencia_id' => $ag,
                //                 'usuario_id' => $user->id,
                //             ]);
                //         }

                //         if ($errorDemanda) {
                //             $verifyErrorAg = 'error';
                //         }
                //     }

                // }else if($user->tipo == 'agencia'){

                //     //admin agencia fazendo verificação no input switch
                //     $adminAg = AdminAgencia::where('usuario_id', $user->id)->first();
                //     if($request->adminAg == false && $adminAg){
                //         $adminAg->excluido = date('Y-m-d H:i:s');
                //         $adminAg->save();
                //     }else if($request->adminAg == true && $adminAg){
                //         $adminAg->excluido = null;
                //         $adminAg->save();
                //     }else{
                //         $newAdminAg = new AdminAgencia();
                //         $newAdminAg->usuario_id = $user->id;
                //         $newAdminAg->save();
                //     }


                //     if($request->marcas){
                //       $verifyErrorMarca = $this->helpUserAdminAge($user->id, $request->marcas);
                //     }

                // }

                // if ($verifyErrorAg == 'error' && $verifyErrorMarca == 'error') {
                //     return response()->json([
                //         'success' => false,
                //         'message' => 'Existem erros nas informações fornecidas. (Marca e Agência)',
                //     ], 400);

                // } else if ($verifyErrorAg == 'error') {
                //     return response()->json([
                //         'success' => false,
                //         'message' => 'Você não pode mudar a agência, pois já existe um job cadastrado nessa agência',
                //     ], 400);

                // } else if ($verifyErrorMarca == 'error') {
                //     return response()->json([
                //         'success' => false,
                //         'message' => 'Você não pode mudar de marca, pois já existe um job cadastrado nessa marca.',
                //     ], 400);
                // } else {
                //     return response()->json([
                //         'success' => true,
                //         'message' => 'Editado com sucesso.'
                //     ], 200);

                // }

            }
        }
    }

    // public function helpUserAdminAge($userId, $requestM){
    //     $marcasColaborador = MarcaUsuario::select('marca_id')->where('usuario_id', $userId)->get();
    //     $demandasByUser = Demanda::select('id')->where('criador_id', $userId)->get();
    //     $idsDemandas = [];
    //     $erroDemanda = false;

    //     foreach($demandasByUser as $d){
    //         array_push($idsDemandas, $d->id);
    //     }

    //     foreach($marcasColaborador as $item){
    //         // Verifica se há demandas associadas à agência
    //         $hasDemandas = DemandaMarca::where('marca_id', $item->marca_id)->whereIn('demanda_id', $idsDemandas)->exists();

    //         // Verifica se a agência não está presente em $request->agencias_colaboradores e também não está na cláusula whereNotIn em agencia_id
    //         if (!$hasDemandas && !in_array($item->marca_id, $requestM)) {
    //             // Remove a agência não presente em $request->agencias_colaboradores
    //             $marca = MarcaUsuario::where('usuario_id', $userId)->where('marca_id', $item->marca_id)->delete();
    //         } else if ($hasDemandas && !in_array($item->marca_id, $requestM)) {
    //             $erroDemanda = true; // Marca a ocorrência do erro
    //         }
    //     }

    //     foreach($requestM as $item){

    //         $brandsUser = MarcaUsuario::updateOrCreate([
    //         'marca_id' => $item,
    //         'usuario_id' => $userId
    //         ], [
    //             'marca_id' => $item,
    //             'usuario_id' => $userId
    //         ]);

    //     }

    //     if ($erroDemanda) {
    //        return 'error';
    //     }

    // }

    //delete

    public function agencyDelete($id){
        $excAgency = Agencia::where('id', $id)->where('excluido', null)->first();
        if($excAgency){
            $excAgency->excluido = date('Y-m-d H:i:s');
            $excAgency->save();
            return response()->json([
                'success' => true,
                'message' => 'Agência excluida com sucesso.'
            ], 200);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Esse agência não pode ser excluida'
            ], 200);

        }
    }

    public function brandDelete($id){
        $excBrand = Marca::where('id', $id)->where('excluido', null)->first();
        if($excBrand){
            $excBrand->excluido = date('Y-m-d H:i:s');
            $excBrand->save();
            return response()->json([
                'success' => true,
                'message' => 'Marca excluida com sucesso.'
            ], 200);
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Esse marca não pode ser excluida'
            ], 200);
        }
    }

    public function UserDelete($id){
        $user = Auth::User();
        $excUser = User::where('id', $id)->where('excluido', null)->first();
        if($excUser){
            $excUser->excluido = date('Y-m-d H:i:s');
            $excUser->save();
            if($user->id == $excUser->id){
                Auth::logout();
                return response()->json([
                    'success' => true,
                    'message' => 'Usuário excluído com sucesso',
                    'redirect' => route('/login')
                ], 200);
            }else{
                return response()->json([
                    'success' => true,
                    'message' => 'Usuário excluído com sucesso',
                ], 200);
            }
        }else{
            return response()->json([
                'success' => false,
                'message' => 'Esse usuário não pode ser excuído.',
            ], 400);

        }
    }

    public function agencysGraphs($id){
        $user = Auth::User();
        $agencia = Agencia::where('id', $id)->where('excluido', null)->first();

        //média geral
        $demandasTempos = DemandaTempo::where('agencia_id', $id)->where('finalizado', '!=', null)->get();
        $diferencasEmDias = [];

        foreach ($demandasTempos as $item) {
            $iniciado = Carbon::parse($item->iniciado);
            $finalizado = Carbon::parse($item->finalizado);

            // filtra os dias úteis (segunda a sexta)
            if ($finalizado->diffInHours($iniciado) < 24) {
                $diferencaEmDias = 0.5;
            } else {
                $diferencaEmHoras = $finalizado->diffInHoursFiltered(function($date) {
                    // verifica se a data é um final de semana (sábado ou domingo)
                    if ($date->isWeekend()) {
                        return false;
                    }

                    // lista de feriados do Brasil
                    $feriados = [
                        '01-01', // Ano Novo
                        '21-04', // Tiradentes
                        '01-05', // Dia do Trabalho
                        '07-09', // Independência do Brasil
                        '12-10', // Nossa Senhora Aparecida
                        '02-11', // Dia de Finados
                        '15-11', // Proclamação da República
                        '25-12', // Natal
                    ];

                    // verifica se a data é um feriado
                    $diaMes = $date->format('d-m');
                    return !in_array($diaMes, $feriados);
                }, $iniciado, true);

                $diferencaEmDias = $diferencaEmHoras / 24;
            }

            $diferencaEmDias = number_format($diferencaEmDias, 2, '.', '');
            $diferencasEmDias[] = $diferencaEmDias;
            $item->diferencaEmDias = $diferencaEmDias;
        }

        $media = count($diferencasEmDias) > 0 ? array_sum($diferencasEmDias) / count($diferencasEmDias) : 0;
        // $media = round($media, 0); // arredonda para o número inteiro mais próximo
        // $media = floor($media); // arredonda para baixo
        $media = number_format($media, 1); // formata o número com uma casa decimal
        // if($media < 1){
        //     $media = number_format($media, 1); // formata o número com uma casa decimal
        //  }else{
        //   $media = round($media, 0); // arredonda para o número inteiro mais próximo
        //   $media = floor($media); // arredonda para baixo
        //  }

        //média em meses
        $currentYear = date('Y');
        $daysCountByMonth = [];

        for ($month = 1; $month <= 12; $month++) {
            $daysCountByMonth[$month] = DemandaTempo::select('criado', 'iniciado', 'finalizado')->where('agencia_id', $id)->whereYear('criado', $currentYear)->whereMonth('criado', $month)->where('finalizado', '!=', null)->get();
            $demandasCriadas[$month] = Demanda::select('id', 'criado', 'finalizada')->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->where('agencia_id', $id)->whereYear('criado', $currentYear)->whereMonth('criado', $month)->count();
            $demandasFinalizadas[$month] = Demanda::select('id', 'criado', 'finalizada')->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->where('agencia_id', $id)->whereYear('criado', $currentYear)->whereMonth('criado', $month)->where('finalizada', 1)->count();
            $demandasPrazo[$month] = Demanda::select('id', 'atrasada')->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->where('agencia_id', $id)->whereYear('criado', $currentYear)->whereMonth('criado', $month)->where('atrasada', 0)->where('finalizada', 1)->count();
            $demandasAtrasada[$month] = Demanda::select('id', 'atrasada')->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->where('agencia_id', $id)->whereYear('criado', $currentYear)->whereMonth('criado', $month)->where('atrasada', 1)->where('finalizada', 1)->count();
        }

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

        $mediaMeses = [];

        foreach ($daysCountByMonth as $indice => $array) {
            if (!empty($array)) {
                $totalDias = 0;
                $qtdArrays = count($array);
                foreach ($array as $item) {
                    $iniciado = Carbon::parse($item['iniciado']);
                    $finalizado = Carbon::parse($item['finalizado']);

                    if ($finalizado->diffInHours($iniciado) < 24) {
                        $diferencaEmDias = 0.5;
                    } else {
                        $diferencaEmHoras = $finalizado->diffInHoursFiltered(function($date) {
                            // verifica se a data é um final de semana (sábado ou domingo)
                            if ($date->isWeekend()) {
                                return false;
                            }

                            // lista de feriados do Brasil
                            $feriados = [
                                '01-01', // Ano Novo
                                '21-04', // Tiradentes
                                '01-05', // Dia do Trabalho
                                '07-09', // Independência do Brasil
                                '12-10', // Nossa Senhora Aparecida
                                '02-11', // Dia de Finados
                                '15-11', // Proclamação da República
                                '25-12', // Natal
                            ];

                            // verifica se a data é um feriado
                            $diaMes = $date->format('d-m');
                            return !in_array($diaMes, $feriados);
                        }, $iniciado, true);
                        $diferencaEmDias = $diferencaEmHoras / 24;
                    }

                    $diferencaEmDias = number_format($diferencaEmDias, 2, '.', '');
                    $totalDias += $diferencaEmDias;
                }

                $mediaM = $qtdArrays > 0 ? $totalDias / $qtdArrays : 0; // Verifica se $qtdArrays é maior que 0 antes de fazer a divisão

                $mediaM = number_format($mediaM, 1);
                // if($mediaM < 1){
                //    $mediaM = number_format($mediaM, 1); // formata o número com uma casa decimal
                // }else{
                //  $mediaM = round($mediaM, 0); // arredonda para o número inteiro mais próximo
                //  $mediaM = floor($mediaM); // arredonda para baixo
                // }

                $mediaMeses[] = [
                    'mes' => $meses[Carbon::createFromFormat('!m', $indice)->format('M')], // Obtém o nome do mês a partir do número do índice
                    'dias' => $mediaM
                ];

            } else {
                $mediaMeses[] = [
                    'mes' => $meses[Carbon::createFromFormat('!m', $indice)->format('M')],
                    'dias' => 0
                ];
            }
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

        //demandas
        $demandas = Demanda::where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->where('agencia_id', $id)
        ->whereHas('agencia', function ($query) {
            $query->where('excluido', null);
        })->with(['demandasReabertas' => function ($query) {
            $query->where('finalizado', null);
            $query->where('excluido', null);
        }])->with(['marcas' => function ($query) {
            $query->where('excluido', null);
        }])->with('demandasUsuario')->withCount(['questionamentos as count_questionamentos' => function ($query) {
            $query->where('visualizada_col', 0)->where('excluido', null)->where(function ($query) {
                $query->where('tipo', 'like', '%Questionamento%')
                ->orWhere('tipo', 'like', '%Observação%')
                ->orWhere('tipo', 'like', '%Entregue%')
                ->orWhere('tipo', 'like', '%Mudança%');
            });
        }])->leftJoin('demandas_ordem_jobs', function ($join) use ($user) {
            $join->on('demandas.id', '=', 'demandas_ordem_jobs.demanda_id')
                ->where('demandas_ordem_jobs.usuario_id', '=', $user->id);
        })
        ->select('demandas.*', 'demandas_ordem_jobs.ordem as ordem')
        ->orderByRaw('ISNULL(demandas_ordem_jobs.ordem) ASC, demandas_ordem_jobs.ordem ASC, demandas.id DESC')->paginate(15);
        //s
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

        //counts
        $demandasCount = Demanda::where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->where('agencia_id', $id)->where('finalizada', 1)->whereYear('criado', $currentYear)->count();
        $demandasAtrasadasCount = Demanda::where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->where('agencia_id', $id)->where('atrasada', 1)->whereYear('criado', $currentYear)->where('finalizada', 1)->count();
        $demandasEmPrazoCount = Demanda::where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->where('agencia_id', $id)->where('atrasada', 0)->whereYear('criado', $currentYear)->where('finalizada', 1)->count();

        //demandas Atrasadas e no prazo
        $demandasMesesAtrasadas = [];
        foreach ($demandasAtrasada as $indice => $array) {
            if (!empty($array)) {
                $demandasMesesAtrasadas[] = [
                    'mes' => $meses[Carbon::createFromFormat('!m', $indice)->format('M')],
                    'atrasadas' => $array
                ];
            } else {
                $demandasMesesAtrasadas[] = [
                    'mes' => $meses[Carbon::createFromFormat('!m', $indice)->format('M')],
                    'atrasadas' => 0
                ];
            }
        }

        $demandasMesesNoPrazo = [];
        foreach ($demandasPrazo as $indice => $array) {
            if (!empty($array)) {
                $demandasMesesNoPrazo[] = [
                    'mes' => $meses[Carbon::createFromFormat('!m', $indice)->format('M')],
                    'prazo' => $array
                ];
            } else {
                $demandasMesesNoPrazo[] = [
                    'mes' => $meses[Carbon::createFromFormat('!m', $indice)->format('M')],
                    'prazo' => 0
                ];
            }
        }

        $resultadosDemandaPrazos = [];
        //juntar atrasadas e no prazo
        foreach($demandasMesesAtrasadas as $c){
            foreach($demandasMesesNoPrazo as $f){
                if($c['mes'] == $f['mes']){
                    $resultadosDemandaPrazos[] = [
                        "mes" => $c['mes'],
                        'atrasadas' => $c['atrasadas'],
                        'prazo' => $f['prazo']
                    ];
                }
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

        return view('Admin8/Agencia/graficos', [
          'agencia' => $agencia,
          'media' => $media,
          'mediaMeses' => $mediaMeses,
          'resultadosDemanda' => $resultadosDemanda,
          'demandas' => $demandas,
          'demandasCount' => $demandasCount,
          'resultadosDemandaPrazos' => $resultadosDemandaPrazos,
          'demandasAtrasadasCount' => $demandasAtrasadasCount,
          'demandasEmPrazoCount' => $demandasEmPrazoCount,
          'arrayOrdem' => $arrayOrdem,
          'ordemValue' => $ordemValue
        ]);
    }

    public function exportDays($id)
    {
        //Média de dias por mês

        $agencia = Agencia::where('id', $id)->where('excluido', null)->first();

        return Excel::download(new DemandasExport($agencia->id), $agencia->nome.'-media-de-dias.xlsx');

    }

    public function exportPrazos($id)
    {
        //Média de dias por mês

        $agencia = Agencia::where('id', $id)->where('excluido', null)->first();

        return Excel::download(new DemandasExportPrazos($agencia->id), $agencia->nome.'-atrasadas-em-prazo.xlsx');

    }

    public function exportJobs($id)
    {
        //Jobs criados/finalizados por mês

        $agencia = Agencia::where('id', $id)->where('excluido', null)->first();

        return Excel::download(new DemandasExportJobs($agencia->id), $agencia->nome.'-demandas-criadas-finalizadas.xlsx');

    }

    public function stages(){
        $demandas = Demanda::where('etapa_1', 1)->where('etapa_2', 0)->where('excluido', null)->with(['agencia' => function ($query) {
            $query->where('excluido', null);
        }])->get();

        if($demandas){
            return view('Admin8/etapas', [
                'demandas' => $demandas,
           ]);
        }

        return redirect('/8poroito/admin');

    }
}
