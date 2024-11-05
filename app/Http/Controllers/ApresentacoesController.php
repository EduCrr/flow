<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Marca;
use Carbon\Carbon;
use Illuminate\Support\Facades\Lang;

class ApresentacoesController extends Controller
{


    public function dashboard(Request $request){

        $marcas = Marca::where('excluido', null)->get();
        $currentYear = date('Y');
        $currentDay = date('d');
        $dataAtual = date('Y-m-d H:i:s');
        $now = Carbon::now('America/Sao_Paulo');
        $startDateOfMonth = $now->startOfMonth()->format('Y-m-d H:i:s');
        $endDateOfMonth = $now->endOfMonth()->format('Y-m-d H:i:s');
        $mesAtual = $now->month;

        $mesAtualLangClass = Lang::get('date.months')[now()->format('n') - 1];

        $marcas->transform(function ($marca) use ($currentYear, $currentDay, $dataAtual, $startDateOfMonth, $endDateOfMonth, $mesAtual) {


            //gerais

            $entreguesGeralCount = $marca->demandas()
            ->where('excluido', null)
            ->where('etapa_1', 1)
            ->where('etapa_2', 1)
            ->where('entregue', 1)
            ->where('finalizada', 0)
            ->where('pausado', 0)
            ->whereYear('inicio', $currentYear)
            ->count();

            $marca->entreguesGeralCount = $entreguesGeralCount;


            $atrasadasGeralCount = $marca->demandas()
            ->join('demandas_atrasadas', 'demandas.id', '=', 'demandas_atrasadas.demanda_id')
            ->where('excluido', null)
            ->where('etapa_1', 1)
            ->where('etapa_2', 1)
            ->where('entregue', 0)
            ->where('finalizada', 0)
            ->where('pausado', 0)
            ->whereYear('inicio', $currentYear)
            ->count();

            $marca->atrasadasGeralCount = $atrasadasGeralCount;

            $emPautaGeralCount = $marca->demandas()
            ->where('excluido', null)
            ->where('etapa_1', 1)
            ->where('etapa_2', 1)
            ->where('em_pauta', 1)
            ->where('finalizada', 0)
            ->where('entregue', 0)
            ->where('pausado', 0)
            ->whereYear('inicio', $currentYear)
            ->count();

            $marca->emPautaGeralCount = $emPautaGeralCount;

            $entreguesMesCount = $marca->demandas()
            ->where('excluido', null)
            ->where('etapa_1', 1)
            ->where('etapa_2', 1)
            ->where('entregue', 1)
            ->where('finalizada', 0)
            ->where('pausado', 0)
            ->where(function ($query) use ($startDateOfMonth, $endDateOfMonth) {
                $query->where(function ($subQuery) use ($startDateOfMonth, $endDateOfMonth) {
                    $subQuery->whereBetween('final', [$startDateOfMonth, $endDateOfMonth]);
                });
            })
            ->count();

            $marca->entreguesMesCount = $entreguesMesCount;

            $atrasadasMesCount = $marca->demandas()
            ->join('demandas_atrasadas', 'demandas.id', '=', 'demandas_atrasadas.demanda_id')
            ->where('excluido', null)
            ->where('etapa_1', 1)
            ->where('etapa_2', 1)
            ->where('entregue', 0)
            ->where('finalizada', 0)
            ->where('pausado', 0)
            ->where(function ($query) use ($startDateOfMonth, $endDateOfMonth) {
                $query->where(function ($subQuery) use ($startDateOfMonth, $endDateOfMonth) {
                    $subQuery->whereBetween('inicio', [$startDateOfMonth, $endDateOfMonth]);
                })->orWhere(function ($subQuery) use ($startDateOfMonth, $endDateOfMonth) {
                    $subQuery->whereBetween('final', [$startDateOfMonth, $endDateOfMonth]);
                });
            })
            ->count();

            $marca->atrasadasMesCount = $atrasadasMesCount;

            $emPautaMesCount = $marca->demandas()
            ->where('excluido', null)
            ->where('etapa_1', 1)
            ->where('etapa_2', 1)
            ->where('em_pauta', 1)
            ->where('finalizada', 0)
            ->where('entregue', 0)
            ->where('pausado', 0)
            ->where(function ($query) use ($startDateOfMonth, $endDateOfMonth) {
                $query->where(function ($subQuery) use ($startDateOfMonth, $endDateOfMonth) {
                    $subQuery->whereBetween('inicio', [$startDateOfMonth, $endDateOfMonth]);
                })->orWhere(function ($subQuery) use ($startDateOfMonth, $endDateOfMonth) {
                    $subQuery->whereBetween('final', [$startDateOfMonth, $endDateOfMonth]);
                });
            })
            ->count();

            $marca->emPautaMesCount = $emPautaMesCount;

            $atrasadas = $marca->demandas()
            ->join('demandas_atrasadas', 'demandas.id', '=', 'demandas_atrasadas.demanda_id')
            ->where('excluido', null)
            ->where('finalizada', 0)
            ->where('entregue', 0)
            ->where('etapa_1', 1)
            ->where('etapa_2', 1)
            ->where('pausado', 0)
            ->with(['marcas' => function ($query) {
                $query->where('excluido', null);
            }])
            ->with('criador')->with('subCriador')
            ->with(['demandasReabertas' => function ($query) {
                $query->where('finalizado', null);
                $query->where('excluido', null);
            }])->get();

            foreach ($atrasadas as $key => $demanda) {
                $demanda->criador->nome = explode(' ', $demanda->criador->nome)[0];

                if ($demanda->finalizada == 1) {
                    $porcentagem = 100;
                } else {
                    $totalFinalizados = $demanda->prazosDaPauta()->whereNotNull('finalizado')->count();

                    $totalNaoFinalizados = $demanda->prazosDaPauta()->whereNull('finalizado')->count();

                    $totalPrazos = $totalFinalizados + $totalNaoFinalizados;
                    if ($totalPrazos == 0) {
                        $porcentagem = 0;
                    } elseif ($totalFinalizados == 0) {
                        $porcentagem = 10;
                    } else {
                        $porcentagem = round(($totalFinalizados / $totalPrazos) * 95);
                    }
                }

                $demanda->porcentagem = $porcentagem;

                $demandasReabertas = $demanda->demandasReabertas;
                if ($demandasReabertas->count() > 0) {
                    $sugerido = $demandasReabertas->sortByDesc('id')->first()->sugerido;
                    $demanda->final = $sugerido;
                }
            }

            $marca->atrasadas = $atrasadas;
            $marca->atrasadasCount = count($atrasadas);


            $entregaHoje = $marca->demandas()
            ->where('excluido', null)
            ->where('finalizada', 0)
            ->where('entregue', 0)
            ->where('etapa_1', 1)
            ->where('etapa_2', 1)
            ->where('pausado', 0)
            ->whereDate('final', '=', now()->format('Y-m-d'))
            ->with(['demandasReabertas' => function ($query) {
                $query->where('finalizado', null);
                $query->where('excluido', null);
            }])
            ->with(['marcas' => function ($query) {
                $query->where('excluido', null);
            }])
            ->with('criador')->with('subCriador')
            ->get();

            foreach ($entregaHoje as $key => $demanda) {
                $demanda->criador->nome = explode(' ', $demanda->criador->nome)[0];

                if ($demanda->finalizada == 1) {
                    $porcentagem = 100;
                } else {
                    $totalFinalizados = $demanda->prazosDaPauta()->whereNotNull('finalizado')->count();

                    $totalNaoFinalizados = $demanda->prazosDaPauta()->whereNull('finalizado')->count();

                    $totalPrazos = $totalFinalizados + $totalNaoFinalizados;
                    if ($totalPrazos == 0) {
                        $porcentagem = 0;
                    } elseif ($totalFinalizados == 0) {
                        $porcentagem = 10;
                    } else {
                        $porcentagem = round(($totalFinalizados / $totalPrazos) * 95);
                    }
                }

                $demanda->porcentagem = $porcentagem;

                $demandasReabertas = $demanda->demandasReabertas;
                if ($demandasReabertas->count() > 0) {
                    $sugerido = $demandasReabertas->sortByDesc('id')->first()->sugerido;
                    $demanda->final = $sugerido;
                }

            }

            $marca->entregaHoje = $entregaHoje;
            $marca->entregaHojeCount = count($entregaHoje);


            $entregaMesAtual = $marca->demandas()
            ->where('finalizada', 0)
            ->where('entregue', 0)
            ->where('etapa_1', 1)
            ->where('etapa_2', 1)
            ->where('pausado', 0)
            ->where('excluido', null)
            ->whereMonth('final', $mesAtual)
            ->with(['demandasReabertas' => function ($query) {
                $query->where('finalizado', null);
                $query->where('excluido', null);
            }])
            ->with(['marcas' => function ($query) {
                $query->where('excluido', null);
            }])
            ->with('criador')
            ->with('subCriador')
            ->get();

            foreach ($entregaMesAtual as $key => $demanda) {
                $demanda->criador->nome = explode(' ', $demanda->criador->nome)[0];

                if ($demanda->finalizada == 1) {
                    $porcentagem = 100;
                } else {
                    $totalFinalizados = $demanda->prazosDaPauta()->whereNotNull('finalizado')->count();

                    $totalNaoFinalizados = $demanda->prazosDaPauta()->whereNull('finalizado')->count();

                    $totalPrazos = $totalFinalizados + $totalNaoFinalizados;
                    if ($totalPrazos == 0) {
                        $porcentagem = 0;
                    } elseif ($totalFinalizados == 0) {
                        $porcentagem = 10;
                    } else {
                        $porcentagem = round(($totalFinalizados / $totalPrazos) * 95);
                    }
                }

                $demanda->porcentagem = $porcentagem;

                $demandasReabertas = $demanda->demandasReabertas;
                if ($demandasReabertas->count() > 0) {
                    $sugerido = $demandasReabertas->sortByDesc('id')->first()->sugerido;
                    $demanda->final = $sugerido;
                }
            }

            $marca->entregaMesAtual = $entregaMesAtual;
            $marca->entregaMesAtualCount = count($entregaMesAtual);


            //demandas infos

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
                $demandasCriadas[$month] = $marca->demandas()->select('id', 'inicio', 'finalizada', 'etapa_1', 'etapa_2')->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->whereYear('inicio', $currentYear)->whereMonth('inicio', $month)->count();

                $demandasFinalizadas[$month] = $marca->demandas()->select('id', 'inicio', 'finalizada', 'etapa_1', 'etapa_2')->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->whereYear('inicio', $currentYear)->whereMonth('inicio', $month)->where('finalizada', 1)->count();

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
                            'finalizadas' => $f['finalizadas'],
                        ];
                    }
                }
            }

            $marca->resultadosDemanda = $resultadosDemanda;

            $marca->totalMes = $atrasadasMesCount + $emPautaMesCount + $entreguesMesCount;
            $marca->total = $atrasadasGeralCount + $emPautaGeralCount + $entreguesGeralCount;
            return $marca;
        });


        return view('Admin/dashboard', [
            'marcas' => $marcas,
            'ano' => $currentYear,
            'mes' => $mesAtualLangClass
        ]);
    }

}
