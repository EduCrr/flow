<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Demanda;
use App\Models\User;
use App\Models\DemandaImagem;
use App\Models\Marca;
use App\Models\Notificacao;
use App\Models\LinhaTempo;
use App\Models\DemandaTempo;
use App\Models\Questionamento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
Use Alert;
use App\Models\Agencia;
use App\Models\AgenciaUsuario;
use App\Models\BriefingLido;
use App\Models\DemandaAtrasada;
use App\Models\DemandaColaborador;
use App\Models\DemandaOrdem;
use App\Models\DemandaOrdemJob;
use App\Models\DemandaReaberta;
use App\Models\DemandaUsuario;
use App\Models\MarcaColaborador;
use App\Models\QuestionamentoLido;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Response;
use App\Utils\EnviarMail;
use App\Utils\OrdemJob;

class DemandasController extends Controller
{

    //findOne job
    public function index(Request $request, $id){

        $demanda = Demanda::where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->where('id', $id)->with('imagens')->with('criador')->with('subCriador')->with('demandasUsuario')->with('demandasReabertas')->with(['prazosDaPauta.agencia', 'prazosDaPauta.comentarios'])->with(['marcas' => function ($query) {
        $query->where('excluido', null);
        }])->with('descricoes')->withCount(['prazosDaPauta as count_prazos' => function ($query) {
            $query->where('finalizado', null);
        }])->with(['demandaColaboradores' => function ($query) {
        }])->with('marcasDemandas')->first();

        $user = Auth::User();
        $briefingCount = null;

        if($demanda){
            $demanda['agencia'] = $demanda->agencia()->first();
           
            $demanda['questionamentos'] = $demanda->questionamentos()
            ->where('excluido', null)
            ->with(['usuario' => function ($query) {
                $query->where('excluido', null);
            }])
            ->with('respostas.usuario', 'respostas.respostaUsuarioMarcado')
            ->with(['lidos' => function ($query) {
                $query->whereHas('usuario', function ($query) {
                    $query->where('excluido', null);
                })->where('marcado', 1);
            }])
            ->with(['lidosNotificacao' => function ($query) {
                $query->where('tipo', 'Comentario')->whereHas('usuario', function ($query) {
                    $query->where('excluido', null);
                });
            }])
            ->get()
            ->transform(function ($questionamento) {
                $questionamento->respostas->each(function ($resposta) {
                    $resposta->lidosResposta = $resposta->questionamentos->lidosNotificacao->where('tipo', 'Resposta')->filter(function ($lido) use ($resposta) {
                        return $lido->usuario_id != $resposta->usuario_id; 
                    })->map(function ($lido) {
                        return $lido->usuario; 
                    })->values()->all();
                    unset($resposta->questionamentos); 
                });
                
                return $questionamento;
            });
            
            $comenatariosIds = $demanda['questionamentos']->pluck('id')->toArray();
            
            $demanda['recorrenciasAnuais'] = $demanda->demandaRecorrencias()->where('tipo', 'Anual')
            ->with(['recorrencias' => function ($query) use($user) {
                $query->where('tipo', 'Anual')
                ->orderByRaw('CASE WHEN finalizado = 0 THEN 0 ELSE 1 END')
                ->orderBy('data', 'asc')
                ->with('ajustes')->withCount(['ajustes as count_ajustes' => function ($query) {
                    $query->where('entregue', 0);
                }])
                ->with(['comentarios' => function ($query) use($user) {
                    $query->with('usuario')
                        ->withCount(['lidos as count_comentarios' => function ($query) use($user){
                            $query->where('visualizada', 0)->where('usuario_id', $user->id);
                        }])->with(['lidos' => function($subquery){
                            $subquery->where('visualizada', 1);
                        }]);
                }]);
            }])
            ->get()
            ->transform(function ($item) {
                $item->recorrencias->each(function ($recorrencia) {
                    $recorrencia->hasComentariosNaoLidos = $recorrencia->comentarios->contains('count_comentarios', '>', 0);
                });
                return $item;
            });


            $demanda['recorrenciasMensais'] = $demanda->demandaRecorrencias()->where('tipo', 'Mensal')
            ->with(['recorrencias' => function ($query) use($user) {
                $query->where('tipo', 'Mensal')
                    ->orderByRaw('CASE WHEN finalizado = 0 THEN 0 ELSE 1 END')
                    ->orderBy('data', 'asc')
                    ->with('ajustes')
                    ->withCount(['ajustes as count_ajustes' => function ($query) {
                        $query->where('entregue', 0);
                    }])
                    ->with(['comentarios' => function ($query) use($user) {
                        $query->with('usuario')
                            ->withCount(['lidos as count_comentarios' => function ($query) use($user){
                                $query->where('visualizada', 0)->where('usuario_id', $user->id);
                            }])->with(['lidos' => function($subquery){
                                $subquery->where('visualizada', 1);
                            }]);
                    }]);
            }])
            ->get()
            ->transform(function ($item) {
                $item->recorrencias->each(function ($recorrencia) {
                    $recorrencia->hasComentariosNaoLidos = $recorrencia->comentarios->contains('count_comentarios', '>', 0);
                });
                return $item;
            });


            $demanda['recorrenciasSemanais'] = $demanda->demandaRecorrencias()->where('tipo', 'Semanal')
            ->with(['recorrencias' => function ($query) use($user){
                $query->where('tipo', 'Semanal')
                ->orderByRaw('CASE WHEN finalizado = 0 THEN 0 ELSE 1 END')
                ->orderBy('data', 'asc')
                 ->with('ajustes')->withCount(['ajustes as count_ajustes' => function ($query) {
                    $query->where('entregue', 0);
                }])
                ->with(['comentarios' => function ($query) use($user) {
                    $query->with('usuario')
                        ->withCount(['lidos as count_comentarios' => function ($query) use($user){
                            $query->where('visualizada', 0)->where('usuario_id', $user->id);
                        }])->with(['lidos' => function($subquery){
                            $subquery->where('visualizada', 1);
                        }]);
                }]);
            }])
            ->get()
            ->transform(function ($item) {
                $item->recorrencias->each(function ($recorrencia) {
                    $recorrencia->hasComentariosNaoLidos = $recorrencia->comentarios->contains('count_comentarios', '>', 0);
                });
                return $item;
            });

            foreach ($demanda['recorrenciasAnuais'] as $key => $item) {
                $totalRecorrencias = $item->recorrencias->count();

                $recorrenciasEntregues = $item->recorrencias->filter(function ($recorrencia) {
                    return $recorrencia->finalizado == '1';
                })->count();

                $porcentagemEntregue = $totalRecorrencias > 0 ? ($recorrenciasEntregues / $totalRecorrencias) * 100 : 0;

                $item->porcentagemEntregue = number_format($porcentagemEntregue, 1);

                $demanda['recorrenciasAnuais'][$key] = $item;
            }

            foreach ($demanda['recorrenciasMensais'] as $key => $item) {
                $totalRecorrencias = $item->recorrencias->count();

                $recorrenciasEntregues = $item->recorrencias->filter(function ($recorrencia) {
                    return $recorrencia->finalizado == '1';
                })->count();

                $porcentagemEntregue = $totalRecorrencias > 0 ? ($recorrenciasEntregues / $totalRecorrencias) * 100 : 0;

                $item->porcentagemEntregue = number_format($porcentagemEntregue, 1);

                $demanda['recorrenciasMensais'][$key] = $item;
            }

            foreach ($demanda['recorrenciasSemanais'] as $key => $item) {
                $totalRecorrencias = $item->recorrencias->count();

                $recorrenciasEntregues = $item->recorrencias->filter(function ($recorrencia) {
                    return $recorrencia->finalizado == '1';
                })->count();

                $porcentagemEntregue = $totalRecorrencias > 0 ? ($recorrenciasEntregues / $totalRecorrencias) * 100 : 0;

                $item->porcentagemEntregue = number_format($porcentagemEntregue, 1);

                $demanda['recorrenciasSemanais'][$key] = $item;
            }

            foreach($demanda['prazosDaPauta'] as $key => $item) {
                if($item->finalizado !== null) {
                    $iniciado = \Carbon\Carbon::parse($item->iniciado);
                    $finalizado = \Carbon\Carbon::parse($item->finalizado);
                    $duracao = null;

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

                    $diferencaEmDias = number_format($diferencaEmDias, 1, '.', '');

                    if($diferencaEmDias < 1) {
                        $duracao = "Menos de 1 dia";
                    } else if($diferencaEmDias == 1) {
                        $duracao = $diferencaEmDias . " dia";
                    }else{
                        $duracao = $diferencaEmDias . " dias";
                    }

                    $demanda['prazosDaPauta'][$key]->final = $duracao;
                } else {
                    $demanda['prazosDaPauta'][$key]->final = null;
                }
            }


            $showAg = false;
            $showColaborador = false;
            $isAdmin = false;

            $idsAgUser = $demanda->demandasUsuario->pluck('id')->toArray();
            $idsColaboradoresUser = $demanda->demandaColaboradores->pluck('id')->toArray();

            $isSend = LinhaTempo::where('demanda_id', $id)->where('status', 'Entregue')->count();

            $entregue = Demanda::where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->where('id', $id)->where('entregue', '1')->count();

            if(in_array($user->id, $idsAgUser)){
                //Ler comentários ag
                $showAg = true;
                
                $demandaWithBriefingCount = $demanda->withCount(['briefing as briefing_count' => function ($query) use ($user) {
                    $query->where('usuario_id', $user->id);
                }])->find($demanda->id);

                $briefingCount = $demandaWithBriefingCount->briefing_count;
                
            }else{
                $showAg = false;
            }
            if(in_array($user->id, $idsColaboradoresUser)){
                $showColaborador = true;
                // foreach($demanda['questionamentos'] as $quest){
                //     if($user->id == $quest->marcado_usuario_id){
                //         $quest->visualizada_col = 1;
                //         $quest->save();
                //     }
                // }
            }else{
                $showColaborador = false;
            }

            if($user->tipo == 'admin' && $user->id != $demanda->criador_id && $showColaborador == false){
                $marcasColaboradorIds = MarcaColaborador::where('usuario_id', $user->id)->pluck('marca_id')->toArray();
                if ($demanda->marcas->pluck('id')->intersect($marcasColaboradorIds)->isNotEmpty()) {
                    $isAdmin = true;
                }

            }else{
                $isAdmin = false;
            }

            //porcentagem
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
            $lineTime = LinhaTempo::where('demanda_id', $id)->with('usuario')->get();

            //Pegar inicias do nome
            foreach ($lineTime as $linha) {
                $usuario = $linha->usuario;
                $nome_formatado = $usuario->nome;

                // Separa o nome completo em partes
                $nomes = explode(' ', $nome_formatado);

                // Pega o primeiro nome
                $primeiro_nome = $nomes[0];

                // Pega as primeiras letras dos sobrenomes
                $iniciais_sobrenomes = '';
                if (count($nomes) > 1) {
                    for ($i = 1; $i < count($nomes); $i++) {
                        $sobrenome = $nomes[$i];
                        if ($i == count($nomes) - 1) {
                            // Último sobrenome, não adiciona espaço depois da inicial
                            $iniciais_sobrenomes .= strtoupper(substr($sobrenome, 0, 1)).'.';
                        } else {
                            // Sobrenome não é o último, adiciona espaço depois da inicial
                            $iniciais_sobrenomes .= strtoupper(substr($sobrenome, 0, 1)).'. ';
                        }
                    }
                }

                // Concatena o primeiro nome e as iniciais dos sobrenomes
                if ($iniciais_sobrenomes != '') {
                    $nome_formatado = $primeiro_nome.' '.$iniciais_sobrenomes;
                } else {
                    $nome_formatado = $primeiro_nome;
                }

                // Atribui o nome formatado à propriedade "nome" do objeto "usuario"
                $usuario->nome = $nome_formatado;
            }

            $agenciaUsuarios = Agencia::where('id', $demanda->agencia_id)
            ->with(['agenciasUsuarios' => function ($query) {
                $query->select('usuarios.id', 'usuarios.nome');
            }])
            ->first();


            $agenciaUsersIds = $agenciaUsuarios->agenciasUsuarios->pluck('id')->toArray();

            $userDemanda = $demanda->demandasUsuario->pluck('id')->toArray();

            $colaboradores = MarcaColaborador::where('marca_id', $demanda->marcasDemandas[0]->id)
            ->join('usuarios', 'marcas_colaboradores.usuario_id', '=', 'usuarios.id')
            ->get();

            $adminBrand = MarcaColaborador::where('marca_id', $demanda->marcasDemandas[0]->id)
            ->join('usuarios', 'marcas_colaboradores.usuario_id', '=', 'usuarios.id')
            ->where('usuarios.tipo', 'admin')
            ->get();


            $userColaboradores = $demanda->demandaColaboradores->map(function ($colaborador) {
                return [
                    'id' => $colaborador->id,
                    'nome' => $colaborador->nome,
                ];
            })->toArray();


            $userAdminBrand = $adminBrand->map(function ($colaborador) {
                return [
                    'id' => $colaborador->id,
                    'nome' => $colaborador->nome,
                ];
            })->toArray();


            $userAgencia = $demanda->demandasUsuario->map(function ($agenciaUser) {
                return [
                    'id' => $agenciaUser->id,
                    'nome' => $agenciaUser->nome,
                ];
            })->toArray();

            $userColaboradores[] = [
                'id' => $demanda->criador->id,
                'nome' => $demanda->criador->nome,
            ];

            $resultUsers = collect(array_merge($userAgencia, $userColaboradores))->unique('id')->values()->all();
            $deliveriesReq = $demanda->demandaRecorrencias()->where('finalizada', 0)->count();

            // ler comentario
            $questLidos = QuestionamentoLido::where('usuario_id', $user->id)->where('visualizada', 0)->whereIn('comentario_id', $comenatariosIds)->get();
            foreach ($questLidos as $item) {
                QuestionamentoLido::where('comentario_id', $item->comentario_id)->where('usuario_id', $user->id)
                ->update(['visualizada' => 1]);
            }

            if($showAg || $user->id == $demanda->criador_id || $showColaborador || $isAdmin || $user->tipo == 'admin_8'){
                return view('Job/index', [
                    'demanda' => $demanda,
                    'user' => $user,
                    'showAg' => $showAg,
                    'isSend' => $isSend,
                    'lineTime' => $lineTime,
                    'entregue' => $entregue,
                    'showColaborador' => $showColaborador,
                    'idsColaboradoresUser' => $idsColaboradoresUser,
                    'isAdmin' => $isAdmin,
                    'agenciaUsuarios' => $agenciaUsuarios,
                    'agenciaUsersIds' => $agenciaUsersIds,
                    'userDemanda' => $userDemanda,
                    'colaboradores' => $colaboradores,
                    'userColaboradores' => $userColaboradores,
                    'resultUsers' => $resultUsers,
                    'deliveriesReq' => $deliveriesReq,
                    'briefingCount' => $briefingCount
                ]);
            }else{
                return redirect('/')->with('warning', 'Esse job não está disponível.' );
            }
        }else{
            return redirect('/login')->with('warning', 'Esse job não está disponível.' );
        }

    }

    public function downloadImage($id){

        $image = DemandaImagem::find($id);

        if($image){
            $path = public_path('assets/images/files/'.$image->imagem);
            return response()->download($path);
        }

        return view('home');
    }

    public function findAll(Request $request){

        $user = Auth::User();

        $demandas = Demanda::where('demandas.excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->where(function ($query) use ($user) {
        $query->whereHas('demandasUsuario', function ($query) use ($user) {
            $query->where('usuario_id', $user->id);
        });
        })->with(['marcas' => function ($query) {
        $query->where('excluido', null);
        }])->with(['agencia' => function ($query) {
        $query->where('excluido', null);
        }])->with(['demandasReabertas' => function ($query) {
            $query->where('finalizado', null);
            $query->where('excluido', null);
        }]);

        $demandas->with(['questionamentos' => function ($query) use($user) {
            $query->with('usuario')
                ->withCount(['lidos as count_comentarios' => function ($query) use($user){
                    $query->where('visualizada', 0)->where('usuario_id', $user->id);
                }]);
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

        $demandas->withCount(['questionamentos as count_questionamentos' => function ($query) use($user) {
            $query->where('visualizada_ag', 0)->where('excluido', null)->where('marcado_usuario_id', $user->id);
        }])
        ->withCount(['questionamentos as count_respostas' => function ($query)  use($user) {
            $query->whereHas('respostas', function ($query) use($user)  {
                $query->where('visualizada_ag', 0)->where('marcado_usuario_id', $user->id);
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

        return view('Job/jobs', [
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

    public function uploadImg(Request $request, $id){
        $user = Auth::User();
        $imgs = $request->file('file');
        $input_data = $request->all();
        $validator = Validator::make(
            $input_data, [
            'file.*' => 'required'
            ],[
                'file.*.required' => 'Adicione algum arquivo.',
            ]
        );

        if(!$validator->fails()){
            if ($request->hasFile('file')) {
                foreach($imgs as $item){
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

                    $newArqJob = new DemandaImagem();
                    $newArqJob->demanda_id =  $id;
                    $newArqJob->imagem = $photoName;
                    $newArqJob->usuario_id = $user->id;
                    $newArqJob->criado =  date('Y-m-d H:i:s');
                    $newArqJob->save();
                }

                $demanda = Demanda::select('id', 'criador_id')->where('id', $id)->with('demandasUsuario')->first();

                if($user->id == $demanda->criador_id){
                    foreach($demanda['demandasUsuario'] as $item){
                        $notificacao = new Notificacao();
                        $notificacao->demanda_id = $demanda->id;
                        $notificacao->criado = date('Y-m-d H:i:s');
                        $notificacao->visualizada = '0';
                        $notificacao->tipo = 'criada';
                        $notificacao->usuario_id = $item->id;
                        $notificacao->criado = date('Y-m-d H:i:s');
                        $notificacao->conteudo = 'Novo arquivo foi adicionado na aba anexos.';
                        $notificacao->save();
                    }
                }else{
                    $criadorNotificacao = new Notificacao();
                    $criadorNotificacao->usuario_id = $demanda->criador->id;
                    $criadorNotificacao->demanda_id = $demanda->id;
                    $criadorNotificacao->conteudo = 'Novo arquivo foi adicionado na aba anexos.';
                    $criadorNotificacao->criado = date('Y-m-d H:i:s');
                    $criadorNotificacao->visualizada = '0';
                    $criadorNotificacao->tipo = 'criada';
                    $criadorNotificacao->save();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Arquivo adicionado!.'
                ], Response::HTTP_OK);

            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Não foi possível adicionar este(s) arquivo(s)'
                ], Response::HTTP_BAD_REQUEST);
            }
        }else{

            return response()->json([
                'success' => false,
                'message' =>  $validator->errors()->first(),
            ], Response::HTTP_BAD_REQUEST);

        }
    }

    public function deleteArq(Request $request, $id){

        $fileUpload = DemandaImagem::find($id);

        if($fileUpload){
            $path=public_path().'/assets/images/files/'.$fileUpload->imagem;
            if (file_exists($path)) {
                unlink($path);
            }
            $fileUpload->delete();


            return response()->json([
                'success' => true,
                'message' => 'Arquivo excluido com sucesso.'
            ], Response::HTTP_OK);

        }

        return response()->json([
            'success' => false,
            'message' =>  'Não foi possível excluir este arquivo'
        ], Response::HTTP_BAD_REQUEST);

    }



    //iniciar pauta
    public function changeStatusPauta(Request $request, $id){
        $user = Auth::User();
        $demanda = Demanda::where('id', $request->id)->where('excluido', null)->where('etapa_1', 1)->where('etapa_2', 1)->with('criador')->with('demandaColaboradores')->first();

        $validator = Validator::make($request->all(),[
           'sugeridoAg' => 'required',

            ],[
                'sugeridoAg.required' => 'Preencha o prazo de entrega!',
            ]
        );

        if($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' =>  $validator->errors()->first(),
            ], Response::HTTP_BAD_REQUEST);

        }

        if($demanda){

            $newTimeLineStart =  new LinhaTempo();
            $newTimeLineStart->demanda_id = $request->id;
            $newTimeLineStart->usuario_id = $user->id;
            $newTimeLineStart->criado = date('Y-m-d H:i:s');

            $newTimeJob = new DemandaTempo();
            $newTimeJob->demanda_id = $request->id;
            $newTimeJob->agencia_id = $demanda->agencia_id;


            $newTimeJob->criado = date('Y-m-d H:i:s');
            $newTimeJob->sugerido = $request->sugeridoAg;
            $newTimeJob->aceitar_agencia = 1;
            $newTimeJob->recebido = 1;
            $newTimeJob->code_tempo = 'em-pauta';
            $newTimeJob->iniciado = date('Y-m-d H:i:s');

            if($demanda->em_alteracao == 0){
                $hasScheduleReopenCount = LinhaTempo::where('demanda_id', $request->id)->where('code', 'iniciada-pauta')->count();

                if($hasScheduleReopenCount == 0){
                    $newTimeLineStart->status = 'Iniciada pauta 1';
                    $newTimeLineStart->code = 'iniciada-pauta';
                    $newTimeJob->status = 'Pauta 1';

                }else{
                    $newTimeLineStart->status = 'Iniciada pauta '.($hasScheduleReopenCount + 1);
                    $newTimeLineStart->code = 'iniciada-pauta';
                    $newTimeJob->status = 'Pauta '.($hasScheduleReopenCount + 1);
                }

                $demanda->em_pauta = 1;
                $demanda->status = 'Em pauta';
                $demanda->finalizada = 0;
                $demanda->entregue = 0;
                $demanda->save();

                $newTimeLineStart->save();
                $newTimeJob->save();

            }

            //notificar criador

            $criadorNotificacao = new Notificacao();
            $criadorNotificacao->usuario_id = $demanda->criador->id;
            $criadorNotificacao->demanda_id = $demanda->id;
            $criadorNotificacao->conteudo = 'O job entrou em pauta.';
            $criadorNotificacao->criado = date('Y-m-d H:i:s');
            $criadorNotificacao->visualizada = '0';
            $criadorNotificacao->tipo = 'pauta';
            $criadorNotificacao->save();

            $colaboradoresIds = $demanda->demandaColaboradores->pluck('id')->toArray();

            foreach($colaboradoresIds as $c){
                if($demanda->criador->id != $c){
                    $notificacaoColaborador = new Notificacao();
                    $notificacaoColaborador->demanda_id = $demanda->id;
                    $notificacaoColaborador->criado = date('Y-m-d H:i:s');
                    $notificacaoColaborador->visualizada = '0';
                    $notificacaoColaborador->tipo = 'pauta';
                    $notificacaoColaborador->usuario_id = $c;
                    $notificacaoColaborador->conteudo = 'O job entrou em pauta.';
                    $notificacaoColaborador->save();
                }
            }

            //notificar quando o prazo for maior que o atual

            $dataSugerida = Carbon::parse($request->sugeridoAg);
            $dataFinal = Carbon::parse($demanda->final);

            if ($dataSugerida->gt($dataFinal)) {
                $criadorNotificacao = new Notificacao();
                $criadorNotificacao->usuario_id = $demanda->criador->id;
                $criadorNotificacao->demanda_id = $demanda->id;
                $criadorNotificacao->conteudo = 'Por favor, confira e confirme o prazo de entrega definido pelo usuário para o job '. $demanda->id;
                $criadorNotificacao->criado = date('Y-m-d H:i:s');
                $criadorNotificacao->visualizada = '0';
                $criadorNotificacao->tipo = 'questionamento';
                $criadorNotificacao->save();
            }

        }

        return response()->json([
            'success' => true,
            'message' => 'Job alterado com sucesso.'
        ], Response::HTTP_OK);

    }


    public function editStatusPauta(Request $request, $id){
        $timePauta = DemandaTempo::find($id);
        $validator = Validator::make($request->all(),[
           'finalizadoEdit' => 'required',

            ],[
                'finalizadoEdit.required' => 'Preencha seu prazo para esse job!',
            ]
        );

        if($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], Response::HTTP_BAD_REQUEST);
        }

        if(!$validator->fails()){
            if($timePauta){
                $timePauta->finalizado = $request->finalizadoEdit;
                $timePauta->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Pauta alterada com sucesso.'
            ], Response::HTTP_OK);

        }

    }

    public function changeDemandatitle(Request $request, $id){
        $demanda = Demanda::find($id);
        if($demanda){
            $demanda->titulo = $request->titulo;
            $demanda->save();

            return response()->json([
                'success' => true,
                'message' => 'Pauta alterada com sucesso.'
            ], Response::HTTP_OK);

        }else{

            return response()->json([
                'success' => false,
                'message' => 'Esse job não pode ser alterado.'
            ], Response::HTTP_BAD_REQUEST);

        }
    }


    public function changeTime(Request $request, $id){
        $user = Auth::User();
        $validator = Validator::make($request->all(),[
            'sugerido' => 'required',
            'sugeridoAlt' => 'required',
            ],[
                'sugerido.required' => 'Preencha o campo data.',
                'sugeridoAlt.required' => 'Descreva o motivo para a alteração do prazo!',
            ]
        );

        if($validator->fails()) {

            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 400);

        }

        if(!$validator->fails()){
            $demandaPrazo = DemandaTempo::where('id', $id)->with('demanda:id,criador_id,agencia_id', 'demanda.agencia')->first();
            $demanda = Demanda::select('id', 'titulo')->where('id', $demandaPrazo->demanda_id)->with('demandasUsuario')->with('demandaColaboradores')->first();
            $actionLink = route('Job', ['id' => $demanda->id]);
            $bodyEmailDefault = 'O job '. $demanda->id .  ': '.$demanda->titulo . ', recebeu uma nova mensagem.'. '<br/>'.  'Acesse pelo link logo abaixo.';

            $bodyconteudo = '';
            $userDemanda =  $demandaPrazo->demanda->criador->id;
            $user = Auth::User();

            //pegar numero
            if (preg_match('/\d+/', $demandaPrazo->status, $matches)) {
                $lastNumber = $matches[0];
            }

            $demandaPrazo->sugerido = $request->sugerido;

            $colaboradoresIds = $demanda->demandaColaboradores->pluck('id')->toArray();
            $usuariosIds = $demanda->demandasUsuario->pluck('id')->toArray();

            if($demanda){

                $newComment = new Questionamento();
                $newComment->demanda_id = $demandaPrazo->demanda_id;
                $newComment->usuario_id = $user->id;
                $newComment->descricao = $request->sugeridoAlt;
                $newComment->criado = date('Y-m-d H:i:s');
                if($demandaPrazo->code_tempo == 'em-pauta'){
                    $newComment->tipo = 'Mudança de prazo da '. strtolower($demandaPrazo->status);
                }else if($demandaPrazo->code_tempo == 'alteracao'){
                    $newComment->tipo = 'Mudança de prazo da alteração '. $lastNumber;

                }
                $newComment->cor = '#f9bc0b';
                $newComment->save();

                //agencia notificacao
                if($user->id == $userDemanda || in_array($user->id, $colaboradoresIds)){
                    $demandaPrazo->aceitar_colaborador = 1;
                    $demandaPrazo->aceitar_agencia = 0;

                    if($demandaPrazo->code_tempo == 'em-pauta'){
                        $bodyconteudo = 'Por favor, confira e confirme o prazo de entrega definido pelo colaborador para '. strtolower($demandaPrazo->status).' do job '.$demanda->id.'.';

                    }else if($demandaPrazo->code_tempo == 'alteracao'){
                        $bodyconteudo = 'Por favor, confira e confirme o prazo de entrega definido pelo colaborador para alteração '. $lastNumber.' do job '.$demanda->id.'.';
                    }

                    $demandaT = Demanda::select('final', 'id')->where('id', $demandaPrazo->demanda_id)->with(['demandasReabertas' => function ($query) {
                        $query->where('finalizado', null)->where('excluido', null)->first();
                    }])->first();


                    if(count($demandaT['demandasReabertas']) > 0){
                        $demandaReaberta = DemandaReaberta::where('id', $demandaT['demandasReabertas'][0]->id)->first();
                        if (strtotime($request->sugerido) > strtotime($demandaReaberta->sugerido)) {
                            // $request->sugeridoComment é maior que $demanda->final
                            $demandaReaberta->sugerido = $request->sugerido;
                            $demandaReaberta->save();
                            $removerDemandasAtrasadas = DemandaAtrasada::where('demanda_id', $demandaT->id)->delete();
                        }
                    }else{
                        if (strtotime($request->sugerido) > strtotime($demandaT->final)) {
                            // $request->sugeridoComment é maior que $demanda->final
                            $demandaT->final = $request->sugerido;
                            $demandaT->save();
                            $removerDemandasAtrasadas = DemandaAtrasada::where('demanda_id', $demandaT->id)->delete();
                        }
                    }
                    $notificacaoAgencia = [];
                    $marcarUsuarioAgComentario = [];

                    foreach($usuariosIds as $u){
                        if ($user->id != $u) {
                            $notificacaoAgencia[] = [
                                'usuario_id' => $u,
                                'demanda_id' =>  $demanda->id,
                                'conteudo' => $bodyconteudo,
                                'tipo' => 'questionamento',
                                'tipo_referencia' => 'comentario-'.$newComment->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                            ];

                            $comentarioUsuario = [
                                'usuario_id' => $u,
                                'comentario_id' => $newComment->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'marcado' => 0,
                                'visualizada' => '0',
                            ];
                            $marcarUsuarioAgComentario[] = $comentarioUsuario;
                        }
                    }

                    QuestionamentoLido::insert($marcarUsuarioAgComentario);
                    Notificacao::insert($notificacaoAgencia);

                    $response = $demandaPrazo->save();

                    if($response){
                        $usersToNotifyByEmail = User::select('id', 'email', 'nome')->where('notificar_email', 1)->whereIn('id', $usuariosIds)->get();
                        $titleEmail = $bodyconteudo;
                        foreach ($usersToNotifyByEmail as $userToNotify) {
                            // EnviarMail::enviarEmail($userToNotify->email, $userToNotify->nome, $actionLink, $bodyEmailDefault, $titleEmail, $request->sugeridoAlt, $user->nome);
                            try {
                                EnviarMail::enviarEmail($userToNotify->email, $userToNotify->nome, $actionLink, $bodyEmailDefault, $titleEmail, $request->sugeridoAlt, $user->nome);
                            } catch (\Exception $e) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Oops, ocorreu um erro ao enviar uma notificação via e-mail, mas sua mensagem foi cadastrada em nosso sistema!'
                                ], 400);
                            }
                        }
                    }


                }else if($user->id != $userDemanda && !in_array($user->id, $colaboradoresIds)){
                    //criador notificacao
                    $demandaPrazo->aceitar_colaborador = 0;
                    $demandaPrazo->aceitar_agencia = 1;
                    $bodyMensagem = '';

                    if($demandaPrazo->code_tempo == 'em-pauta'){
                        $bodyMensagem = 'Por favor, confira e confirme o prazo de entrega definido pelo usuário para '. strtolower($demandaPrazo->status).' do job '.$demanda->id.'.';
                    }else if($demandaPrazo->code_tempo == 'alteracao'){
                        $bodyMensagem = 'Por favor, confira e confirme o prazo de entrega definido pelo usuário para alteração '. $lastNumber.' do job '.$demanda->id.'.';
                    }

                    $colaboradoresIds[] = $userDemanda;

                    $notificacaoColaborador = [];
                    $marcarUsuarioColComentario = [];

                    foreach($colaboradoresIds as $c){
                        if ($user->id != $c) {

                            $notificacaoColaborador[] = [
                                'usuario_id' => $c,
                                'demanda_id' =>  $demanda->id,
                                'conteudo' => $bodyMensagem,
                                'tipo' => 'questionamento',
                                'tipo_referencia' => 'comentario-'.$newComment->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'visualizada' => '0',
                            ];

                            $comentarioUsuario = [
                                'usuario_id' => $c,
                                'comentario_id' => $newComment->id,
                                'criado' => date('Y-m-d H:i:s'),
                                'marcado' => 0,
                                'visualizada' => '0',
                            ];
                            $marcarUsuarioColComentario[] = $comentarioUsuario;
                        }

                    }

                    Notificacao::insert($notificacaoColaborador);
                    QuestionamentoLido::insert($marcarUsuarioColComentario);

                    $response = $demandaPrazo->save();

                    if($response){
                        $usersToNotifyByEmail = User::select('id', 'email', 'nome')->where('notificar_email', 1)->whereIn('id', $colaboradoresIds)->get();
                        $titleEmail = $bodyMensagem;
                        foreach ($usersToNotifyByEmail as $userToNotify) {
                            // EnviarMail::enviarEmail($userToNotify->email, $userToNotify->nome, $actionLink, $bodyEmailDefault, $titleEmail, $request->sugeridoAlt, $user->nome);
                            try {
                                EnviarMail::enviarEmail($userToNotify->email, $userToNotify->nome, $actionLink, $bodyEmailDefault, $titleEmail, $request->sugeridoAlt, $user->nome);
                            } catch (\Exception $e) {
                                return response()->json([
                                    'success' => false,
                                    'message' => 'Oops, ocorreu um erro ao enviar uma notificação via e-mail, mas sua mensagem foi cadastrada em nosso sistema!'
                                ], 400);
                            }
                        }
                    }

                }

                foreach($usuariosIds as $u){
                    OrdemJob::OrdemJobHelper($u, $demanda->id);
                }

                foreach($colaboradoresIds as $c){
                    OrdemJob::OrdemJobHelper($c, $demanda->id);
                }

                $adminIds = User::where('notificar_email', 1)->whereNotIn('id', $colaboradoresIds)->where('tipo','admin')->get();

                foreach($adminIds as $usuario) {
                    OrdemJob::OrdemJobHelper($usuario->id, $demanda->id);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Pauta alterada com sucesso.'
                ], 200);

            }else{
                return response()->json([
                    'success' => false,
                    'message' => 'Ocorreu um erro, tente novamente mais tarde.'
                ], 400);
            }

        }

    }

    // private function enviarEmail($email, $nome, $actionLink, $bodyEmail, $titleEmail) {
    //     Mail::send('notify-job', ['action_link' => $actionLink, 'nome' => $nome, 'body' => $bodyEmail, 'titulo' => $titleEmail], function($message) use ($email, $titleEmail) {
    //         $message->from('informativo@uniflow.app.br', 'Informativo UniFlow Unicasa')
    //             ->to($email)
    //             // ->bcc('eduardo.8poroito@gmail.com')
    //             ->subject($titleEmail);
    //     });
    // }

    public function finalizeAgenda(Request $request, $id){

        $user = Auth::User();
        $lastNumber = '';
        $demandaPrazo = DemandaTempo::where('id', $id)
        ->with('agencia')->first();
        $demandaPrazo->finalizado = date('Y-m-d H:i:s');
        $demandaPrazo->aceitar_agencia = 1;
        $demandaPrazo->aceitar_colaborador = 1;
        $titleEmail = '';
        //verificar se foi atrasada

        $finalizado = Carbon::createFromFormat('Y-m-d H:i:s', $demandaPrazo->finalizado, 'UTC')->setTimezone('America/Sao_Paulo');
        $sugerido = Carbon::createFromFormat('Y-m-d H:i:s', $demandaPrazo->sugerido, 'UTC')->setTimezone('America/Sao_Paulo');

        // Calcular a diferença em minutos
        $diferencaMinutos = $sugerido->diffInMinutes($finalizado);

        if ($diferencaMinutos >= 1 && $finalizado > $sugerido) {
            $demandaPrazo->atrasada = 1;
        } else {
            $demandaPrazo->atrasada = 0;
        }

        $demandaPrazo->save();

        $verifyTimeAgenda = DemandaTempo::where('demanda_id', $request->demandaId)->where('finalizado', '=', null)->count();

        //pegar numero
        if (preg_match('/\d+/', $demandaPrazo->status, $matches)) {
            $lastNumber = $matches[0];
        }

        $demanda = Demanda::with(['demandaColaboradores' => function ($query) {
            $query->select('usuarios.id', 'usuarios.email', 'usuarios.notificar_email', 'usuarios.nome');
        }])->with('criador')->find($request->demandaId);

        $removerDemandasAtrasadas = DemandaAtrasada::where('demanda_id', $demanda->id)->delete();

        $colaboradoresIds = $demanda->demandaColaboradores->pluck('id')->toArray();

        $criado = Carbon::parse($demandaPrazo->criado);

        $newTimeLine =  new LinhaTempo();
        $newTimeLine->demanda_id = $request->demandaId;
        $newTimeLine->usuario_id = $user->id;
        $newTimeLine->criado = date('Y-m-d H:i:s');

        $criadorNotificacao = new Notificacao();
        $criadorNotificacao->usuario_id = $demanda->criador->id;
        $criadorNotificacao->demanda_id =  $request->demandaId;
        $criadorNotificacao->criado = date('Y-m-d H:i:s');
        $criadorNotificacao->visualizada = '0';
        $criadorNotificacao->tipo = 'entregue';

        $actionLink = route('Job', ['id' => $request->demandaId]);

        //LINHA TEMPO ENTREGUE PAUTA
        if($demandaPrazo->code_tempo == 'em-pauta'){

            $newTimeLine->status = 'Entregue pauta '.$lastNumber;
            $titleEmail = 'Entregue a '. strtolower($demandaPrazo->status);
            $newTimeLine->code = 'entregue';
            $newTimeLine->save();
            $criadorNotificacao->conteudo = $user->nome . ' entregou a '. strtolower($demandaPrazo->status).'.';
            $criadorNotificacao->save();

            foreach($colaboradoresIds as $c){
                if($demanda->criador->id != $c){
                    $notificacaoColaborador = new Notificacao();
                    $notificacaoColaborador->demanda_id = $demanda->id;
                    $notificacaoColaborador->criado = date('Y-m-d H:i:s');
                    $notificacaoColaborador->visualizada = '0';
                    $notificacaoColaborador->tipo = 'entregue';
                    $notificacaoColaborador->usuario_id = $c;
                    $notificacaoColaborador->conteudo = $criadorNotificacao->conteudo;
                    $notificacaoColaborador->save();
                }
            }

            if($verifyTimeAgenda == 0){
                $bodyEmail = 'Foi entregue a '.strtolower($demandaPrazo->status). ' e a agência está aguardando a sua análise.'.'<br/>'. 'Acesse pelo link logo abaixo.';
            }else{
                $bodyEmail = 'Foi entregue a '.strtolower($demandaPrazo->status). '<br/>'. 'Acesse pelo link logo abaixo.';
            }

            //criador
            if($demanda->criador->notificar_email == 1){
                $sendEmail = EnviarMail::enviarEmail($demanda->criador->email, $demanda->criador->nome, $actionLink, $bodyEmail, $titleEmail);
            }

            //colaboradores

            foreach($demanda['demandaColaboradores'] as $item){

                if($item->notificar_email == 1){
                    $sendEmail = EnviarMail::enviarEmail($item->email, $item->nome, $actionLink, $bodyEmail, $titleEmail);
                }
            }

        }

        //LINHA TEMPO ENTREGUE ALTERACAO

        if($demandaPrazo->code_tempo == 'alteracao'){

            $newTimeLine->status = 'Entregue pauta A'.$lastNumber;
            $titleEmail = 'Job '.$demanda->id.': '. $demanda->titulo. '. Entregue a alteração ' . $lastNumber;
            $newTimeLine->code = 'entregue-alteracao';
            $newTimeLine->save();
            $criadorNotificacao->conteudo =  $user->nome . ' entregou a alteração '. $lastNumber . '.';

            $criadorNotificacao->save();

            foreach($colaboradoresIds as $c){
                if($demanda->criador->id != $c){
                    $notificacaoColaborador = new Notificacao();
                    $notificacaoColaborador->demanda_id = $demanda->id;
                    $notificacaoColaborador->criado = date('Y-m-d H:i:s');
                    $notificacaoColaborador->visualizada = '0';
                    $notificacaoColaborador->tipo = 'entregue';
                    $notificacaoColaborador->usuario_id = $c;
                    $notificacaoColaborador->conteudo = $criadorNotificacao->conteudo;
                    $notificacaoColaborador->save();
                }
            }

            if($verifyTimeAgenda == 0){
                $bodyEmail = 'Foi entregue a alteração ' . $lastNumber.'. Essa foi a última alteração solicitada por você, e agora a agência está aguardando a sua análise.'. '<br/>'. 'Acesse pelo link logo abaixo.';
            }else{
                $bodyEmail = 'Foi entregue a alteração ' . $lastNumber.'.'. '<br/>'. 'Acesse pelo link logo abaixo.';
            }

            //criador
            if($demanda->criador->notificar_email == 1){
                $sendEmail = EnviarMail::enviarEmail($demanda->criador->email, $demanda->criador->nome,  $actionLink, $bodyEmail, $titleEmail);
            }

            //colaboradores

            foreach($demanda['demandaColaboradores'] as $item){

                if($item->notificar_email == 1){
                    $sendEmail = EnviarMail::enviarEmail($item->email, $item->email, $actionLink, $bodyEmail, $titleEmail);
                }
            }

        }

        //verificar se foi a última pauta e entregar job
        if ($verifyTimeAgenda == 0) {
            $demanda->em_pauta = 0;
            $demanda->finalizada = 0;
            $demanda->status = "Entregue";
            $demanda->entregue = 1;
            $demanda->em_alteracao = 0;
            $demanda->entregue_recebido = 0;

            $titleEmail = 'O job '.$demanda->id.' foi entregue';

            $criadorNotificacaoEntrega = new Notificacao();
            $criadorNotificacaoEntrega->usuario_id = $demanda->criador->id;
            $criadorNotificacaoEntrega->demanda_id =  $request->demandaId;
            $criadorNotificacaoEntrega->criado = date('Y-m-d H:i:s');
            $criadorNotificacaoEntrega->visualizada = '0';
            $criadorNotificacaoEntrega->tipo = 'entregue';
            $criadorNotificacaoEntrega->conteudo =  $user->nome .' alterou o status para entregue.';

            $criadorNotificacaoEntrega->save();

            foreach($colaboradoresIds as $c){
                if($demanda->criador->id != $c){
                    $notificacaoColaborador = new Notificacao();
                    $notificacaoColaborador->demanda_id = $demanda->id;
                    $notificacaoColaborador->criado = date('Y-m-d H:i:s');
                    $notificacaoColaborador->visualizada = '0';
                    $notificacaoColaborador->tipo = 'entregue';
                    $notificacaoColaborador->usuario_id = $c;
                    $notificacaoColaborador->conteudo =  $criadorNotificacaoEntrega->conteudo;
                    $notificacaoColaborador->save();
                }
            }

            $countDemandasReabertas = DemandaReaberta::where('demanda_id', $demanda->id)->count();

            if($countDemandasReabertas == 0){

                // criar a data final
                $dataFinal = Carbon::createFromFormat('Y-m-d H:i:s', $demanda->final, 'UTC')->setTimezone('America/Sao_Paulo');

                // verificar se este trabalho foi reaberto
                $verifyReOpenJob = DemandaReaberta::where('demanda_id', $id)->orderBy('id', 'DESC')->first();

                if ($verifyReOpenJob) {
                    // converter a data final para o fuso horário da América/São_Paulo
                    $dataFinal = Carbon::createFromFormat('Y-m-d H:i:s', $verifyReOpenJob->sugerido, 'UTC')->setTimezone('America/Sao_Paulo');
                }

                // comparar as datas
                $diferencaMinutosFinal = $dataFinal->diffInMinutes($finalizado);

                if ($diferencaMinutosFinal >= 1 && $finalizado > $dataFinal) {
                    $demanda->atrasada = 1;
                } else {
                    $demanda->atrasada = 0;
                }
            }
            $demanda->save();

        }

        return response()->json([
            'success' => true,
            'message' => 'Concluído com sucesso.'
        ], Response::HTTP_OK);

    }


    public function startAgenda(Request $request, $id){
        $user = Auth::User();
        $demandaPrazo = DemandaTempo::where('id', $id)->with('agencia')->first();
        $demandaPrazo->iniciado = date('Y-m-d H:i:s');
        $demandaPrazo->save();
        $demandaPrazoN = preg_replace("/[^0-9]/", "", $demandaPrazo->status);

        $demanda = Demanda::select('id', 'em_pauta', 'criador_id', 'status')->where('id', $demandaPrazo->demanda_id)->with('demandaColaboradores')->first();
        $colaboradoresIds = $demanda->demandaColaboradores->pluck('id')->toArray();

        $demanda->em_pauta = 1;
        $demanda->status = "Em pauta";
        $demanda->save();

        $notificacao = new Notificacao();
        $notificacao->demanda_id = $demandaPrazo->demanda_id;
        $notificacao->criado = date('Y-m-d H:i:s');
        $notificacao->visualizada = '0';
        $notificacao->conteudo = $user->nome .' iniciou a alteração ' . $demandaPrazoN .'.';

        $notificacao->tipo = 'criada';
        $notificacao->usuario_id = $demanda->criador_id;
        $notificacao->save();

        foreach($colaboradoresIds as $c){
            if($demanda->criador_id != $c){
                $notificacaoColaborador = new Notificacao();
                $notificacaoColaborador->demanda_id =$demandaPrazo->demanda_id;
                $notificacaoColaborador->criado = date('Y-m-d H:i:s');
                $notificacaoColaborador->visualizada = '0';
                $notificacaoColaborador->tipo = 'criada';
                $notificacaoColaborador->usuario_id = $c;
                $notificacaoColaborador->conteudo = $notificacao->conteudo;
                $notificacaoColaborador->save();
            }

        }

        $newTimeLine = new LinhaTempo();
        $newTimeLine->demanda_id = $demanda->id;
        $newTimeLine->usuario_id = $user->id;
        $newTimeLine->code = 'iniciada-alteracao';

        $newTimeLine->status = 'Em pauta A' . $demandaPrazoN;
        $newTimeLine->criado = date('Y-m-d H:i:s');
        $newTimeLine->save();

        return response()->json([
            'success' => true,
            'message' => 'Alteração iniciada com sucesso.'
        ], Response::HTTP_OK);

    }

    public function acceptTime(Request $request, $id){
        $user = Auth::User();
        $demandaPrazo = DemandaTempo::where('id', $id)
        ->with('agencia')
        ->with(['demanda' => function($query) {
            $query->select('criador_id', 'id');
        }])
        ->first();

        $demanda = Demanda::select('id', 'criador_id')->where('id', $demandaPrazo->demanda_id)->with('demandaColaboradores')->first();
        $colaboradoresIds = $demanda->demandaColaboradores->pluck('id')->toArray();

        //pegar numero
        if (preg_match('/\d+/', $demandaPrazo->status, $matches)) {
            $lastNumber = $matches[0];
        }

        $demandaPrazo->aceitar_agencia = 1;
        $demandaPrazo->save();

        //notificar

        $notificacao = new Notificacao();
        $notificacao->demanda_id = $demandaPrazo->demanda_id;
        $notificacao->criado = date('Y-m-d H:i:s');
        $notificacao->visualizada = '0';

        if($demandaPrazo->code_tempo == 'em-pauta'){
            $notificacao->conteudo = $user->nome .' aceitou o novo prazo da ' . strtolower($demandaPrazo->status).' do job '.$demanda->id.'.';
        }else if($demandaPrazo->code_tempo == 'alteracao'){
           $notificacao->conteudo = $user->nome. ' aceitou o novo prazo da alteração ' . $lastNumber.' do job '.$demanda->id.'.';
        }

        $notificacao->tipo = 'criada';
        $notificacao->usuario_id = $demandaPrazo->demanda->criador_id;
        $notificacao->save();

        foreach($colaboradoresIds as $c){
            if($demanda->criador_id != $c){
                $notificacaoColaborador = new Notificacao();
                $notificacaoColaborador->demanda_id = $demanda->id;
                $notificacaoColaborador->criado = date('Y-m-d H:i:s');
                $notificacaoColaborador->visualizada = '0';
                $notificacaoColaborador->tipo = 'criada';
                $notificacaoColaborador->usuario_id = $c;
                $notificacaoColaborador->conteudo = $notificacao->conteudo;
                $notificacaoColaborador->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Prazo aceito.'
        ], 200);


    }

    public function addUser(Request $request){
        $getU = DemandaUsuario::where('demanda_id', $request->demandaId)->where('usuario_id', $request->checkboxValue)->count();
        $getD = DemandaUsuario::where('demanda_id', $request->demandaId)->count();

        $getUfirst = DemandaUsuario::where('demanda_id', $request->demandaId)->first();

        if($request->checkboxValue == $getUfirst->usuario_id){
            return response()->json([
                'type' => 'error',
                'message' => 'Você não pode remover este usuário.'
            ], Response::HTTP_OK);
        }


        if($getU > 0 ){
            if($getD > 1){
                DemandaUsuario::where('demanda_id', $request->demandaId)->where('usuario_id', $request->checkboxValue)->delete();
                DemandaOrdemJob::where('usuario_id', $request->checkboxValue)->where('demanda_id', $request->demandaId)->delete();
                $verifyNotDelete = Notificacao::where('tipo', 'convidado')->where('demanda_id', $request->demandaId)->where('usuario_id', $request->checkboxValue)->delete();

                return response()->json([
                    'type' => 'success',
                    'message' => 'Removido com sucesso.'
                ], Response::HTTP_OK);


            }else{
                return response()->json([
                    'type' => 'error',
                    'message' => 'Você não pode remover este usuário.'
                ], Response::HTTP_OK);
            }

        }else{
            $newUser =  new DemandaUsuario();
            $newUser->usuario_id = $request->checkboxValue;
            $newUser->demanda_id = $request->demandaId;
            $newUser->save();

            $responseOrdem = OrdemJob::OrdemJobHelper($request->checkboxValue, $request->demandaId);
            $verifyNot = Notificacao::where('tipo', 'convidado')->where('demanda_id', $request->demandaId)->where('usuario_id', $request->checkboxValue)->count();

            if($verifyNot == 0){
                $notificacao = new Notificacao();
                $notificacao->demanda_id =  $request->demandaId;
                $notificacao->criado = date('Y-m-d H:i:s');
                $notificacao->visualizada = '0';
                $notificacao->conteudo = 'Você foi selecionado para participar do Job ('.$request->demandaId.')';
                $notificacao->tipo = 'convidado';
                $notificacao->usuario_id = $request->checkboxValue;
                $notificacao->save();
            }


            return response()->json([
                'type' => 'success',
                'message' => 'Adicionado com sucesso.'
            ], Response::HTTP_OK);
        }

    }

    public function addCol(Request $request){
        $getU = DemandaColaborador::where('demanda_id', $request->demandaId)->where('usuario_id', $request->checkboxValue)->count();

        $demanda = Demanda::select('id', 'criador_id')->where('id', $request->demandaId)->first();

        if($demanda->criador_id == $request->checkboxValue){
            return response()->json([
                'type' => 'error',
                'message' => 'Você não pode remover este usuário.'
            ], Response::HTTP_OK);
        }

        if($getU > 0 ){
            DemandaColaborador::where('demanda_id', $request->demandaId)->where('usuario_id', $request->checkboxValue)->delete();
            DemandaOrdemJob::where('usuario_id', $request->checkboxValue)->where('demanda_id', $request->demandaId)->delete();
            $verifyNotDelete = Notificacao::where('tipo', 'convidado')->where('demanda_id', $request->demandaId)->where('usuario_id', $request->checkboxValue)->delete();

            return response()->json([
                'type' => 'success',
                'message' => 'Removido com sucesso.'
            ], Response::HTTP_OK);

        }else{
            $newUser =  new DemandaColaborador();
            $newUser->usuario_id = $request->checkboxValue;
            $newUser->demanda_id = $request->demandaId;
            $newUser->save();

            $responseOrdem = OrdemJob::OrdemJobHelper($request->checkboxValue, $request->demandaId);
            $verifyNot = Notificacao::where('tipo', 'convidado')->where('demanda_id', $request->demandaId)->where('usuario_id', $request->checkboxValue)->count();

            if($verifyNot == 0){
                $notificacao = new Notificacao();
                $notificacao->demanda_id =  $request->demandaId;
                $notificacao->criado = date('Y-m-d H:i:s');
                $notificacao->visualizada = '0';
                $notificacao->conteudo = 'Você foi selecionado para participar do Job ('.$request->demandaId.')';
                $notificacao->tipo = 'convidado';
                $notificacao->usuario_id = $request->checkboxValue;
                $notificacao->save();
            }

            return response()->json([
                'type' => 'success',
                'message' => 'Adicionado com sucesso.'
            ], Response::HTTP_OK);
        }

    }

    public function readBriefing(Request $request){
        $loggedUser = Auth::User();
        $user = User::where('id', $request->user)->first();
        $demanda = Demanda::where('id', $request->demanda_id)->with('demandaColaboradores')->with('demandasUsuario')->first();
        $demandaUsuariosIds = $demanda->demandasUsuario->pluck('id')->toArray();
        $userAg = in_array($loggedUser->id, $demandaUsuariosIds);

        $briefingCount = BriefingLido::where('demanda_id', $demanda->id)->where('usuario_id', $user->id)->count();

        if($userAg == 1 && $briefingCount == 0){

            $demandaColaboradoresIds[] = $demanda->criador_id;
            $demandaColaboradoresIds = array_merge($demandaColaboradoresIds, $demanda->demandaColaboradores->pluck('id')->toArray());
            $demandaColaboradoresIds = array_unique($demandaColaboradoresIds);
    
            $notificacoesReq = [];
            foreach ($demandaColaboradoresIds as $usuario) {
                if ($user->id != $usuario) {
                    $notificacao = [
                        'usuario_id' => $usuario,
                        'demanda_id' => $demanda->id,
                        'criado' => date('Y-m-d H:i:s'),
                        'conteudo' => $loggedUser->nome.' visualizou o briefing da demanda '.$demanda->id.'.',
                        'visualizada' => '0',
                        'tipo' => 'briefing',
                        'tipo_referencia' => 'demanda-briefing-'.$demanda->id,

                    ];
                    $notificacoesReq[] = $notificacao;
                }
            }

            Notificacao::insert($notificacoesReq);

            $newBriefing = new BriefingLido();
            $newBriefing->usuario_id = $user->id;
            $newBriefing->demanda_id = $demanda->id;
            $response = $newBriefing->save();
            
            if($response){
                return true;
            }
        }
    }
}
