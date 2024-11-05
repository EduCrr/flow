@php
    // $layout = $loggedUser->tipo == 'agencia' ? 'layouts.agencia' : ($loggedUser->tipo == 'admin' || $loggedUser->tipo == 'admin_8' ? 'layouts.admin' : 'layouts.colaborador');
    $layout = ($loggedUser->tipo == 'agencia') ? 'layouts.agencia' :
    (($loggedUser->tipo == 'admin') ? 'layouts.admin' :
    (($loggedUser->tipo == 'admin_8') ? 'layouts.admin8' : 'layouts.colaborador'));

@endphp

@extends($layout)

@section('title', 'Job '. $demanda->id)

@section('css')
@endsection

@section('content')
    <section>
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="">
                                <div class="custom-tab tab-profile">
                                    <!-- Nav tabs -->
                                    <ul class="nav nav-tabs nav-tabs-custom customResponsiveUl" role="tablist">
                                        <li class="nav-item">
                                            <a id="projectLink" class="nav-link active pb-3 pt-0" data-bs-toggle="tab" href="#job"
                                                role="tab"><i class="fas fa-check-circle me-2"></i>Job</a>
                                        </li>
                                        <li class="nav-item">
                                            <a id="pautasLink" class="nav-link pb-3 pt-0" data-bs-toggle="tab" href="#pautas"
                                                role="tab"><i class="fas fa-calendar-alt  me-2"></i>Pautas
                                                @if($demanda->count_prazos > 0)
                                                    <span class="badge bg-danger rounded-pill">{{$demanda->count_prazos}}</span>
                                                @endif
                                            </a>
                                        </li>
                                        @if(in_array($loggedUser->id, $agenciaUsersIds))
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="dropdown userslist">
                                                    <button style="color: #495057"  class="nav-link pb-3 pt-0 dropdown-toggle" type="button"
                                                        id="dropdownMenuButton" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-user  me-2"></i>Adicionar usuários
                                                    </button>

                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        @foreach($agenciaUsuarios['agenciasUsuarios'] as $ag)
                                                            @if($ag->id !== $demanda->criador_id)
                                                            <div class="my-2">
                                                                <div class="form-check"style="margin-left: 5px;">
                                                                    <input style="margin-right: 5px;" type="checkbox" id="{{$ag->id}}" class="form-check-input form-ag" value="{{$ag->id}}" data-parsley-multiple="groups" data-parsley-mincheck="2"
                                                                        @if($demanda['demandasUsuario']->contains('id', $ag->id)) checked @endif
                                                                        @if ($demanda['demandasUsuario'][0]->id === $ag->id) disabled @endif>
                                                                    <label class="form-check-label" for="{{$ag->id}}">{{$ag->nome}}</label>
                                                                </div>
                                                            </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif
                                        @if(($demanda->subCriador != null && $loggedUser->id == $demanda->subCriador->id) || $loggedUser->id == $demanda->criador_id)
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="dropdown userslist">
                                                    <button style="color: #495057"  class="nav-link pb-3 pt-0 dropdown-toggle" type="button"
                                                        id="dropdownMenuButton" data-bs-toggle="dropdown"
                                                        aria-haspopup="true" aria-expanded="false">
                                                        <i class="fas fa-user  me-2"></i>Adicionar colaboradores
                                                    </button>

                                                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                                                        @foreach($colaboradores as $col)
                                                            <div class="my-2">
                                                                <div class="form-check"style="margin-left: 5px;">
                                                                    <input style="margin-right: 5px;" type="checkbox" id="{{$col->id}}" class="form-check-input form-col" value="{{$col->id}}" data-parsley-multiple="groups" data-parsley-mincheck="2"
                                                                        @if($demanda['demandaColaboradores']->contains('id', $col->id)) checked @endif
                                                                        @if ($demanda->criador_id === $col->id) disabled checked @endif>
                                                                    <label class="form-check-label" for="{{$col->id}}">{{$col->nome}}</label>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @endif

                                        @if($loggedUser->tipo == 'agencia' && $demanda->subCriador)
                                            @if($loggedUser->id == $demanda->subCriador->id)
                                                <div class="btnCreate">
                                                    <a href="{{ route('Agencia.editar', ['id' => $demanda->id]) }}" class="btn">Editar</a>
                                                </div>
                                            @endif
                                        @endif

                                        @if ($showColaborador || $loggedUser->id == $demanda->criador_id )
                                            <div class="btnCreate">
                                                @if($demanda->finalizada == 0 || $demanda->pausado == 0)
                                                <button data-bs-toggle="modal" data-bs-target="#modalRecorrencia" class="btn btn-primary recorrencia">Criar recorrência</button>
                                                @endif
                                                @if($loggedUser->id == $demanda->criador_id)
                                                    <a href="{{route('Job.editar' , ['id' => $demanda->id])}}" class="btn ">Editar</a>
                                                @endif
                                                <div class="card">
                                                    <div class="modal fade" id="modalRecorrencia" tabindex="-1" role="dialog">
                                                        <div class="modal-dialog" role="document">
                                                            <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                <form class="responseAjax" method="POST" action="{{route('Campanha.recorrencia', ['id' => $demanda->id])}}">
                                                                    @csrf
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title align-self-center"
                                                                            id="modalReabirJob">Criar recorrência</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <div class="row">
                                                                            <div class="col-md-12">
                                                                                <div class="mb-3 no-margin">
                                                                                    <label class="mb-1">Campanha</label>
                                                                                    <input name="campanha" class="form-control"  type="text" />
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-12">
                                                                                <div class="mb-3 no-margin">
                                                                                    <label class="mb-1">Padrão de recorrência</label>
                                                                                    <select name="tipoRecorrencia" class="form-select select2 tipoRecorrencia">
                                                                                        <option value="Mensal">Mensal</option>
                                                                                        <option value="Anual">Anual</option>
                                                                                        <option value="Semanal">Semanal</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-12 semanal hidden">
                                                                                <div class="mb-3 no-margin">
                                                                                    <label class="mb-1">Semanas:</label>
                                                                                    <input type="text" class="form-control date-multiple" placeholder="Intervalo de datas" name="dateRange">
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6 mensal">
                                                                                <div class="mb-3 no-margin">
                                                                                    <label class="mb-1">Começa em:</label>
                                                                                    <input name="inicio" class="form-control" type="month"/>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6 mensal">
                                                                                <div class="mb-3 no-margin">
                                                                                    <label class="mb-1">Termina em:</label>
                                                                                    <input name="final" class="form-control" type="month"/>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-12 mensal">
                                                                                <div class="mb-3 no-margin">
                                                                                    <label class="mb-1">Dia das entregas:</label>
                                                                                    <input name="dia_ocorrencia" class="form-control" type="number" min="1" max="31"/>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6 anual hidden">
                                                                                <div class="mb-3 no-margin">
                                                                                    <label class="mb-1">Selecione o ano inicial e o dia:</label>
                                                                                    <input name="anoInicial" class="form-control" type="date"/>
                                                                                </div>
                                                                            </div>
                                                                            <div class="col-md-6 anual hidden">
                                                                                <div class="mb-3 no-margin">
                                                                                    <label class="mb-1">Selecione o ano final:</label>
                                                                                    <select name="anoFinal" id="yearpicker" class="form-select select2">
                                                                                        <?php
                                                                                            $startYear = date("Y");
                                                                                            $lastYear = $startYear + 6;
                                                                                            for ($i = $startYear; $i <= $lastYear; $i++) {
                                                                                                echo "<option value='$i'>$i</option>";
                                                                                            }
                                                                                        ?>
                                                                                    </select>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-light"
                                                                            data-bs-dismiss="modal">Fechar</button>
                                                                        <button type="submit" class="btn btn-primary submitModal">Confirmar</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                    </ul>
                                    <div class="tab-content pt-4">
                                        <div class="tab-pane active" id="job" role="tabpanel">
                                            <div class="row">
                                                <div class="progressiveBar">
                                                    <small class="float-end ms-2 font-size-12">{{$demanda->porcentagem}}%</small>
                                                    <div class="progress" style="height: 5px">
                                                        <div
                                                        class="progress-bar bg-primary"
                                                        role="progressbar"
                                                        style="width: {{$demanda->porcentagem}}%"
                                                        aria-valuenow="{{$demanda->porcentagem}}"
                                                        aria-valuemin="0"
                                                        aria-valuemax="100"
                                                        ></div>
                                                    </div>

                                                </div>
                                                @if(count($lineTime) > 0)
                                                <div class="col-xl-12">
                                                    <div class="card adjustHeightCard">
                                                        <div class="card-body">
                                                            <div class="lineTime">
                                                                <div class="d-flex justify-content-center" style="height: 15px;">
                                                                    <div class="spinner-border" role="status">
                                                                        <span class="sr-only">Carregando...</span>
                                                                    </div>
                                                                </div>
                                                                <ul class="timeline" id="timeline">
                                                                    <div class="carousel">
                                                                        @foreach ($lineTime as $line )
                                                                        <li class="li complete">
                                                                            <div class="timestamp">
                                                                                <span class="author">{{ $line->usuario->nome }}</span>
                                                                                <span class="date"> {{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $line->criado)->format('d/m/Y H:i'); }}<span>
                                                                            </div>
                                                                            <div class="status">
                                                                                @if ($line->code == 'questionamento')
                                                                                    <img class="iconStatus" src="{{url('assets/images/atention.png')}}" >
                                                                                    @elseif($line->code == 'reaberto')
                                                                                    <img class="iconStatus" src="{{url('assets/images/reload.png')}}" >
                                                                                    @elseif($line->code == 'alteracao')
                                                                                    <img class="iconStatus" src="{{url('assets/images/alteration.png')}}" >
                                                                                    @elseif($line->code == 'removido')
                                                                                    <img class="iconStatus" src="{{url('assets/images/delete.png')}}" >
                                                                                    @elseif($line->code == 'congelado')
                                                                                    <img class="iconStatus" src="{{url('assets/images/pause.png')}}" >
                                                                                    @else
                                                                                    <img class="iconStatus" src="{{url('assets/images/verify.png')}}" >

                                                                                @endif
                                                                                <h6> {{ $line->status }} </h6>
                                                                            </div>
                                                                        </li>
                                                                        @endforeach
                                                                        @if($demanda->finalizada != 1)
                                                                        <li class="li" style="margin-top:39px">
                                                                            <div class="timestamp">
                                                                                <span class="author"></span>
                                                                                <span class="date"><span>
                                                                            </div>
                                                                            <div class="status status-final">
                                                                                <h6>Aguardando próxima etapa </h6>
                                                                            </div>
                                                                        </li>
                                                                        @endif
                                                                    </div>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                                <div class="col-xl-12">
                                                @if(count($demanda['recorrenciasMensais']) > 0)
                                                    <div class="col-xl-12">
                                                        <div class="card cardRec" data-simplebar style="max-height: 700px;">
                                                            <div class="card-body">
                                                                <div class="adjustBriefing">
                                                                    <h5 class="card-title comments">Entregas mensais</h5>
                                                                    <a class="arounded" data-bs-toggle="collapse" href="#collapseMensal" role="button" aria-expanded="false" aria-controls="collapseMensal"></a>
                                                                </div>
                                                                <div class="collapse" id="collapseMensal">
                                                                    @foreach ($demanda['recorrenciasMensais'] as $item )
                                                                    <div class="col-xl-12">
                                                                        <div class="card cardFirst">
                                                                            <div class="card-header">
                                                                                <div>
                                                                                    <p class="mb-3"><strong>Campanha:</strong> {!! $item->titulo !!}</p>
                                                                                    @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                        <div class="reqBtns">
                                                                                            <span class="editBt" data-bs-toggle="modal" data-bs-target="#modalCreateRecMensal-{{$item->id}}"><i style="cursor: pointer" class="fas fa-plus"></i></span>
                                                                                            <form class="responseAjax" action="{{ route('Recorrencia.delete', ['id' => $item->id]) }}" method="POST">
                                                                                                @csrf
                                                                                                @method('DELETE')
                                                                                                <div class="right gap-items-2">
                                                                                                    <button type="submit" class="submitForm deleteBtn"> <i class="fas fa-trash"></i></button>
                                                                                                </div>
                                                                                            </form>
                                                                                        </div>
                                                                                        <div class="card" style="margin-bottom: 0px">
                                                                                            <div class="modal fade" id="modalCreateRecMensal-{{$item->id}}" tabindex="-1" role="dialog">
                                                                                                <div class="modal-dialog" role="document">
                                                                                                    <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                                        <form class="responseAjax" method="POST" action="{{route('Recorrencia.mensal_create_action', ['id' => $item->id])}}">
                                                                                                            @csrf
                                                                                                            <div class="modal-header">
                                                                                                                <h5 class="modal-title align-self-center"
                                                                                                                    id="modalReabirJob">Criar recorrência</h5>
                                                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                            </div>
                                                                                                            @component('components.RecorrenciaComponentCriar', ['tipo' => 'mensal'])@endcomponent
                                                                                                        </form>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="">
                                                                                    <p class="data-attributes mb-0">
                                                                                        <div class="percent">
                                                                                            <p style="display:none;">{{$item->porcentagemEntregue}}%</p>
                                                                                        </div>            
                                                                                    </p>
                                                                                </div>
                                                                            </div>
                                                                            @foreach($item['recorrencias'] as $key => $m)
                                                                            @php
                                                                                $backgroundColor = '#ff8538'; // valor padrão

                                                                                if ($m->status == 'pendente') {
                                                                                    $backgroundColor = '#ff8538';
                                                                                } elseif ($m->status == 'Em pauta') {
                                                                                    $backgroundColor = '#f9bc0b';
                                                                                } elseif ($m->status == 'Entregue') {
                                                                                    $backgroundColor = '#44a2d2';
                                                                                }
                                                                                elseif ($m->status == 'Finalizado') {
                                                                                    $backgroundColor = '#3dbb3d';
                                                                                }
                                                                            @endphp
                                                                            <div class="card cardSingle">
                                                                                <div class="card-body"  style="border: 1px solid {{$backgroundColor}};">
                                                                                    <div class="cardSingleInitial" data-idAg="{{$m->id}}" data-isReadAg{{$m->id}}="{{$m->hasComentariosNaoLidos}}">
                                                                                        @if($m->hasComentariosNaoLidos)
                                                                                            <div class="mt-3">
                                                                                                <span><i class="fas fa-comment-dots msg"></i></span>
                                                                                            </div>
                                                                                        @endif
                                                                                        @if($m->entregue == 1)
                                                                                            @if($m->finalizado == 0)
                                                                                                @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                                <div class="mt-3">
                                                                                                    <form class="responseAjax responseAjaxReq d-flex"  method="POST" action="{{route('Recorrencia.Pauta.finalizar_tempo', ['id' => $m->id])}}">
                                                                                                        @csrf
                                                                                                        <div class="checkbox my-2">
                                                                                                            <div class="form-check adjustStatus" style="padding-left: 0px">
                                                                                                                <button type="submit"  class="form-control reopenJob fin blockBtn submitFinalize">Finalizar</button>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </form>
                                                                                                </div>
                                                                                                @endif
                                                                                            @endif
                                                                                        <div class="mt-3">
                                                                                            <p><span style="font-weight: 500; color: {{$m->atrasada == 1 ? '#f73e1d' : '#3dbb3d'}};">Entregue<span></span>@if($m->atrasada == 1)<span> com atraso</span>@endif:</span> {{ \Carbon\Carbon::parse($m->data_entrega)->locale('pt_BR')->isoFormat('DD/MM/YYYY HH:mm')}}
                                                                                            </p>
                                                                                        </div>
                                                                                        @endif
                                                                                        @if($m->titulo)
                                                                                        <div class="d-flex align-items-center mt-3">
                                                                                            <p class="mb-0">Título: {{$m->titulo}}</p>
                                                                                        </div>
                                                                                        @endif
                                                                                        <div class="mt-3">
                                                                                            <p class="mb-0 font-size-13">
                                                                                               Prazo para entrega:
                                                                                                <span class="text-muted" style="text-transform: capitalize;">
                                                                                                    {{ \Carbon\Carbon::parse($m->data)->locale('pt_BR')->isoFormat('DD/MM/YYYY  MMMM') }}
                                                                                                </span>
                                                                                                @php
                                                                                                    $dataAtualReq = \Carbon\Carbon::now()->startOfDay();
                                                                                                    $mData = \Carbon\Carbon::parse($m->data)->startOfDay();
                                                                                                @endphp
                                                                                                @if ($m->entregue == 0 && $dataAtualReq->greaterThan($mData))
                                                                                                    <span class="atrasado">Atrasada</span>
                                                                                                @endif
                                                                                            </p>
                                                                                        </div>
                                                                                        <div class="mt-3">
                                                                                            <ul class="list-unstyled list-inline mb-0">
                                                                                                <li class="list-inline-item">
                                                                                                    <p class="text-muted font-size-13 mb-0">
                                                                                                    Status :
                                                                                                    </p>
                                                                                                </li>
                                                                                                <li class="list-inline-item">
                                                                                                    <span class="badge" style="padding: 5px; background-color: {{ $backgroundColor }};"
                                                                                                    >{{$m->status}}</span
                                                                                                    >
                                                                                                </li>
                                                                                            </ul>
                                                                                        </div>
                                                                                        @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                        <div class="mt-3" style="margin-bottom: 1rem;">
                                                                                            <div class="btns btnAbosulte">
                                                                                                <span data-bs-toggle="modal" data-bs-target="#modalEditRecMensal-{{$m->id}}" class="editBt">
                                                                                                    <i style="cursor: pointer" class="fas fa-edit"></i>
                                                                                                </span>
                                                                                                <form class="responseAjax" action="{{ route('Recorrencia.single_delete', ['id' => $m->id]) }}" method="POST">
                                                                                                    @csrf
                                                                                                    @method('DELETE')
                                                                                                    <div class="right gap-items-2">
                                                                                                        <button type="submit" class="submitForm deleteBtn"> <i class="fas fa-trash"></i></button>
                                                                                                    </div>
                                                                                                </form>
                                                                                            </div>
                                                                                        </div>
                                                                                        @endif
                                                                                        @if($showAg)
                                                                                        <div class="btns btnAbosulte mt-3">
                                                                                            @if($m->em_pauta == 0 && $m->entregue == 0 && $m->em_alteracao == 0)
                                                                                                <span class="form-control alterarPrazoRec alt" data-bs-toggle="modal" data-bs-target="#modalPautaRecorrenciaEditar{{ $m->id }}">Alterar prazo</span>
                                                                                                <form class="responseAjax" method="POST" action="{{route('Recorrencia.Pauta.iniciar_tempo', ['id' => $m->id])}}">
                                                                                                    @csrf
                                                                                                    <input type="submit" class="form-control reopenJob fin blockBtn submitQuest" value="Iniciar">
                                                                                                </form>
                                                                                            @endif
                                                                                            @if($m->em_pauta == 1 && $m->entregue == 0)
                                                                                                @if($m->count_ajustes == 0)
                                                                                                    <span class="form-control alterarPrazoRec alt" data-bs-toggle="modal" data-bs-target="#modalPautaRecorrenciaEditar{{ $m->id }}">Alterar prazo</span>
                                                                                                        <form class="responseAjax" method="POST" action="{{route('Recorrencia.Pauta.entregar_tempo', ['id' => $m->id])}}">
                                                                                                            @csrf
                                                                                                            <input type="submit" class="form-control reopenJob fin blockBtn submitQuest" value="Concluir">
                                                                                                        </form>
                                                                                                @else
                                                                                                    <p class="bgText" style="background-color: #f73e1d; cursor:auto;">Alterações pendetes ({{$m->count_ajustes}})</p>
                                                                                                @endif
                                                                                            @endif
                                                                                            <div class="card">
                                                                                                <div class="modal fade" id="modalPautaRecorrenciaEditar{{$m->id}}" role="dialog">
                                                                                                    <div class="modal-dialog" role="document">
                                                                                                        <div class="modal-content">
                                                                                                            <form class="responseAjax" method="POST"action="{{route('Recorrencia.data', ['id' => $m->id])}}">
                                                                                                                @csrf
                                                                                                                <div class="modal-header">
                                                                                                                    <h5 class="modal-title align-self-center"
                                                                                                                        id="modalPautaRecorrenciaEditar{{$m->id}}">Alterar data</h5>
                                                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                                </div>
                                                                                                                <div class="modal-body">
                                                                                                                    <div class="row">
                                                                                                                        <div class="col-md-12">
                                                                                                                            <div class="mb-3 no-margin">
                                                                                                                                <input required name="data_recorrencia" value="{{$m->data}}" class="form-control dataRecorrencia"  type="date" />
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <div class="modal-footer">
                                                                                                                    <button type="button" class="btn btn-light"
                                                                                                                        data-bs-dismiss="modal">Fechar</button>
                                                                                                                    <button type="submit" class="btn btn-primary submitModal">Confirmar</button>
                                                                                                                </div>
                                                                                                            </form>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        @endif
                                                                                    </div>
                                                                                    <div class="contentRecorrencia mt-3" style="display: none;">
                                                                                        @if($m->descricao)
                                                                                        <div class="blockSpace">
                                                                                            <div class="">
                                                                                                <p class="mb-0 font-size-13">
                                                                                                    Briefing:
                                                                                                </p>
                                                                                                <div class="text-muted font-size-13 text-muted-tiny commentsUsers">{{$m->descricao}}</div>
                                                                                            </div>
                                                                                        </div>
                                                                                        @endif
                                                                                        <div class="blockSpace">
                                                                                            <div class="initialTitle commentsAlteracoes">
                                                                                                <h5 style="margin-bottom: 0px;" class="card-title comments">Comentários</h5>
                                                                                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalComentarioMensal-{{$m->id}}" >Criar comentário</button>
                                                                                            </div>
                                                                                            @foreach($m['comentarios'] as $key => $comentario)
                                                                                            <div class="activity">
                                                                                                <img alt="" class="img-activity" src="{{url('/assets/images/users/')}}/{{$comentario['usuario']->avatar }}">
                                                                                                <div class="time-item" id="{{  preg_replace('/\s+/', '', 'comentario-recorrencia-'.$comentario->id)}}">
                                                                                                    <div class="item-info">
                                                                                                        <div class="text-muted float-end font-size-10 dateComentary">
                                                                                                            {{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $comentario->criado)->format('d/m/Y H:i'); }}
                                                                                                        </div>
                                                                                                        <div class="status statusComments">
                                                                                                            <h5 class="mb-1">{{ $comentario['usuario']->nome }}</h5>
                                                                                                            @if($comentario->usuario_id == $loggedUser->id && count($comentario->lidos) > 0)
                                                                                                            <div class="service_box">
                                                                                                                <div class="service_box_inner">
                                                                                                                    <div class="services_box_icon">
                                                                                                                        <div class="contentLido">
                                                                                                                            <i style="font-size: 12px;" class="fa fa-check"></i>
                                                                                                                            <i style="font-size: 12px; z-index: -1;" class="fa fa-check"></i>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                    <div class="service_content">
                                                                                                                        <ul class="services_box_list">
                                                                                                                            <li class="text-custom">
                                                                                                                                @foreach($comentario->lidos as $lido)
                                                                                                                                    <span class="services_box__list-text">{!! $lido->usuario->nome !!}<br/> </span>
                                                                                                                                @endforeach
                                                                                                                            </li>
                                                                                                                        </ul>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            @endif
                                                                                                        </div>
                                                                                                        <p class="text-muted font-size-13 text-muted-tiny commentsUsers" style="margin-top: 15px">
                                                                                                            {{ $comentario->descricao }}
                                                                                                        </p>
                                                                                                        @if($loggedUser->id == $comentario->usuario_id)
                                                                                                        <div class="btns">
                                                                                                            <span data-bs-toggle="modal" data-bs-target="#modalComentarioMensalEditar-{{$comentario->id}}"class="editBt">
                                                                                                                <i style="cursor: pointer" class="fas fa-edit"></i>
                                                                                                            </span>
                                                                                                            <form class="responseAjax" action="{{route('Recorrencia.comentario_delete', ['id' => $comentario->id])}}" method="post">
                                                                                                                @csrf
                                                                                                                @method('DELETE')
                                                                                                                <div class="right gap-items-2">
                                                                                                                    <button type="submit" class="submitForm deleteBtn"> <i class="fas fa-trash"></i></button>
                                                                                                                </div>
                                                                                                            </form>
                                                                                                            <div class="modal fade modalRecorrenciaComentarioEditar" id="modalComentarioMensalEditar-{{$comentario->id}}" role="dialog">
                                                                                                                <div class="modal-dialog" role="document">
                                                                                                                    <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                                                        @component('components.RecorrenciaComponentComentarioEditar', ['req' => $comentario])@endcomponent
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                        @endif
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                        <div class="blockSpace">
                                                                                            <div class="initialTitle commentsAlteracoes commentsAlteracoes" style="margin-bottom: 0px;">
                                                                                                <h5 style="margin-bottom: 0px;" class="card-title">Alterações</h5>
                                                                                                @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCriarAjusteMensal-{{$m->id}}" >Criar alteração</button>
                                                                                                @endif
                                                                                            </div>
                                                                                            @foreach($m['ajustes'] as $key => $ajuste)
                                                                                                <div class="recorrenciaSingle adjustRecorrenciaAj {{ $ajuste->entregue == 1 ? 'notifyContent' : '' }}">
                                                                                                    <div class="d-flex align-items-start" style="width: 100%">
                                                                                                        <div class="flex-1">
                                                                                                            <div class="ajusteContent">
                                                                                                                <div class="{{ $ajuste->entregue == 1 ? 'mt-0' : 'mt-3' }} initialAjuste">
                                                                                                                    <p class="mb-0 font-size-13">
                                                                                                                        <strong>Alteração {!! $key + 1 !!}</strong>
                                                                                                                    </p>
                                                                                                                    <div class="editarAjuste">
                                                                                                                        @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                                                        <div class="btns">
                                                                                                                            <span data-bs-toggle="modal" data-bs-target="#modalEditAjusteMensal-{{$ajuste->id}}" class="editBt">
                                                                                                                                <i style="cursor: pointer" class="fas fa-edit"></i>
                                                                                                                            </span>
                                                                                                                            <div class="right gap-items-2">
                                                                                                                                <form class="responseAjax" action="{{ route('Recorrencia.ajuste_delete', ['id' => $ajuste->id]) }}" method="POST">
                                                                                                                                    @csrf
                                                                                                                                    @method('DELETE')
                                                                                                                                    <button type="submit" class="submitForm deleteBtn"><i class="fas fa-trash"></i></button>
                                                                                                                                </form>
                                                                                                                            </div>
                                                                                                                            <div class="modal fade" id="modalEditAjusteMensal-{{$ajuste->id}}"  tabindex="-1" role="dialog">
                                                                                                                                <div class="modal-dialog" role="document">
                                                                                                                                    <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                                                                        @component('components.RecorrenciaComponentEditarAjuste', ['req' => $ajuste, 'tipo' => 'mensal'])@endcomponent
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        @else
                                                                                                                        <div class="btns" style="justify-content: flex-start;">
                                                                                                                            @if ($showAg)
                                                                                                                                @if($ajuste->em_pauta == 0 && $ajuste->entregue == 0)
                                                                                                                                    <span class="form-control alterarPrazoRec alt" data-bs-toggle="modal" data-bs-target="#modalPautaRecorrenciaAjusteEditar{{ $ajuste->id }}">Alterar prazo</span>
                                                                                                                                    <form class="responseAjax" method="POST" action="{{route('Recorrencia.Ajuste.Pauta.entregar_tempo', ['id' => $ajuste->id])}}">
                                                                                                                                        @csrf
                                                                                                                                        <input type="hidden" name="keyAlteracao" value="{{$key}}">
                                                                                                                                        <input type="submit" class="form-control reopenJob fin blockBtn submitQuest" value="Iniciar">
                                                                                                                                    </form>
                                                                                                                                @endif
                                                                                                                                @if($ajuste->em_pauta == 1 && $ajuste->entregue == 0)
                                                                                                                                    <span class="form-control alterarPrazoRec alt" data-bs-toggle="modal" data-bs-target="#modalPautaRecorrenciaAjusteEditar{{ $ajuste->id }}">Alterar prazo</span>
                                                                                                                                    <form class="responseAjax" method="POST" action="{{route('Recorrencia.Ajuste.Pauta.finalizar_tempo', ['id' => $ajuste->id])}}">
                                                                                                                                        @csrf
                                                                                                                                        <input type="hidden" name="keyAlteracao" value="{{$key}}">
                                                                                                                                        <input type="submit" class="form-control reopenJob fin blockBtn submitQuest" value="Concluir">
                                                                                                                                    </form>
                                                                                                                                @endif
                                                                                                                                <div class="card">
                                                                                                                                    <div class="modal fade" id="modalPautaRecorrenciaAjusteEditar{{$ajuste->id}}" role="dialog">
                                                                                                                                        <div class="modal-dialog" role="document">
                                                                                                                                            <div class="modal-content">
                                                                                                                                                <form class="responseAjax" method="POST"action="{{route('Recorrencia.ajuste_data', ['id' => $ajuste->id])}}">
                                                                                                                                                    @csrf
                                                                                                                                                    <div class="modal-header">
                                                                                                                                                        <h5 class="modal-title align-self-center"
                                                                                                                                                            id="modalPautaRecorrenciaAjusteEditar{{$ajuste->id}}">Alterar data</h5>
                                                                                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                                                                    </div>
                                                                                                                                                    <div class="modal-body">
                                                                                                                                                        <div class="row">
                                                                                                                                                            <div class="col-md-12">
                                                                                                                                                                <div class="mb-3 no-margin">
                                                                                                                                                                    <input required name="data_recorrencia" value="{{$ajuste->data}}" class="form-control dataRecorrencia"  type="date" />
                                                                                                                                                                </div>
                                                                                                                                                            </div>
                                                                                                                                                        </div>
                                                                                                                                                    </div>
                                                                                                                                                    <div class="modal-footer">
                                                                                                                                                        <button type="button" class="btn btn-light"
                                                                                                                                                            data-bs-dismiss="modal">Fechar</button>
                                                                                                                                                        <button type="submit" class="btn btn-primary submitModal">Confirmar</button>
                                                                                                                                                    </div>
                                                                                                                                                </form>
                                                                                                                                            </div>
                                                                                                                                        </div>
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                                            @endif
                                                                                                                        </div>
                                                                                                                        @endif
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <div class="mt-3">
                                                                                                                    <p class="mb-0 font-size-13">
                                                                                                                       Prazo para entrega:
                                                                                                                        <span class="text-muted" style="text-transform: capitalize;">
                                                                                                                        {{ \Carbon\Carbon::parse($ajuste->data)->locale('pt_BR')->isoFormat('DD/MM/YYYY  MMMM') }}
                                                                                                                        </span>
                                                                                                                        @php
                                                                                                                            $dataAtualReq = \Carbon\Carbon::now()->startOfDay();
                                                                                                                            $mData = \Carbon\Carbon::parse($ajuste->data)->startOfDay();
                                                                                                                        @endphp
                                                                                                                        @if ($ajuste->entregue == 0 && $dataAtualReq->greaterThan($mData))
                                                                                                                            <span class="atrasado">Atrasada</span>
                                                                                                                        @endif
                                                                                                                    </p>
                                                                                                                </div>
                                                                                                                <div class="mt-3">
                                                                                                                    <ul class="list-unstyled list-inline mb-0">
                                                                                                                        <li class="list-inline-item">
                                                                                                                            @php
                                                                                                                                $backgroundColor = '#ff8538'; // valor padrão

                                                                                                                                if ($ajuste->status == 'pendente') {
                                                                                                                                    $backgroundColor = '#ff8538';
                                                                                                                                } elseif ($ajuste->status == 'Em pauta') {
                                                                                                                                    $backgroundColor = '#f9bc0b';
                                                                                                                                } elseif ($ajuste->status == 'Entregue') {
                                                                                                                                    $backgroundColor = '#44a2d2';
                                                                                                                                }
                                                                                                                            @endphp
                                                                                                                            <p class="text-muted font-size-13 mb-0">
                                                                                                                            Status :
                                                                                                                            </p>
                                                                                                                        </li>
                                                                                                                        <li class="list-inline-item">
                                                                                                                            <span class="badge" style="padding: 5px; background-color: {{ $backgroundColor }};"
                                                                                                                            >{{$ajuste->status}}</span
                                                                                                                            >
                                                                                                                        </li>
                                                                                                                    </ul>
                                                                                                                </div>
                                                                                                                @if($ajuste->descricao)
                                                                                                                <div class="mt-3">
                                                                                                                    <p>Briefing:</p>
                                                                                                                    <div class="text-muted font-size-13 text-muted-tiny commentsUsers">{{$ajuste->descricao}}</div>
                                                                                                                </div>
                                                                                                                @endif
                                                                                                            </div>
                                                                                                            @if($ajuste->entregue == 1)
                                                                                                                <div class="mt-3">
                                                                                                                    <p><span style="color: {{$ajuste->atrasada == 1 ? '#f73e1d' : '#3dbb3d'}};">Entregue<span></span>@if($ajuste->atrasada == 1)< span>com atraso</span>@endif:</span> {{ \Carbon\Carbon::parse($ajuste->data_entrega)->locale('pt_BR')->isoFormat('DD/MM/YYYY HH:mm')}}
                                                                                                                    </p>
                                                                                                                </div>
                                                                                                            @endif
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="card" style="margin-bottom: 0px">
                                                                                @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                <div class="modal fade" id="modalEditRecMensal-{{$m->id}}" tabindex="-1" role="dialog">
                                                                                    <div class="modal-dialog" role="document">
                                                                                        <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                            @component('components.RecorrenciaComponentEditar', ['tipo' => 'mensal', 'post' => 'Recorrencia.mensal_action', 'req' => $m, 'item' => $item, 'showAg' => $showAg ])@endcomponent
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="modal fade modalAjuste" id="modalCriarAjusteMensal-{{$m->id}}" tabindex="-1" role="dialog">
                                                                                    <div class="modal-dialog" role="document">
                                                                                        <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                            @component('components.RecorrenciaComponentCriarAjuste', ['tipo' => 'mensal', 'req' => $m])@endcomponent
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                @endif
                                                                                <div class="modal fade modalRecorrenciaComentario" id="modalComentarioMensal-{{$m->id}}" data-id="{{$m->id}}" role="dialog">
                                                                                    <div class="modal-dialog" role="document">
                                                                                        <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                            @component('components.RecorrenciaComponentComentario', ['req' => $m])@endcomponent
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endif
                                                    @if(count($demanda['recorrenciasAnuais']) > 0)
                                                    <div class="col-xl-12">
                                                        <div class="card cardRec" data-simplebar style="max-height: 700px;">
                                                            <div class="card-body">
                                                                <div class="adjustBriefing">
                                                                    <h5 class="card-title comments">Entregas anuais</h5>
                                                                    <a class="arounded" data-bs-toggle="collapse" href="#collapseAnual" role="button" aria-expanded="false" aria-controls="collapseAnual"></a>
                                                                </div>
                                                                <div class="collapse" id="collapseAnual">
                                                                    @foreach ($demanda['recorrenciasAnuais'] as $item )
                                                                    <div class="col-xl-12">
                                                                        <div class="card cardFirst">
                                                                            <div class="card-header">
                                                                                <div>
                                                                                    <p class="mb-3"><strong>Campanha:</strong> {!! $item->titulo !!}</p>
                                                                                    @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                        <div class="reqBtns">
                                                                                            <span class="editBt" data-bs-toggle="modal" data-bs-target="#modalCreateRecAnual-{{$item->id}}"><i style="cursor: pointer" class="fas fa-plus"></i></span>
                                                                                            <form class="responseAjax" action="{{ route('Recorrencia.delete', ['id' => $item->id]) }}" method="POST">
                                                                                                @csrf
                                                                                                @method('DELETE')
                                                                                                <div class="right gap-items-2">
                                                                                                    <button type="submit" class="submitForm deleteBtn"> <i class="fas fa-trash"></i></button>
                                                                                                </div>
                                                                                            </form>
                                                                                        </div>
                                                                                        <div class="card" style="margin-bottom: 0px">
                                                                                            <div class="modal fade" id="modalCreateRecAnual-{{$item->id}}" tabindex="-1" role="dialog">
                                                                                                <div class="modal-dialog" role="document">
                                                                                                    <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                                        <form class="responseAjax" method="POST" action="{{route('Recorrencia.anual_create_action', ['id' => $item->id])}}">
                                                                                                            @csrf
                                                                                                            <div class="modal-header">
                                                                                                                <h5 class="modal-title align-self-center"
                                                                                                                    id="modalReabirJob">Criar recorrência</h5>
                                                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                            </div>
                                                                                                            @component('components.RecorrenciaComponentCriar', ['tipo' => 'anual'])@endcomponent
                                                                                                        </form>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="">
                                                                                    <p class="data-attributes mb-0">
                                                                                        <div class="percent">
                                                                                            <p style="display:none;">{{$item->porcentagemEntregue}}%</p>
                                                                                        </div>            
                                                                                    </p>
                                                                                </div>
                                                                            </div>
                                                                            @foreach($item['recorrencias'] as $key => $a)
                                                                            @php
                                                                                $backgroundColor = '#ff8538'; // valor padrão

                                                                                if ($a->status == 'pendente') {
                                                                                    $backgroundColor = '#ff8538';
                                                                                } elseif ($a->status == 'Em pauta') {
                                                                                    $backgroundColor = '#f9bc0b';
                                                                                } elseif ($a->status == 'Entregue') {
                                                                                    $backgroundColor = '#44a2d2';
                                                                                }
                                                                                elseif ($a->status == 'Finalizado') {
                                                                                    $backgroundColor = '#3dbb3d';
                                                                                }
                                                                            @endphp
                                                                            <div class="card cardSingle">
                                                                                <div class="card-body"  style="border: 1px solid {{$backgroundColor}};">
                                                                                    <div class="cardSingleInitial" data-idAg="{{$a->id}}" data-isReadAg{{$a->id}}="{{$a->hasComentariosNaoLidos}}">
                                                                                        @if($a->hasComentariosNaoLidos)
                                                                                            <div class="mt-3">
                                                                                                <span><i class="fas fa-comment-dots msg"></i></span>
                                                                                            </div>
                                                                                        @endif
                                                                                        @if($a->entregue == 1)
                                                                                            @if($a->finalizado == 0)
                                                                                                @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                                <div class="mt-3">
                                                                                                    <form class="responseAjax responseAjaxReq d-flex"  method="POST" action="{{route('Recorrencia.Pauta.finalizar_tempo', ['id' => $a->id])}}">
                                                                                                        @csrf
                                                                                                        <div class="checkbox my-2">
                                                                                                            <div class="form-check adjustStatus" style="padding-left: 0px">
                                                                                                                <button type="submit"  class="form-control reopenJob fin blockBtn submitFinalize">Finalizar</button>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </form>
                                                                                                </div>
                                                                                                @endif
                                                                                            @endif
                                                                                        <div class="mt-3">
                                                                                            <p><span style="font-weight: 500; color: {{$a->atrasada == 1 ? '#f73e1d' : '#3dbb3d'}};">Entregue<span></span>@if($a->atrasada == 1)<span> com atraso</span>@endif:</span> {{ \Carbon\Carbon::parse($a->data_entrega)->locale('pt_BR')->isoFormat('DD/MM/YYYY HH:mm')}}
                                                                                            </p>
                                                                                        </div>
                                                                                        @endif
                                                                                        @if($a->titulo)
                                                                                        <div class="d-flex align-items-center mt-3">
                                                                                            <p class="mb-0">Título: {{$a->titulo}}</p>
                                                                                        </div>
                                                                                        @endif
                                                                                        <div class="mt-3">
                                                                                            <p class="mb-0 font-size-13">
                                                                                               Prazo para entrega:
                                                                                                <span class="text-muted" style="text-transform: capitalize;">
                                                                                                    {{ \Carbon\Carbon::parse($a->data)->locale('pt_BR')->isoFormat('DD/MM/YYYY  MMMM') }}
                                                                                                </span>
                                                                                                @php
                                                                                                    $dataAtualReq = \Carbon\Carbon::now()->startOfDay();
                                                                                                    $aData = \Carbon\Carbon::parse($a->data)->startOfDay();
                                                                                                @endphp
                                                                                                @if ($a->entregue == 0 && $dataAtualReq->greaterThan($aData))
                                                                                                    <span class="atrasado">Atrasada</span>
                                                                                                @endif
                                                                                            </p>
                                                                                        </div>
                                                                                        <div class="mt-3">
                                                                                            <ul class="list-unstyled list-inline mb-0">
                                                                                                <li class="list-inline-item">
                                                                                                    <p class="text-muted font-size-13 mb-0">
                                                                                                    Status :
                                                                                                    </p>
                                                                                                </li>
                                                                                                <li class="list-inline-item">
                                                                                                    <span class="badge" style="padding: 5px; background-color: {{ $backgroundColor }};"
                                                                                                    >{{$a->status}}</span
                                                                                                    >
                                                                                                </li>
                                                                                            </ul>
                                                                                        </div>
                                                                                        @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                        <div class="mt-3" style="margin-bottom: 1rem;">
                                                                                            <div class="btns btnAbosulte">
                                                                                                <span data-bs-toggle="modal" data-bs-target="#modalEditRecAnual-{{$a->id}}" class="editBt">
                                                                                                    <i style="cursor: pointer" class="fas fa-edit"></i>
                                                                                                </span>
                                                                                                <form class="responseAjax" action="{{ route('Recorrencia.single_delete', ['id' => $a->id]) }}" method="POST">
                                                                                                    @csrf
                                                                                                    @method('DELETE')
                                                                                                    <div class="right gap-items-2">
                                                                                                        <button type="submit" class="submitForm deleteBtn"> <i class="fas fa-trash"></i></button>
                                                                                                    </div>
                                                                                                </form>
                                                                                            </div>
                                                                                        </div>
                                                                                        @endif
                                                                                        @if($showAg)
                                                                                        <div class="btns btnAbosulte mt-3">
                                                                                            @if($a->em_pauta == 0 && $a->entregue == 0 && $a->em_alteracao == 0)
                                                                                                <span class="form-control alterarPrazoRec alt" data-bs-toggle="modal" data-bs-target="#modalPautaRecorrenciaEditar{{ $a->id }}">Alterar prazo</span>
                                                                                                <form class="responseAjax" method="POST" action="{{route('Recorrencia.Pauta.iniciar_tempo', ['id' => $a->id])}}">
                                                                                                    @csrf
                                                                                                    <input type="submit" class="form-control reopenJob fin blockBtn submitQuest" value="Iniciar">
                                                                                                </form>
                                                                                            @endif
                                                                                            @if($a->em_pauta == 1 && $a->entregue == 0)
                                                                                                @if($a->count_ajustes == 0)
                                                                                                    <span class="form-control alterarPrazoRec alt" data-bs-toggle="modal" data-bs-target="#modalPautaRecorrenciaEditar{{ $a->id }}">Alterar prazo</span>
                                                                                                        <form class="responseAjax" method="POST" action="{{route('Recorrencia.Pauta.entregar_tempo', ['id' => $a->id])}}">
                                                                                                            @csrf
                                                                                                            <input type="submit" class="form-control reopenJob fin blockBtn submitQuest" value="Concluir">
                                                                                                        </form>
                                                                                                    @else
                                                                                                    <p class="bgText" style="background-color: #f73e1d; cursor:auto;">Alterações pendetes ({{$a->count_ajustes}})</p>
                                                                                                @endif
                                                                                            @endif
                                                                                            <div class="card">
                                                                                                <div class="modal fade" id="modalPautaRecorrenciaEditar{{$a->id}}" role="dialog">
                                                                                                    <div class="modal-dialog" role="document">
                                                                                                        <div class="modal-content">
                                                                                                            <form class="responseAjax" method="POST"action="{{route('Recorrencia.data', ['id' => $a->id])}}">
                                                                                                                @csrf
                                                                                                                <div class="modal-header">
                                                                                                                    <h5 class="modal-title align-self-center"
                                                                                                                        id="modalPautaRecorrenciaEditar{{$a->id}}">Alterar data</h5>
                                                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                                </div>
                                                                                                                <div class="modal-body">
                                                                                                                    <div class="row">
                                                                                                                        <div class="col-md-12">
                                                                                                                            <div class="mb-3 no-margin">
                                                                                                                                <input required name="data_recorrencia" value="{{$a->data}}" class="form-control dataRecorrencia"  type="date" />
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <div class="modal-footer">
                                                                                                                    <button type="button" class="btn btn-light"
                                                                                                                        data-bs-dismiss="modal">Fechar</button>
                                                                                                                    <button type="submit" class="btn btn-primary submitModal">Confirmar</button>
                                                                                                                </div>
                                                                                                            </form>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        @endif
                                                                                    </div>
                                                                                    <div class="contentRecorrencia mt-3" style="display: none;">
                                                                                        @if($a->descricao)
                                                                                        <div class="blockSpace">
                                                                                            <div class="">
                                                                                                <p class="mb-0 font-size-13">
                                                                                                    Briefing:
                                                                                                </p>
                                                                                                <div class="text-muted font-size-13 text-muted-tiny commentsUsers">{{$a->descricao}}</div>
                                                                                            </div>
                                                                                        </div>
                                                                                        @endif
                                                                                        <div class="blockSpace">
                                                                                            <div class="initialTitle commentsAlteracoes">
                                                                                                <h5 style="margin-bottom: 0px;" class="card-title comments">Comentários</h5>
                                                                                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalComentarioAnual-{{$a->id}}" >Criar comentário</button>
                                                                                            </div>
                                                                                            @foreach($a['comentarios'] as $key => $comentario)
                                                                                            <div class="activity">
                                                                                                <img alt="" class="img-activity" src="{{url('/assets/images/users/')}}/{{$comentario['usuario']->avatar }}">
                                                                                                <div class="time-item" id="{{  preg_replace('/\s+/', '', 'comentario-recorrencia-'.$comentario->id)}}">
                                                                                                    <div class="item-info">
                                                                                                        <div class="text-muted float-end font-size-10 dateComentary">
                                                                                                            {{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $comentario->criado)->format('d/m/Y H:i'); }}
                                                                                                        </div>
                                                                                                        <div class="status statusComments">
                                                                                                            <h5 class="mb-1">{{ $comentario['usuario']->nome }}</h5>
                                                                                                            @if($comentario->usuario_id == $loggedUser->id && count($comentario->lidos) > 0)
                                                                                                            <div class="service_box">
                                                                                                                <div class="service_box_inner">
                                                                                                                    <div class="services_box_icon">
                                                                                                                        <div class="contentLido">
                                                                                                                            <i style="font-size: 12px;" class="fa fa-check"></i>
                                                                                                                            <i style="font-size: 12px; z-index: -1;" class="fa fa-check"></i>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                    <div class="service_content">
                                                                                                                        <ul class="services_box_list">
                                                                                                                            <li class="text-custom">
                                                                                                                                @foreach($comentario->lidos as $lido)
                                                                                                                                    <span class="services_box__list-text">{!! $lido->usuario->nome !!}<br/> </span>
                                                                                                                                @endforeach
                                                                                                                            </li>
                                                                                                                        </ul>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            @endif
                                                                                                        </div>
                                                                                                        <p class="text-muted font-size-13 text-muted-tiny commentsUsers" style="margin-top: 15px">
                                                                                                            {{ $comentario->descricao }}
                                                                                                        </p>
                                                                                                        @if($loggedUser->id == $comentario->usuario_id)
                                                                                                        <div class="btns">
                                                                                                            <span data-bs-toggle="modal" data-bs-target="#modalComentarioAnualEditar-{{$comentario->id}}"class="editBt">
                                                                                                                <i style="cursor: pointer" class="fas fa-edit"></i>
                                                                                                            </span>
                                                                                                            <form class="responseAjax" action="{{route('Recorrencia.comentario_delete', ['id' => $comentario->id])}}" method="post">
                                                                                                                @csrf
                                                                                                                @method('DELETE')
                                                                                                                <div class="right gap-items-2">
                                                                                                                    <button type="submit" class="submitForm deleteBtn"> <i class="fas fa-trash"></i></button>
                                                                                                                </div>
                                                                                                            </form>
                                                                                                            <div class="modal fade modalRecorrenciaComentarioEditar" id="modalComentarioAnualEditar-{{$comentario->id}}" role="dialog">
                                                                                                                <div class="modal-dialog" role="document">
                                                                                                                    <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                                                        @component('components.RecorrenciaComponentComentarioEditar', ['req' => $comentario])@endcomponent
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                        @endif
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                        <div class="blockSpace">
                                                                                            <div class="initialTitle commentsAlteracoes commentsAlteracoes" style="margin-bottom: 0px;">
                                                                                                <h5 style="margin-bottom: 0px;" class="card-title">Alterações</h5>
                                                                                                @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCriarAjusteAnual-{{$a->id}}" >Criar alteração</button>
                                                                                                @endif
                                                                                            </div>
                                                                                            @foreach($a['ajustes'] as $key => $ajuste)
                                                                                                <div class="recorrenciaSingle adjustRecorrenciaAj {{ $ajuste->entregue == 1 ? 'notifyContent' : '' }}">
                                                                                                    <div class="d-flex align-items-start" style="width: 100%">
                                                                                                        <div class="flex-1">
                                                                                                            <div class="ajusteContent">
                                                                                                                <div class="{{ $ajuste->entregue == 1 ? 'mt-0' : 'mt-3' }} initialAjuste">
                                                                                                                    <p class="mb-0 font-size-13">
                                                                                                                        <strong>Alteração {!! $key + 1 !!}</strong>
                                                                                                                    </p>
                                                                                                                    <div class="editarAjuste">
                                                                                                                        @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                                                        <div class="btns">
                                                                                                                            <span data-bs-toggle="modal" data-bs-target="#modalEditAjusteAnual-{{$ajuste->id}}" class="editBt">
                                                                                                                                <i style="cursor: pointer" class="fas fa-edit"></i>
                                                                                                                            </span>
                                                                                                                            <div class="right gap-items-2">
                                                                                                                                <form class="responseAjax" action="{{ route('Recorrencia.ajuste_delete', ['id' => $ajuste->id]) }}" method="POST">
                                                                                                                                    @csrf
                                                                                                                                    @method('DELETE')
                                                                                                                                    <button type="submit" class="submitForm deleteBtn"><i class="fas fa-trash"></i></button>
                                                                                                                                </form>
                                                                                                                            </div>
                                                                                                                            <div class="modal fade" id="modalEditAjusteAnual-{{$ajuste->id}}"  tabindex="-1" role="dialog">
                                                                                                                                <div class="modal-dialog" role="document">
                                                                                                                                    <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                                                                        @component('components.RecorrenciaComponentEditarAjuste', ['req' => $ajuste, 'tipo' => 'anual'])@endcomponent
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        @else
                                                                                                                        <div class="btns" style="justify-content: flex-start;">
                                                                                                                            @if ($showAg)
                                                                                                                                @if($ajuste->em_pauta == 0 && $ajuste->entregue == 0)
                                                                                                                                    <span class="form-control alterarPrazoRec alt" data-bs-toggle="modal" data-bs-target="#modalPautaRecorrenciaAjusteEditar{{ $ajuste->id }}">Alterar prazo</span>
                                                                                                                                    <form class="responseAjax" method="POST" action="{{route('Recorrencia.Ajuste.Pauta.entregar_tempo', ['id' => $ajuste->id])}}">
                                                                                                                                        @csrf
                                                                                                                                        <input type="hidden" name="keyAlteracao" value="{{$key}}">
                                                                                                                                        <input type="submit" class="form-control reopenJob fin blockBtn submitQuest" value="Iniciar">
                                                                                                                                    </form>
                                                                                                                                @endif
                                                                                                                                @if($ajuste->em_pauta == 1 && $ajuste->entregue == 0)
                                                                                                                                    <span class="form-control alterarPrazoRec alt" data-bs-toggle="modal" data-bs-target="#modalPautaRecorrenciaAjusteEditar{{ $ajuste->id }}">Alterar prazo</span>
                                                                                                                                    <form class="responseAjax" method="POST" action="{{route('Recorrencia.Ajuste.Pauta.finalizar_tempo', ['id' => $ajuste->id])}}">
                                                                                                                                        @csrf
                                                                                                                                        <input type="hidden" name="keyAlteracao" value="{{$key}}">
                                                                                                                                        <input type="submit" class="form-control reopenJob fin blockBtn submitQuest" value="Concluir">
                                                                                                                                    </form>
                                                                                                                                @endif
                                                                                                                                <div class="card">
                                                                                                                                    <div class="modal fade" id="modalPautaRecorrenciaAjusteEditar{{$ajuste->id}}" role="dialog">
                                                                                                                                        <div class="modal-dialog" role="document">
                                                                                                                                            <div class="modal-content">
                                                                                                                                                <form class="responseAjax" method="POST"action="{{route('Recorrencia.ajuste_data', ['id' => $ajuste->id])}}">
                                                                                                                                                    @csrf
                                                                                                                                                    <div class="modal-header">
                                                                                                                                                        <h5 class="modal-title align-self-center"
                                                                                                                                                            id="modalPautaRecorrenciaAjusteEditar{{$ajuste->id}}">Alterar data</h5>
                                                                                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                                                                    </div>
                                                                                                                                                    <div class="modal-body">
                                                                                                                                                        <div class="row">
                                                                                                                                                            <div class="col-md-12">
                                                                                                                                                                <div class="mb-3 no-margin">
                                                                                                                                                                    <input required name="data_recorrencia" value="{{$ajuste->data}}" class="form-control dataRecorrencia"  type="date" />
                                                                                                                                                                </div>
                                                                                                                                                            </div>
                                                                                                                                                        </div>
                                                                                                                                                    </div>
                                                                                                                                                    <div class="modal-footer">
                                                                                                                                                        <button type="button" class="btn btn-light"
                                                                                                                                                            data-bs-dismiss="modal">Fechar</button>
                                                                                                                                                        <button type="submit" class="btn btn-primary submitModal">Confirmar</button>
                                                                                                                                                    </div>
                                                                                                                                                </form>
                                                                                                                                            </div>
                                                                                                                                        </div>
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                                            @endif
                                                                                                                        </div>
                                                                                                                        @endif
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <div class="mt-3">
                                                                                                                    <p class="mb-0 font-size-13">
                                                                                                                       Prazo para entrega:
                                                                                                                        <span class="text-muted" style="text-transform: capitalize;">
                                                                                                                        {{ \Carbon\Carbon::parse($ajuste->data)->locale('pt_BR')->isoFormat('DD/MM/YYYY  MMMM') }}
                                                                                                                        </span>
                                                                                                                        @php
                                                                                                                            $dataAtualReq = \Carbon\Carbon::now()->startOfDay();
                                                                                                                            $aData = \Carbon\Carbon::parse($ajuste->data)->startOfDay();
                                                                                                                        @endphp
                                                                                                                        @if ($ajuste->entregue == 0 && $dataAtualReq->greaterThan($aData))
                                                                                                                            <span class="atrasado">Atrasada</span>
                                                                                                                        @endif
                                                                                                                    </p>
                                                                                                                </div>
                                                                                                                <div class="mt-3">
                                                                                                                    <ul class="list-unstyled list-inline mb-0">
                                                                                                                        <li class="list-inline-item">
                                                                                                                            @php
                                                                                                                                $backgroundColor = '#ff8538'; // valor padrão

                                                                                                                                if ($ajuste->status == 'pendente') {
                                                                                                                                    $backgroundColor = '#ff8538';
                                                                                                                                } elseif ($ajuste->status == 'Em pauta') {
                                                                                                                                    $backgroundColor = '#f9bc0b';
                                                                                                                                } elseif ($ajuste->status == 'Entregue') {
                                                                                                                                    $backgroundColor = '#44a2d2';
                                                                                                                                }
                                                                                                                            @endphp
                                                                                                                            <p class="text-muted font-size-13 mb-0">
                                                                                                                            Status :
                                                                                                                            </p>
                                                                                                                        </li>
                                                                                                                        <li class="list-inline-item">
                                                                                                                            <span class="badge" style="padding: 5px; background-color: {{ $backgroundColor }};"
                                                                                                                            >{{$ajuste->status}}</span
                                                                                                                            >
                                                                                                                        </li>
                                                                                                                    </ul>
                                                                                                                </div>
                                                                                                                @if($ajuste->descricao)
                                                                                                                <div class="mt-3">
                                                                                                                    <p>Briefing:</p>
                                                                                                                    <div class="text-muted font-size-13 text-muted-tiny commentsUsers">{{$ajuste->descricao}}</div>
                                                                                                                </div>
                                                                                                                @endif
                                                                                                            </div>
                                                                                                            @if($ajuste->entregue == 1)
                                                                                                                <div class="mt-3">
                                                                                                                    <p><span style="color: {{$ajuste->atrasada == 1 ? '#f73e1d' : '#3dbb3d'}};">Entregue<span></span>@if($ajuste->atrasada == 1)< span>com atraso</span>@endif:</span> {{ \Carbon\Carbon::parse($ajuste->data_entrega)->locale('pt_BR')->isoFormat('DD/MM/YYYY HH:mm')}}
                                                                                                                    </p>
                                                                                                                </div>
                                                                                                            @endif
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="card" style="margin-bottom: 0px">
                                                                                @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                <div class="modal fade" id="modalEditRecAnual-{{$a->id}}" tabindex="-1" role="dialog">
                                                                                    <div class="modal-dialog" role="document">
                                                                                        <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                            @component('components.RecorrenciaComponentEditar', ['tipo' => 'anual', 'post' => 'Recorrencia.anual_action', 'req' => $a, 'item' => $item, 'showAg' => $showAg ])@endcomponent
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="modal fade modalAjuste" id="modalCriarAjusteAnual-{{$a->id}}" tabindex="-1" role="dialog">
                                                                                    <div class="modal-dialog" role="document">
                                                                                        <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                            @component('components.RecorrenciaComponentCriarAjuste', ['tipo' => 'anual', 'req' => $a])@endcomponent
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                @endif
                                                                                <div class="modal fade modalRecorrenciaComentario" id="modalComentarioAnual-{{$a->id}}" data-id="{{$a->id}}" role="dialog">
                                                                                    <div class="modal-dialog" role="document">
                                                                                        <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                            @component('components.RecorrenciaComponentComentario', ['req' => $a])@endcomponent
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endif
                                                    @if(count($demanda['recorrenciasSemanais']) > 0)
                                                    <div class="col-xl-12">
                                                        <div class="card cardRec" data-simplebar style="max-height: 700px;">
                                                            <div class="card-body">
                                                                <div class="adjustBriefing">
                                                                    <h5 class="card-title comments">Entregas semanais</h5>
                                                                    <a class="arounded" data-bs-toggle="collapse" href="#collapseSemanal" role="button" aria-expanded="false" aria-controls="collapseSemanal"></a>
                                                                </div>
                                                                <div class="collapse" id="collapseSemanal">
                                                                    @foreach ($demanda['recorrenciasSemanais'] as $item )
                                                                    <div class="col-xl-12">
                                                                        <div class="card cardFirst">
                                                                            <div class="card-header">
                                                                                <div>
                                                                                    <p class="mb-3"><strong>Campanha:</strong> {!! $item->titulo !!}</p>
                                                                                    @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                        <div class="reqBtns">
                                                                                            <span class="editBt" data-bs-toggle="modal" data-bs-target="#modalCreateRecSemanal-{{$item->id}}"><i style="cursor: pointer" class="fas fa-plus"></i></span>
                                                                                            <form class="responseAjax" action="{{ route('Recorrencia.delete', ['id' => $item->id]) }}" method="POST">
                                                                                                @csrf
                                                                                                @method('DELETE')
                                                                                                <div class="right gap-items-2">
                                                                                                    <button type="submit" class="submitForm deleteBtn"> <i class="fas fa-trash"></i></button>
                                                                                                </div>
                                                                                            </form>
                                                                                        </div>
                                                                                        <div class="card" style="margin-bottom: 0px">
                                                                                            <div class="modal fade" id="modalCreateRecSemanal-{{$item->id}}" tabindex="-1" role="dialog">
                                                                                                <div class="modal-dialog" role="document">
                                                                                                    <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                                        <form class="responseAjax" method="POST" action="{{route('Recorrencia.semanal_create_action', ['id' => $item->id])}}">
                                                                                                            @csrf
                                                                                                            <div class="modal-header">
                                                                                                                <h5 class="modal-title align-self-center"
                                                                                                                    id="modalReabirJob">Criar recorrência</h5>
                                                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                            </div>
                                                                                                            @component('components.RecorrenciaComponentCriar', ['tipo' => 'semanal'])@endcomponent
                                                                                                        </form>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    @endif
                                                                                </div>
                                                                                <div class="">
                                                                                    <p class="data-attributes mb-0">
                                                                                        <div class="percent">
                                                                                            <p style="display:none;">{{$item->porcentagemEntregue}}%</p>
                                                                                        </div>            
                                                                                    </p>
                                                                                </div>
                                                                            </div>
                                                                            @foreach($item['recorrencias'] as $key => $s)
                                                                            @php
                                                                                $backgroundColor = '#ff8538'; // valor padrão

                                                                                if ($s->status == 'pendente') {
                                                                                    $backgroundColor = '#ff8538';
                                                                                } elseif ($s->status == 'Em pauta') {
                                                                                    $backgroundColor = '#f9bc0b';
                                                                                } elseif ($s->status == 'Entregue') {
                                                                                    $backgroundColor = '#44a2d2';
                                                                                }
                                                                                elseif ($s->status == 'Finalizado') {
                                                                                    $backgroundColor = '#3dbb3d';
                                                                                }
                                                                            @endphp
                                                                            <div class="card cardSingle">
                                                                                <div class="card-body"  style="border: 1px solid {{$backgroundColor}};">
                                                                                   <div class="cardSingleInitial" data-idAg="{{$s->id}}" data-isReadAg{{$s->id}}="{{$s->hasComentariosNaoLidos}}">
                                                                                        @if($s->hasComentariosNaoLidos)
                                                                                            <div class="mt-3">
                                                                                                <span><i class="fas fa-comment-dots msg"></i></span>
                                                                                            </div>
                                                                                        @endif
                                                                                        @if($s->entregue == 1)
                                                                                            @if($s->finalizado == 0)
                                                                                                @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                                <div class="mt-3">
                                                                                                    <form class="responseAjax responseAjaxReq d-flex"  method="POST" action="{{route('Recorrencia.Pauta.finalizar_tempo', ['id' => $s->id])}}">
                                                                                                        @csrf
                                                                                                        <div class="checkbox my-2">
                                                                                                            <div class="form-check adjustStatus" style="padding-left: 0px">
                                                                                                                <button type="submit"  class="form-control reopenJob fin blockBtn submitFinalize">Finalizar</button>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </form>
                                                                                                </div>
                                                                                                @endif
                                                                                            @endif
                                                                                        <div class="mt-3">
                                                                                            <p><span style="font-weight: 500; color: {{$s->atrasada == 1 ? '#f73e1d' : '#3dbb3d'}};">Entregue<span></span>@if($s->atrasada == 1)<span> com atraso</span>@endif:</span> {{ \Carbon\Carbon::parse($s->data_entrega)->locale('pt_BR')->isoFormat('DD/MM/YYYY HH:mm')}}
                                                                                            </p>
                                                                                        </div>
                                                                                        @endif
                                                                                        @if($s->titulo)
                                                                                        <div class="d-flex align-items-center mt-3">
                                                                                            <p class="mb-0">Título: {{$s->titulo}}</p>
                                                                                        </div>
                                                                                        @endif
                                                                                        <div class="mt-3">
                                                                                            <p class="mb-0 font-size-13">
                                                                                               Prazo para entrega:
                                                                                                <span class="text-muted" style="text-transform: capitalize;">
                                                                                                    {{ \Carbon\Carbon::parse($s->data)->locale('pt_BR')->isoFormat('DD/MM/YYYY  MMMM') }}
                                                                                                </span>
                                                                                                @php
                                                                                                    $dataAtualReq = \Carbon\Carbon::now()->startOfDay();
                                                                                                    $sData = \Carbon\Carbon::parse($s->data)->startOfDay();
                                                                                                @endphp
                                                                                                @if ($s->entregue == 0 && $dataAtualReq->greaterThan($sData))
                                                                                                    <span class="atrasado">Atrasada</span>
                                                                                                @endif
                                                                                            </p>
                                                                                        </div>
                                                                                        <div class="mt-3">
                                                                                            <ul class="list-unstyled list-inline mb-0">
                                                                                                <li class="list-inline-item">
                                                                                                    <p class="text-muted font-size-13 mb-0">
                                                                                                    Status :
                                                                                                    </p>
                                                                                                </li>
                                                                                                <li class="list-inline-item">
                                                                                                    <span class="badge" style="padding: 5px; background-color: {{ $backgroundColor }};"
                                                                                                    >{{$s->status}}</span
                                                                                                    >
                                                                                                </li>
                                                                                            </ul>
                                                                                        </div>
                                                                                        @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                        <div class="mt-3" style="margin-bottom: 1rem;">
                                                                                            <div class="btns btnAbosulte">
                                                                                                <span data-bs-toggle="modal" data-bs-target="#modalEditRecSemanal-{{$s->id}}" class="editBt">
                                                                                                    <i style="cursor: pointer" class="fas fa-edit"></i>
                                                                                                </span>
                                                                                                <form class="responseAjax" action="{{ route('Recorrencia.single_delete', ['id' => $s->id]) }}" method="POST">
                                                                                                    @csrf
                                                                                                    @method('DELETE')
                                                                                                    <div class="right gap-items-2">
                                                                                                        <button type="submit" class="submitForm deleteBtn"> <i class="fas fa-trash"></i></button>
                                                                                                    </div>
                                                                                                </form>
                                                                                            </div>
                                                                                        </div>
                                                                                        @endif
                                                                                        @if($showAg)
                                                                                        <div class="btns btnAbosulte mt-3">
                                                                                            @if($s->em_pauta == 0 && $s->entregue == 0 && $s->em_alteracao == 0)
                                                                                                <span class="form-control alterarPrazoRec alt" data-bs-toggle="modal" data-bs-target="#modalPautaRecorrenciaEditar{{ $s->id }}">Alterar prazo</span>
                                                                                                <form class="responseAjax" method="POST" action="{{route('Recorrencia.Pauta.iniciar_tempo', ['id' => $s->id])}}">
                                                                                                    @csrf
                                                                                                    <input type="submit" class="form-control reopenJob fin blockBtn submitQuest" value="Iniciar">
                                                                                                </form>
                                                                                            @endif
                                                                                            @if($s->em_pauta == 1 && $s->entregue == 0)
                                                                                                @if($s->count_ajustes == 0)
                                                                                                    <span class="form-control alterarPrazoRec alt" data-bs-toggle="modal" data-bs-target="#modalPautaRecorrenciaEditar{{ $s->id }}">Alterar prazo</span>
                                                                                                        <form class="responseAjax" method="POST" action="{{route('Recorrencia.Pauta.entregar_tempo', ['id' => $s->id])}}">
                                                                                                            @csrf
                                                                                                            <input type="submit" class="form-control reopenJob fin blockBtn submitQuest" value="Concluir">
                                                                                                        </form>
                                                                                                    @else
                                                                                                    <p class="bgText" style="background-color: #f73e1d; cursor:auto;">Alterações pendetes ({{$s->count_ajustes}})</p>
                                                                                                @endif
                                                                                            @endif
                                                                                            <div class="card">
                                                                                                <div class="modal fade" id="modalPautaRecorrenciaEditar{{$s->id}}" role="dialog">
                                                                                                    <div class="modal-dialog" role="document">
                                                                                                        <div class="modal-content">
                                                                                                            <form class="responseAjax" method="POST"action="{{route('Recorrencia.data', ['id' => $s->id])}}">
                                                                                                                @csrf
                                                                                                                <div class="modal-header">
                                                                                                                    <h5 class="modal-title align-self-center"
                                                                                                                        id="modalPautaRecorrenciaEditar{{$s->id}}">Alterar data</h5>
                                                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                                </div>
                                                                                                                <div class="modal-body">
                                                                                                                    <div class="row">
                                                                                                                        <div class="col-md-12">
                                                                                                                            <div class="mb-3 no-margin">
                                                                                                                                <input required name="data_recorrencia" value="{{$s->data}}" class="form-control dataRecorrencia"  type="date" />
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <div class="modal-footer">
                                                                                                                    <button type="button" class="btn btn-light"
                                                                                                                        data-bs-dismiss="modal">Fechar</button>
                                                                                                                    <button type="submit" class="btn btn-primary submitModal">Confirmar</button>
                                                                                                                </div>
                                                                                                            </form>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        @endif
                                                                                   </div>
                                                                                    <div class="contentRecorrencia mt-3" style="display: none;">
                                                                                        @if($s->descricao)
                                                                                        <div class="blockSpace">
                                                                                            <div class="">
                                                                                                <p class="mb-0 font-size-13">
                                                                                                    Briefing:
                                                                                                </p>
                                                                                                <div class="text-muted font-size-13 text-muted-tiny commentsUsers">{{$s->descricao}}</div>
                                                                                            </div>
                                                                                        </div>
                                                                                        @endif
                                                                                        <div class="blockSpace">
                                                                                            <div class="initialTitle commentsAlteracoes">
                                                                                                <h5 style="margin-bottom: 0px;" class="card-title comments">Comentários</h5>
                                                                                                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalComentarioSemanal-{{$s->id}}" >Criar comentário</button>
                                                                                            </div>
                                                                                            @foreach($s['comentarios'] as $key => $comentario)
                                                                                            <div class="activity">
                                                                                                <img alt="" class="img-activity" src="{{url('/assets/images/users/')}}/{{$comentario['usuario']->avatar }}">
                                                                                                <div class="time-item" id="{{  preg_replace('/\s+/', '', 'comentario-recorrencia-'.$comentario->id)}}">
                                                                                                    <div class="item-info">
                                                                                                        <div class="text-muted float-end font-size-10 dateComentary">
                                                                                                            {{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $comentario->criado)->format('d/m/Y H:i'); }}
                                                                                                        </div>
                                                                                                        <div class="status statusComments">
                                                                                                            <h5 class="mb-1">{{ $comentario['usuario']->nome }}</h5>
                                                                                                            @if($comentario->usuario_id == $loggedUser->id && count($comentario->lidos) > 0)
                                                                                                            <div class="service_box">
                                                                                                                <div class="service_box_inner">
                                                                                                                    <div class="services_box_icon">
                                                                                                                        <div class="contentLido">
                                                                                                                            <i style="font-size: 12px;" class="fa fa-check"></i>
                                                                                                                            <i style="font-size: 12px; z-index: -1;" class="fa fa-check"></i>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                    <div class="service_content">
                                                                                                                        <ul class="services_box_list">
                                                                                                                            <li class="text-custom">
                                                                                                                                @foreach($comentario->lidos as $lido)
                                                                                                                                    <span class="services_box__list-text">{!! $lido->usuario->nome !!}<br/> </span>
                                                                                                                                @endforeach
                                                                                                                            </li>
                                                                                                                        </ul>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            @endif
                                                                                                        </div>
                                                                                                        <p class="text-muted font-size-13 text-muted-tiny commentsUsers" style="margin-top: 15px">
                                                                                                            {{ $comentario->descricao }}
                                                                                                        </p>
                                                                                                        @if($loggedUser->id == $comentario->usuario_id)
                                                                                                        <div class="btns">
                                                                                                            <span data-bs-toggle="modal" data-bs-target="#modalComentarioSemanalEditar-{{$comentario->id}}"class="editBt">
                                                                                                                <i style="cursor: pointer" class="fas fa-edit"></i>
                                                                                                            </span>
                                                                                                            <form class="responseAjax" action="{{route('Recorrencia.comentario_delete', ['id' => $comentario->id])}}" method="post">
                                                                                                                @csrf
                                                                                                                @method('DELETE')
                                                                                                                <div class="right gap-items-2">
                                                                                                                    <button type="submit" class="submitForm deleteBtn"> <i class="fas fa-trash"></i></button>
                                                                                                                </div>
                                                                                                            </form>
                                                                                                            <div class="modal fade modalRecorrenciaComentarioEditar" id="modalComentarioSemanalEditar-{{$comentario->id}}" role="dialog">
                                                                                                                <div class="modal-dialog" role="document">
                                                                                                                    <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                                                        @component('components.RecorrenciaComponentComentarioEditar', ['req' => $comentario])@endcomponent
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                        @endif
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                        <div class="blockSpace">
                                                                                            <div class="initialTitle commentsAlteracoes commentsAlteracoes" style="margin-bottom: 0px;">
                                                                                                <h5 style="margin-bottom: 0px;" class="card-title">Alterações</h5>
                                                                                                @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCriarAjusteSemanal-{{$s->id}}" >Criar alteração</button>
                                                                                                @endif
                                                                                            </div>
                                                                                            @foreach($s['ajustes'] as $key => $ajuste)
                                                                                                <div class="recorrenciaSingle adjustRecorrenciaAj {{ $ajuste->entregue == 1 ? 'notifyContent' : '' }}">
                                                                                                    <div class="d-flex align-items-start" style="width: 100%">
                                                                                                        <div class="flex-1">
                                                                                                            <div class="ajusteContent">
                                                                                                                <div class="{{ $ajuste->entregue == 1 ? 'mt-0' : 'mt-3' }} initialAjuste">
                                                                                                                    <p class="mb-0 font-size-13">
                                                                                                                      <strong>Alteração {!! $key + 1 !!}</strong>
                                                                                                                    </p>
                                                                                                                    <div class="editarAjuste">
                                                                                                                        @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                                                        <div class="btns">
                                                                                                                            <span data-bs-toggle="modal" data-bs-target="#modalEditAjusteSemanal-{{$ajuste->id}}" class="editBt">
                                                                                                                                <i style="cursor: pointer" class="fas fa-edit"></i>
                                                                                                                            </span>
                                                                                                                            <div class="right gap-items-2">
                                                                                                                                <form class="responseAjax" action="{{ route('Recorrencia.ajuste_delete', ['id' => $ajuste->id]) }}" method="POST">
                                                                                                                                    @csrf
                                                                                                                                    @method('DELETE')
                                                                                                                                    <button type="submit" class="submitForm deleteBtn"><i class="fas fa-trash"></i></button>
                                                                                                                                </form>
                                                                                                                            </div>
                                                                                                                            <div class="modal fade" id="modalEditAjusteSemanal-{{$ajuste->id}}"  tabindex="-1" role="dialog">
                                                                                                                                <div class="modal-dialog" role="document">
                                                                                                                                    <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                                                                        @component('components.RecorrenciaComponentEditarAjuste', ['req' => $ajuste, 'tipo' => 'semanal'])@endcomponent
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                        @else
                                                                                                                        <div class="btns" style="justify-content: flex-start;">
                                                                                                                            @if ($showAg)
                                                                                                                                @if($ajuste->em_pauta == 0 && $ajuste->entregue == 0)
                                                                                                                                    <span class="form-control alterarPrazoRec alt" data-bs-toggle="modal" data-bs-target="#modalPautaRecorrenciaAjusteEditar{{ $ajuste->id }}">Alterar prazo</span>
                                                                                                                                    <form class="responseAjax" method="POST" action="{{route('Recorrencia.Ajuste.Pauta.entregar_tempo', ['id' => $ajuste->id])}}">
                                                                                                                                        @csrf
                                                                                                                                        <input type="hidden" name="keyAlteracao" value="{{$key}}">
                                                                                                                                        <input type="submit" class="form-control reopenJob fin blockBtn submitQuest" value="Iniciar">
                                                                                                                                    </form>
                                                                                                                                @endif
                                                                                                                                @if($ajuste->em_pauta == 1 && $ajuste->entregue == 0)
                                                                                                                                    <span class="form-control alterarPrazoRec alt" data-bs-toggle="modal" data-bs-target="#modalPautaRecorrenciaAjusteEditar{{ $ajuste->id }}">Alterar prazo</span>
                                                                                                                                    <form class="responseAjax" method="POST" action="{{route('Recorrencia.Ajuste.Pauta.finalizar_tempo', ['id' => $ajuste->id])}}">
                                                                                                                                        @csrf
                                                                                                                                        <input type="hidden" name="keyAlteracao" value="{{$key}}">
                                                                                                                                        <input type="submit" class="form-control reopenJob fin blockBtn submitQuest" value="Concluir">
                                                                                                                                    </form>
                                                                                                                                @endif
                                                                                                                                <div class="card">
                                                                                                                                    <div class="modal fade" id="modalPautaRecorrenciaAjusteEditar{{$ajuste->id}}" role="dialog">
                                                                                                                                        <div class="modal-dialog" role="document">
                                                                                                                                            <div class="modal-content">
                                                                                                                                                <form class="responseAjax" method="POST"action="{{route('Recorrencia.ajuste_data', ['id' => $ajuste->id])}}">
                                                                                                                                                    @csrf
                                                                                                                                                    <div class="modal-header">
                                                                                                                                                        <h5 class="modal-title align-self-center"
                                                                                                                                                            id="modalPautaRecorrenciaAjusteEditar{{$ajuste->id}}">Alterar data</h5>
                                                                                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                                                                    </div>
                                                                                                                                                    <div class="modal-body">
                                                                                                                                                        <div class="row">
                                                                                                                                                            <div class="col-md-12">
                                                                                                                                                                <div class="mb-3 no-margin">
                                                                                                                                                                    <input required name="data_recorrencia" value="{{$ajuste->data}}" class="form-control dataRecorrencia"  type="date" />
                                                                                                                                                                </div>
                                                                                                                                                            </div>
                                                                                                                                                        </div>
                                                                                                                                                    </div>
                                                                                                                                                    <div class="modal-footer">
                                                                                                                                                        <button type="button" class="btn btn-light"
                                                                                                                                                            data-bs-dismiss="modal">Fechar</button>
                                                                                                                                                        <button type="submit" class="btn btn-primary submitModal">Confirmar</button>
                                                                                                                                                    </div>
                                                                                                                                                </form>
                                                                                                                                            </div>
                                                                                                                                        </div>
                                                                                                                                    </div>
                                                                                                                                </div>
                                                                                                                            @endif
                                                                                                                        </div>
                                                                                                                        @endif
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <div class="mt-3">
                                                                                                                    <p class="mb-0 font-size-13">
                                                                                                                       Prazo para entrega:
                                                                                                                        <span class="text-muted" style="text-transform: capitalize;">
                                                                                                                        {{ \Carbon\Carbon::parse($ajuste->data)->locale('pt_BR')->isoFormat('DD/MM/YYYY  MMMM') }}
                                                                                                                        </span>
                                                                                                                        @php
                                                                                                                            $dataAtualReq = \Carbon\Carbon::now()->startOfDay();
                                                                                                                            $sData = \Carbon\Carbon::parse($ajuste->data)->startOfDay();
                                                                                                                        @endphp
                                                                                                                        @if ($ajuste->entregue == 0 && $dataAtualReq->greaterThan($sData))
                                                                                                                            <span class="atrasado">Atrasada</span>
                                                                                                                        @endif
                                                                                                                    </p>
                                                                                                                </div>
                                                                                                                <div class="mt-3">
                                                                                                                    <ul class="list-unstyled list-inline mb-0">
                                                                                                                        <li class="list-inline-item">
                                                                                                                            @php
                                                                                                                                $backgroundColor = '#ff8538'; // valor padrão

                                                                                                                                if ($ajuste->status == 'pendente') {
                                                                                                                                    $backgroundColor = '#ff8538';
                                                                                                                                } elseif ($ajuste->status == 'Em pauta') {
                                                                                                                                    $backgroundColor = '#f9bc0b';
                                                                                                                                } elseif ($ajuste->status == 'Entregue') {
                                                                                                                                    $backgroundColor = '#44a2d2';
                                                                                                                                }
                                                                                                                            @endphp
                                                                                                                            <p class="text-muted font-size-13 mb-0">
                                                                                                                            Status :
                                                                                                                            </p>
                                                                                                                        </li>
                                                                                                                        <li class="list-inline-item">
                                                                                                                            <span class="badge" style="padding: 5px; background-color: {{ $backgroundColor }};"
                                                                                                                            >{{$ajuste->status}}</span
                                                                                                                            >
                                                                                                                        </li>
                                                                                                                    </ul>
                                                                                                                </div>
                                                                                                                @if($ajuste->descricao)
                                                                                                                <div class="mt-3">
                                                                                                                    <p>Briefing:</p>
                                                                                                                    <div class="text-muted font-size-13 text-muted-tiny commentsUsers">{{$ajuste->descricao}}</div>
                                                                                                                </div>
                                                                                                                @endif
                                                                                                            </div>
                                                                                                            @if($ajuste->entregue == 1)
                                                                                                                <div class="mt-3">
                                                                                                                    <p><span style="color: {{$ajuste->atrasada == 1 ? '#f73e1d' : '#3dbb3d'}};">Entregue<span></span>@if($ajuste->atrasada == 1)< span>com atraso</span>@endif:</span> {{ \Carbon\Carbon::parse($ajuste->data_entrega)->locale('pt_BR')->isoFormat('DD/MM/YYYY HH:mm')}}
                                                                                                                    </p>
                                                                                                                </div>
                                                                                                            @endif
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            @endforeach
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            <div class="card" style="margin-bottom: 0px">
                                                                                @if ($showColaborador || $loggedUser->id == $demanda->criador_id)
                                                                                <div class="modal fade" id="modalEditRecSemanal-{{$s->id}}" tabindex="-1" role="dialog">
                                                                                    <div class="modal-dialog" role="document">
                                                                                        <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                            @component('components.RecorrenciaComponentEditar', ['tipo' => 'semanal', 'post' => 'Recorrencia.semanal_action', 'req' => $s, 'item' => $item, 'showAg' => $showAg ])@endcomponent
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div class="modal fade modalAjuste" id="modalCriarAjusteSemanal-{{$s->id}}" tabindex="-1" role="dialog">
                                                                                    <div class="modal-dialog" role="document">
                                                                                        <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                            @component('components.RecorrenciaComponentCriarAjuste', ['tipo' => 'semanal', 'req' => $s])@endcomponent
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                @endif
                                                                                <div class="modal fade modalRecorrenciaComentario" id="modalComentarioSemanal-{{$s->id}}" data-id="{{$s->id}}" role="dialog">
                                                                                    <div class="modal-dialog" role="document">
                                                                                        <div class="modal-content adjustContentModal" data-simplebar="init" style="max-height: 600px;">
                                                                                            @component('components.RecorrenciaComponentComentario', ['req' => $s])@endcomponent
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                            @endforeach
                                                                        </div>
                                                                    </div>
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    @endif
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <div style="display: flex">
                                                                @if($demanda->em_pauta == 1 && $demanda->pausado == 0)
                                                                    <div class="showStatus" style="background-color: #f9bc0b">
                                                                        <p>STATUS: EM PAUTA</p>
                                                                    </div>
                                                                    @elseif ($demanda->em_pauta == 0 && $demanda->finalizada == 0 && $demanda->entregue == '0' && $demanda->pausado == 0)
                                                                    <div class="showStatus" style="background-color: #ff8538">
                                                                        <p>STATUS: PENDENTE</p>
                                                                    </div>

                                                                    @elseif($demanda->entregue == 1  && $demanda->pausado == 0)
                                                                    <div class="showStatus" style="background-color: #3dbb3d">
                                                                        <p>STATUS: ENTREGUE</p>
                                                                    </div>

                                                                    @elseif($demanda->pausado == 1)
                                                                    <div class="showStatus" style="background-color: #a0e5f3">
                                                                        <p>STATUS: CONGELADO</p>
                                                                    </div>

                                                                    @elseif($demanda->finalizada == 1)
                                                                    <div class="showStatus" style="background-color: #cfcfcf">
                                                                        <p>STATUS: FINALIZADO</p>
                                                                    </div>
                                                                @endif
                                                                
                                                                @if ($demanda->finalizada == 1 && ($loggedUser->id == $demanda->criador_id || $showColaborador))
                                                                    <span data-bs-toggle="modal" style="background-color: #34495E; cursor: pointer;" data-bs-target="#modalReabirJob" class="" id="reopenJob">Reabrir job</span>
                                                                    <div class="card">
                                                                        <div class="modal fade" id="modalReabirJob" tabindex="-1" role="dialog">
                                                                            <div class="modal-dialog" role="document">
                                                                                <div class="modal-content">
                                                                                    <form class="responseAjax" method="POST"action="{{route('reaberto', ['id' => $demanda->id])}}">
                                                                                        @csrf
                                                                                        <div class="modal-header">
                                                                                            <h5 class="modal-title align-self-center"
                                                                                                id="modalReabirJob">Sugira a nova para data para a entrega do job reaberto</h5>
                                                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                        </div>
                                                                                        <div class="modal-body">
                                                                                            <div class="row">
                                                                                                <div class="col-md-12">
                                                                                                    <div class="mb-3 no-margin">
                                                                                                        <input required name="sugerido_reaberto" value="{{ old('sugerido_reaberto') }}" class="form-control sugerido"  type="datetime-local" />
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        <div class="modal-footer">
                                                                                            <button type="button" class="btn btn-light"
                                                                                                data-bs-dismiss="modal">Fechar</button>
                                                                                            <button type="submit" class="btn btn-primary submitModal">Confirmar</button>
                                                                                        </div>
                                                                                    </form>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                            <div class="initalResume">
                                                                <div class="nameJob">
                                                                    <h5>{{ $demanda->titulo }}</h5>
                                                                    @if ($showAg)
                                                                        <span data-bs-toggle="modal" data-bs-target="#modalMudarNomeJob"><i style="cursor: pointer" class="fas fa-edit"></i></span>
                                                                        <div class="card" style="position: relative">
                                                                            <div class="modal fade" id="modalMudarNomeJob" tabindex="-1" role="dialog">
                                                                                <div class="modal-dialog" role="document">
                                                                                    <div class="modal-content">
                                                                                        <form class="responseAjax" method="POST" action="{{route('Demanda_titulo', ['id' => $demanda->id])}}">
                                                                                            @csrf
                                                                                            <div class="modal-header">
                                                                                                <h5 class="modal-title align-self-center"
                                                                                                    id="modalMudarNomeJob">Editar título do job</h5>
                                                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                            </div>
                                                                                            <div class="modal-body">
                                                                                                <div class="row">
                                                                                                    <div class="col-md-12">
                                                                                                        <div class="mb-3 no-margin">
                                                                                                            <input class="form-control" value="{{$demanda->titulo}}" required type="text" name="titulo" />
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                            <div class="modal-footer">
                                                                                                <button type="button" class="btn btn-light"
                                                                                                    data-bs-dismiss="modal">Fechar</button>
                                                                                                <button type="submit" class="btn btn-primary submitModal">Atualizar</button>
                                                                                            </div>
                                                                                        </form>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                                @if ($loggedUser->id == $demanda->criador_id || $showColaborador)
                                                                    @if($demanda->finalizada == 0)
                                                                        {{-- @if($demanda->entregue == 1 && $demanda->entregue_recebido == 0)
                                                                            <div class="form-check-inline my-2">
                                                                                <div class="form-check">
                                                                                    <form class="responseAjax" method="POST" action="{{route('Receber_alteracoes', ['id' => $demanda->id])}}">
                                                                                        @csrf
                                                                                        <div class="checkbox my-2">
                                                                                            <div class="form-check adjustStatus">
                                                                                                <button type="submit"  class="form-control reopenJob fin blockBtn submitQuest">Receber pautas</button>
                                                                                            </div>
                                                                                        </div>
                                                                                    </form>
                                                                                </div>
                                                                            </div>
                                                                        @else --}}
                                                                        @if($demanda->pausado == 1)
                                                                            <div class="form-check-inline my-2">
                                                                                <div class="form-check">
                                                                                    <form class="responseAjax" method="POST" action="{{route('Retomar_action', ['id' => $demanda->id])}}">
                                                                                        @csrf
                                                                                        @if($dataAtual->greaterThan($demanda->final))
                                                                                            <div class="checkbox my-2">
                                                                                                <div class="form-check adjustStatus">
                                                                                                    <span data-bs-toggle="modal"  data-bs-target="#modalRetomarJob" class="form-control reopenJob fin blockBtn">Retomar job</span>
                                                                                                </div>
                                                                                            </div>
                                                                                            <div class="col-md-6">
                                                                                                <div class="card">
                                                                                                    <div class="modal fade" id="modalRetomarJob" tabindex="-1" role="dialog">
                                                                                                        <div class="modal-dialog" role="document">
                                                                                                            <div class="modal-content">
                                                                                                                <div class="modal-header">
                                                                                                                    <h5 class="modal-title align-self-center"
                                                                                                                        id="modalRetomarJob">Novo Prazo final</h5>
                                                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                                </div>
                                                                                                                <div class="modal-body">
                                                                                                                    <div class="row">
                                                                                                                        <div class="col-md-12">
                                                                                                                            <div class="mb-3 no-margin">
                                                                                                                                <input name="newFinalDate" required id="newFinalDate" value="{{ old('newFinalDate') }}" class="form-control" type="datetime-local" />
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <div class="modal-footer">
                                                                                                                    <button type="button" class="btn btn-light"
                                                                                                                        data-bs-dismiss="modal">Fechar</button>
                                                                                                                    <button type="submit" class="btn btn-primary sendPauta submitModal">Novo prazo</button>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        @else
                                                                                        <div class="checkbox my-2">
                                                                                            <div class="form-check adjustStatus">
                                                                                                <button type="submit"  class="form-control reopenJob fin blockBtn submitQuest">Retomar job</button>
                                                                                            </div>
                                                                                        </div>
                                                                                        @endif
                                                                                    </form>
                                                                                </div>
                                                                            </div>
                                                                        @else
                                                                        <div class="form-check-inline my-2">
                                                                            <div class="form-check adjustStatus">
                                                                                <form class="responseAjax"  method="POST" action="{{route('Pausar_action', ['id' => $demanda->id])}}">
                                                                                    @csrf
                                                                                    <div class="checkbox my-2">
                                                                                        <button type="submit"  class="stopJob submitQuest">Congelar job</button>
                                                                                    </div>
                                                                                </form>
                                                                                <form class="responseAjax"  method="POST" action="{{route('Finalizar_action', ['id' => $demanda->id])}}">
                                                                                    @csrf
                                                                                    <div class="checkbox my-2">
                                                                                        <div class="form-check adjustStatus" style="padding-left: 0px">
                                                                                            <a href="#alteracao" class="form-control reopenJob alt">Solicitar alteração</a>
                                                                                            @if($demanda->entregue == 1)
                                                                                            <button type="submit"  class="form-control reopenJob fin blockBtn submitFinalize">Finalizar</button>
                                                                                            @endif
                                                                                        </div>
                                                                                    </div>
                                                                                </form>
                                                                            </div>
                                                                        </div>
                                                                        @endif
                                                                    @endif
                                                                @endif
                                                                {{-- @if ($showAg && $demanda->recebido == 0 && $demanda->pausado == 0)
                                                                    <form class="changePauta responseAjax" method="post" action="{{route('Pauta.receber', ['id' => $demanda->id])}}">
                                                                        @csrf
                                                                        <button type="submit"  class="form-control reopenJob fin blockBtn submitQuest">Receber job</button>
                                                                    </form>
                                                                @endif --}}
                                                                @if ($showAg && $demanda->finalizada == 0 && $demanda->pausado == 0)
                                                                    <div class="iniciateJob">
                                                                        @if($demanda->em_pauta == 0 && $demanda->entregue == 0 && $demanda->em_alteracao == 0)
                                                                            <span data-bs-toggle="modal" data-bs-target="#modalCriarTempoPauta" class="form-control reopenJob fin" id="pautaModal">Iniciar a pauta</span>
                                                                            <form class="changePauta responseAjax" method="post" action="{{route('Pauta.criar_tempo', ['id' => $demanda->id])}}">
                                                                                @csrf
                                                                                <div class="col-md-6">
                                                                                    <div class="card">
                                                                                        <div class="modal fade" id="modalCriarTempoPauta" tabindex="-1" role="dialog">
                                                                                            <div class="modal-dialog" role="document">
                                                                                                <div class="modal-content">

                                                                                                    <div class="modal-header">
                                                                                                        <h5 class="modal-title align-self-center"
                                                                                                            id="modalCriarTempoPauta">Prazo sugerido para entrega</h5>
                                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                    </div>
                                                                                                    <div class="modal-body">
                                                                                                        <div class="row">
                                                                                                            <div class="col-md-12">
                                                                                                                <div class="mb-3 no-margin">
                                                                                                                    <input name="sugeridoAg" required id="sugeridoAg" value="{{ old('sugeridoAg') }}" class="form-control" type="datetime-local" />
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="modal-footer">
                                                                                                        <button type="button" class="btn btn-light"
                                                                                                            data-bs-dismiss="modal">Fechar</button>
                                                                                                        <button type="submit" class="btn btn-primary sendPauta submitModal">Adicionar pauta</button>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </form>
                                                                        @endif
                                                                        {{-- @if ($demanda->entregue == 1)
                                                                            <h6>Aguardando uma avalição do criador.</h6>
                                                                        @endif --}}
                                                                        <a href="#alteracao" class="form-control reopenJob alt">Questionar</a>
                                                                    </div>

                                                                @endif
                                                            </div>

                                                            <div class="contenJob">
                                                                <div class="contentJobSingle">
                                                                    <h6>Prazo inicial</h6>
                                                                    <p>{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $demanda->inicio)->format('d/m/Y H:i'); }}</p>
                                                                </div>
                                                                <div class="contentJobSingle">
                                                                    <h6>Prazo de entrega
                                                                        <span class="noneSpan" id="tooltip-container">
                                                                            <span class="noneSpan" data-bs-toggle="tooltip"
                                                                                data-bs-placement="right" data-bs-container="#tooltip-container"
                                                                                title="Essa data poderá sofrer alteração caso seja criada uma pauta em que a nova data seja posterior ao prazo sugerido.">
                                                                                <img class="iconStatus" src="{{url('assets/images/alert.png')}}" >
                                                                            </span>
                                                                        </span>
                                                                    </h6>
                                                                    <p>{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $demanda->final)->format('d/m/Y H:i'); }}
                                                                    </p>

                                                                </div>
                                                                <div class="contentJobSingle">
                                                                    <h6>Criado por</h6>
                                                                    <div class="showUsers">
                                                                        @if($demanda->subCriador)
                                                                        <span style="background-color: #222">{{ $demanda['subCriador']->nome }}</span>
                                                                        @else
                                                                        <span style="background-color: #222">{{ $demanda['criador']->nome }}</span>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="contentJobSingle">
                                                                    <h6>Agência</h6>
                                                                    <p>{{ $demanda['agencia']->nome }}</p>
                                                                </div>
                                                                <div class="contentJobSingle">
                                                                    <h6>Usuário(s)</h6>
                                                                    <div class="showUsers">
                                                                        @foreach ($demanda['demandasUsuario'] as $usuario )
                                                                            <span style="background-color: #222">  {{ $usuario->nome }} </span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                                @if($demanda->drive)
                                                                <div class="contentJobSingle">
                                                                    <h6 style="margin-bottom: 15px">Link</h6>
                                                                    <a class="driveBtn" target="_blank" href="{{$demanda->drive}}">Acessar</a>
                                                                </div>
                                                                @endif
                                                                <div class="contentJobSingle">
                                                                    <h6>Marca</h6>
                                                                    <div class="showUsers">
                                                                        @foreach ($demanda['marcas'] as $marca )
                                                                            <span style="background-color: {{$marca->cor}}">  {{ $marca->nome }} </span>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                                @if(count($demanda['demandaColaboradores']) > 0)
                                                                    <div class="contentJobSingle">
                                                                        <h6>Colaboradores</h6>
                                                                        <div class="showUsers">
                                                                        @foreach ($demanda['demandaColaboradores'] as $colaborador )
                                                                            <span style="background-color: #222">  {{ $colaborador->nome }} </span>
                                                                        @endforeach
                                                                        </div>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-xl-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <div class="adjustBriefing">
                                                                <h5 class="card-title comments">Briefing</h5>
                                                                <a class="arounded" @if($showAg) id="openBriefing" data-userid="{{$loggedUser->id}}" @endif data-bs-toggle="collapse" href="#collapseBriefing" role="button" aria-expanded="false" aria-controls="collapseBriefing">
                                                                </a>
                                                            </div>
                                                            <div class="collapse mt-3" id="collapseBriefing">
                                                                <h6 class="card-title comments">Metas e objetivos</h6>
                                                                <p>{{$demanda->descricoes->metas_objetivos}}</p>
                                                            </div>
                                                            <div class="collapse" id="collapseBriefing">
                                                                <h6 class="card-title comments">Peças necessárias</h6>
                                                                <p>{{$demanda->descricoes->peças}}</p>
                                                            </div>
                                                            <div class="collapse" id="collapseBriefing">
                                                                <h6 class="card-title comments">Formato</h6>
                                                                <p style=" text-transform: math-auto;"><strong>Tipo:</strong>
                                                                @if($demanda->descricoes->formato == 'impresso-digital')
                                                                    <span style="text-transform: inherit;">Impresso e Digital</span>
                                                                @else
                                                                <span style="text-transform: capitalize;">{{$demanda->descricoes->formato}}</span>
                                                                @endif
                                                                </p>
                                                                <p><strong>Descrição:</strong> {{$demanda->descricoes->formato_texto}}</p>
                                                            </div>
                                                            @if($demanda->descricoes->dimensoes)
                                                                <div class="collapse" id="collapseBriefing">
                                                                    <h6 class="card-title comments">Dimensões</h6>
                                                                    <p>{{$demanda->descricoes->dimensoes}}</p>
                                                                </div>
                                                            @endif
                                                            <div class="collapse" id="collapseBriefing">
                                                                <h6 class="card-title comments">Descrição</h6>
                                                                <p class="card-descricao"></p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if(count($demanda['demandasReabertas']) > 0)
                                                    @foreach ($demanda['demandasReabertas'] as $item)
                                                        <div class="col-xl-12">
                                                            <div class="card">
                                                                <div class="card-body">
                                                                    <div class="adjustBriefing">
                                                                        <h5 class="card-title comments">
                                                                            Job {{$item->status}} @if($item->finalizado != null)  <i class="mdi mdi-check-circle " style="color:#3dbb3d; font-size: 16px;"></i> @elseif (Carbon\Carbon::parse($item->finalizado)->greaterThan(Carbon\Carbon::parse($item->sugerido)))  <span class="atrasado">ATRASADO!</span> @endif
                                                                        </h5>
                                                                        <a class="arounded" data-bs-toggle="collapse" href="#collapse-{{$item->id}}" role="button" aria-expanded="false" aria-controls="collapse-{{$item->id}}">
                                                                        </a>
                                                                    </div>
                                                                    <div class="collapse" id="collapse-{{$item->id}}">
                                                                        <div class="contenJob">
                                                                            <div class="contentJobSingle">
                                                                                <h6>Prazo inicial do job reaberto

                                                                                </h6>
                                                                                <p>{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $item->iniciado)->format('d/m/Y H:i'); }}</p>
                                                                            </div>
                                                                            <div class="contentJobSingle">
                                                                                <h6>Novo prazo de entrega
                                                                                    <span class="noneSpan" id="tooltip-container">
                                                                                        <span class="noneSpan" data-bs-toggle="tooltip"
                                                                                            data-bs-placement="right" data-bs-container="#tooltip-container"
                                                                                            title="Essa data poderá sofrer alteração caso seja criada uma pauta em que a nova data seja posterior ao prazo sugerido.">
                                                                                            <img class="iconStatus" src="{{url('assets/images/alert.png')}}" >
                                                                                        </span>
                                                                                    </span>
                                                                                </h6>
                                                                                <p>{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $item->sugerido)->format('d/m/Y H:i'); }}</p>
                                                                            </div>
                                                                            @if($item->finalizado != null)
                                                                                <div class="contentJobSingle">
                                                                                    <h6>Finalizado em</h6>
                                                                                    <p>{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $item->finalizado)->format('d/m/Y H:i'); }}</p>
                                                                                </div>
                                                                            @endif
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                @endif
                                            </div>
                                            <div class="row">
                                                <div class="col-xl-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <h5 class="card-title mb-3 comments">Comentários</h5>
                                                            <div data-simplebar class="commentsScroll" style="max-height: 425px;">
                                                                <div class="activity">
                                                                    @foreach ($demanda['questionamentos'] as $key => $item )
                                                                        <img alt="" class="img-activity" src="{{url('/assets/images/users/')}}/{{$item['usuario']->avatar }}">
                                                                        <div class="time-item" id="{{  preg_replace('/\s+/', '', 'comentario-'.$item->id)}}">
                                                                            <div class="item-info">
                                                                                <div class="text-muted float-end font-size-10 dateComentary">
                                                                                    {{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $item->criado)->format('d/m/Y H:i'); }}

                                                                                </div>
                                                                                <div class="status statusComments">
                                                                                    <h5 class="mb-1">{{ $item['usuario']->nome }}</h5>
                                                                                    <span style="background: {{ $item->cor }}" class="answer">{{ $item->tipo }}</span>
                                                                                    @if($item->usuario_id == $loggedUser->id && count($item->lidosNotificacao) > 0)
                                                                                    <div class="service_box">
                                                                                        <div class="service_box_inner">
                                                                                            <div class="services_box_icon">
                                                                                                <div class="contentLido">
                                                                                                    <i style="font-size: 12px;" class="fa fa-check"></i>
                                                                                                    <i style="font-size: 12px; z-index: -1;" class="fa fa-check"></i>
                                                                                                </div>
                                                                                            </div>
                                                                                            <div class="service_content">
                                                                                                <ul class="services_box_list">
                                                                                                    <li class="text-custom">
                                                                                                        @foreach($item->lidosNotificacao as $lido)
                                                                                                            <span class="services_box__list-text">{!! $lido->usuario->nome !!}<br/> </span>
                                                                                                        @endforeach
                                                                                                    </li>
                                                                                                </ul>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                    @endif
                                                                                </div>
                                                                                <p class="text-muted font-size-13 text-muted-tiny commentsUsers" style="margin-top: 15px">
                                                                                    {{ $item->descricao }}
                                                                                </p>
                                                                                @if($item['usuarioMarcado'] && $item->marcado_usuario_id != null)
                                                                                    <div class="userMarkted">
                                                                                        {{"@".$item['usuarioMarcado']->nome}}
                                                                                    </div>
                                                                                @elseif($item->marcado_usuario_id != null || count($item['lidos']) > 0)
                                                                                    @foreach($item['lidos'] as $lido)
                                                                                        <div class="userMarkted">
                                                                                            {{"@".$lido['usuario']->nome}}
                                                                                        </div>
                                                                                    @endforeach
                                                                                @endif
                                                                                @if ($loggedUser->id == $item->usuario_id)
                                                                                <div class="btns">
                                                                                    <span onclick="getComentary({{ $item->id }})" class="editBt" data-bs-toggle="modal" data-bs-target="#modal_{{ $item->id }}">
                                                                                        <i style="cursor: pointer" class="fas fa-edit"></i>
                                                                                    </span>
                                                                                    <form class="responseAjax" action="{{route('Comentario.delete', ['id' => $item->id])}}" method="post">
                                                                                        @csrf
                                                                                        <div class="right gap-items-2">
                                                                                            <button type="submit" class="submitForm deleteBtn"> <i class="fas fa-trash"></i></button>
                                                                                        </div>
                                                                                    </form>
                                                                                    
                                                                                    <div class="modal fade" id="modal_{{ $item->id }}" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                                                                        <div class="modal-dialog">
                                                                                            <div class="modal-content">
                                                                                                <form class="responseAjax" method="POST" action="{{route('Comentario.edit')}}">
                                                                                                    @csrf
                                                                                                    <div class="modal-header">
                                                                                                        <h5 class="modal-title" id="exampleModalLabel">Editar comentários</h5>
                                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                    </div>
                                                                                                    <div class="modal-body">
                                                                                                        <div class="row">
                                                                                                            <div class="col-md-12">
                                                                                                                <div class="mb-3 no-margin">
                                                                                                                    <select name="marcar_usuario[]" class="select2-multiple form-select select2" id="selectGetCommentary_{{$item->id}}" multiple="multiple">
                                                                                                                        @foreach($resultUsers as $user)
                                                                                                                            @if($user['id'] != $loggedUser->id)
                                                                                                                                <option value="{{$user['id']}}">{{$user['nome']}}</option>
                                                                                                                            @endif
                                                                                                                        @endforeach
                                                                                                                    </select>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                            <div class="col-md-12">
                                                                                                                <div class="mb-3 no-margin">
                                                                                                                    <input type="hidden" class="idComment" name="id" />
                                                                                                                    <textarea class="ckText" id="editor_{{ $item->id }}" name="newContent"></textarea>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="modal-footer">
                                                                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Fechar</button>
                                                                                                        <button type="submit" class="btn btn-primary submitModal">Atualizar</button>
                                                                                                    </div>
                                                                                                </form>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                @endif
                                                                                @if ($loggedUser->id == $demanda->criador_id || $showColaborador)
                                                                                    {{--  @if(count($item['respostas']) == 0 && $item->usuario->tipo == 'agencia' || (count($item['respostas']) == 0 && $item->usuario->tipo != 'admin' && !in_array($item->usuario->id, $idsColaboradoresUser)))) --}}
                                                                                    @if(count($item['respostas']) == 0 && in_array($item->usuario->id, $userDemanda))

                                                                                        <div class="btns">
                                                                                            <span  data-bs-toggle="modal" class="answerBtn" data-bs-target=".responder{{ $key }}">
                                                                                                <i style="cursor: pointer" class="mdi mdi-send"></i>
                                                                                            </span>
                                                                                        </div>
                                                                                        <div class="col-md-6">
                                                                                            <div class="card" style="position: relative">
                                                                                                <div class="modal fade responder{{ $key }}" tabindex="-1" role="dialog">
                                                                                                    <div class="modal-dialog" role="document">
                                                                                                        <div class="modal-content">
                                                                                                            <form class="responseAjax" method="POST" action="{{route('Answer.create', ['id' => $item->id])}}">
                                                                                                                @csrf
                                                                                                                <input type="hidden" name="agenciaId" value="{{$demanda->agencia_id}}"/>
                                                                                                                <div class="modal-header">
                                                                                                                    <h5 class="modal-title align-self-center responder{{ $key }}"
                                                                                                                        id="">Responder</h5>
                                                                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                                </div>
                                                                                                                <div class="modal-body">
                                                                                                                    <div class="row">
                                                                                                                        <div class="col-md-12">
                                                                                                                            <div class="mb-3 no-margin">
                                                                                                                                <input type="hidden" value="{{ $demanda->id }} " name="demandaId"/>
                                                                                                                                <textarea  id="newContent--{{ $key }}"  class="ckText" id="modalEl2" name="newContent"></textarea>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                </div>
                                                                                                                <div class="modal-footer">
                                                                                                                    <button type="button" class="btn btn-light"
                                                                                                                        data-bs-dismiss="modal">Fechar</button>
                                                                                                                    <button type="submit" class="btn btn-primary submitModal">Atualizar</button>
                                                                                                                </div>
                                                                                                            </form>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                    @endif
                                                                                @endif
                                                                            </div>
                                                                        </div>
                                                                        @foreach ($item['respostas'] as $resposta  )
                                                                            @if($resposta != null)
                                                                                <img style="margin-left: 18px" alt="" class="img-activity" src="{{url('/assets/images/users/')}}/{{$resposta->usuario->avatar }}">
                                                                                <div style="margin-left: 18px" class="time-item respostaQ" id="{{  preg_replace('/\s+/', '', 'resposta-'.$resposta->id)}}">
                                                                                    <div class="item-info">
                                                                                        <div class="text-muted float-end font-size-10 dateComentary">
                                                                                        {{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $resposta->criado)->format('d/m/Y H:i'); }}

                                                                                    </div>
                                                                                    <div class="status statusComments">
                                                                                        <h5 class="mb-1">{{ $resposta->usuario->nome }} </h5> <span class="answer">Resposta</span>
                                                                                        @if($resposta->usuario_id == $loggedUser->id && count($resposta->lidosResposta) > 0)
                                                                                        <div class="service_box">
                                                                                            <div class="service_box_inner">
                                                                                                <div class="services_box_icon">
                                                                                                    <div class="contentLido">
                                                                                                        <i style="font-size: 12px;" class="fa fa-check"></i>
                                                                                                        <i style="font-size: 12px; z-index: -1;" class="fa fa-check"></i>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <div class="service_content">
                                                                                                    <ul class="services_box_list">
                                                                                                        <li class="text-custom">
                                                                                                            @foreach($resposta->lidosResposta as $lido)
                                                                                                                <span class="services_box__list-text">{!! $lido->nome !!}<br/> </span>
                                                                                                            @endforeach
                                                                                                        </li>
                                                                                                    </ul>
                                                                                                </div>
                                                                                            </div>
                                                                                        </div>
                                                                                        @endif
                                                                                    </div>
                                                                                        <p class="text-muted font-size-13 text-muted-tiny" style="margin-top: 15px">
                                                                                            {{ $resposta->conteudo }}
                                                                                        </p>
                                                                                        @if($resposta['respostaUsuarioMarcado'])
                                                                                            <div class="userMarkted">
                                                                                                {{"@".$resposta['respostaUsuarioMarcado']->nome}}
                                                                                            </div>
                                                                                        @endif
                                                                                        @if ($loggedUser->id == $resposta->usuario->id)
                                                                                            <div class="btns">
                                                                                                <span onclick="getResponse({{ $resposta->id }})" class="editBt" data-bs-toggle="modal"
                                                                                                    data-bs-target="#editResposta_{{$resposta->id}}">
                                                                                                    <i style="cursor: pointer" class="fas fa-edit"></i>
                                                                                                </span>
                                                                                                <form class="responseAjax" action="{{route('Answer.delete', ['id' => $resposta->id])}}" method="post">
                                                                                                    @csrf
                                                                                                    <div class="right gap-items-2">
                                                                                                        <button type="submit" class="submitForm deleteBtn"> <i class="fas fa-trash"></i></button>
                                                                                                    </div>
                                                                                                </form>
                                                                                            </div>
                                                                                            <div class="col-md-6">
                                                                                                <div class="card">
                                                                                                    <div class="modal fade" id="editResposta_{{$resposta->id}}" role="dialog">
                                                                                                        <div class="modal-dialog" role="document">
                                                                                                            <div class="modal-content">
                                                                                                                <form class="responseAjax" method="POST" action="{{route('Answer.edit')}}">
                                                                                                                    @csrf
                                                                                                                    <div class="modal-header">
                                                                                                                        <h5 class="modal-title align-self-center"
                                                                                                                            id="editResposta_{{$resposta->id}}">Editar resposta</h5>
                                                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                                    </div>
                                                                                                                    <div class="modal-body">
                                                                                                                        <div class="row">
                                                                                                                            <div class="col-md-12">
                                                                                                                                <div class="mb-3 no-margin">
                                                                                                                                    <input type="hidden" class="idCommentResponse" name="id" />
                                                                                                                                    <textarea class="ckText" id="editor_resposta_{{$resposta->id}}" name="newContent"></textarea>
                                                                                                                                </div>
                                                                                                                            </div>
                                                                                                                        </div>
                                                                                                                    </div>
                                                                                                                    <div class="modal-footer">
                                                                                                                        <button type="button" class="btn btn-light"
                                                                                                                            data-bs-dismiss="modal">Fechar</button>
                                                                                                                        <button type="submit" class="btn btn-primary submitModal">Atualizar</button>
                                                                                                                    </div>
                                                                                                                </form>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                </div>
                                                                                            </div>
                                                                                        @endif
                                                                                    </div>
                                                                                </div>
                                                                            @endif
                                                                        @endforeach
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @if($loggedUser->tipo != 'admin_8')
                                                <div class="col-md-12 col-xl-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                            <form id="formCreateCommentary" class="needs-validation comentario responseAjax" method="POST" action="{{route('Comentario.action', ['id' => $demanda->id])}}">
                                                                @csrf
                                                                <div class="row gx-3">
                                                                    <div class="col-md-12">
                                                                        <div class="mb-3" id="alteracao">
                                                                            <h5 class="card-title mb-3">Criar um comentário</h5>
                                                                            @if ($showAg)
                                                                                <select name="tipo" class="form-select select2">
                                                                                    @if($demanda->finalizada != 1)
                                                                                        <option value="questionamento">Questionamento</option>
                                                                                    @endif
                                                                                    <option value="observacao">Observação</option>
                                                                                    @if($demanda->entregue == 1)
                                                                                        <option value="entregue">Entregue</option>
                                                                                    @endif
                                                                                </select>
                                                                                <br />  <br />
                                                                                <select name="marcar_usuario[]" class="select2-multiple form-control my-select"  id="select2Multiple"  multiple="multiple">
                                                                                    <option value="">Marcar usuário</option>
                                                                                    @foreach($resultUsers as $user)
                                                                                        @if($user['id'] != $loggedUser->id)
                                                                                            <option value="{{$user['id']}}">{{$user['nome']}}</option>
                                                                                        @endif
                                                                                    @endforeach
                                                                                </select>
                                                                                <br />  <br />
                                                                            @endif
                                                                            @if ($loggedUser->id == $demanda->criador_id || $showColaborador)
                                                                                <select name="tipo" class="form-select select2">
                                                                                    @if($demanda->finalizada == 0 && $demanda->pausado == 0 )
                                                                                     <option value="alteracao">Alteração</option>
                                                                                    @endif
                                                                                    <option value="observacao">Observação</option>
                                                                                    @if($demanda->finalizada == 1 )
                                                                                        <option value="finalizado">Finalizado</option>
                                                                                    @endif
                                                                                </select>
                                                                                <br />  <br />
                                                                                <select name="marcar_usuario[]" class="select2-multiple form-control my-select"  id="select2Multiple"  multiple="multiple">
                                                                                    <option value="">Marcar usuário</option>
                                                                                    @foreach($resultUsers as $user)
                                                                                        @if($user['id'] != $loggedUser->id)
                                                                                            <option value="{{$user['id']}}">{{$user['nome']}}</option>
                                                                                        @endif
                                                                                    @endforeach
                                                                                </select>
                                                                                <br />  <br />
                                                                                @if($demanda->finalizada == 0 && $demanda->pausado == 0)
                                                                                    <input name="sugeridoComment" id="sugeridoComment" value="" class="form-control" type="datetime-local" style="margin-bottom: 20px" />
                                                                                @endif

                                                                            @elseif($isAdmin && !$showAg)
                                                                                <select name="tipo" class="form-select select2">
                                                                                    <option value="observacao">Observação</option>
                                                                                </select>
                                                                                <br />  <br />
                                                                                <select name="marcar_usuario[]" class="select2-multiple form-control my-select"  id="select2Multiple"  multiple="multiple">
                                                                                    <option value="">Marcar usuário</option>
                                                                                    @foreach($resultUsers as $user)
                                                                                        @if($user['id'] != $loggedUser->id)
                                                                                            <option value="{{$user['id']}}">{{$user['nome']}}</option>
                                                                                        @endif
                                                                                    @endforeach
                                                                                </select>
                                                                                <br />  <br />
                                                                            @endif
                                                                            <textarea class="ckText" name="conteudo">{{ old('conteudo')}}</textarea>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <button id="submitButtonCreateCommentary" class="btn btn-light mb-0 w-auto leftAuto verifyBtn submitModal" type="submit">Enviar</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endif
                                                {{-- <div class="col-md-12 col-xl-12">
                                                    <div class="card">
                                                        <div class="card-body">
                                                             <form class="responseAjax" method="post" action="{{route('Imagem.upload', ['id' => $demanda->id])}}" enctype="multipart/form-data"  method="post">
                                                                @csrf
                                                                <div class="row gx-3">
                                                                    <div class="col-md-12">
                                                                        <div class="mb-3">
                                                                            <input data-url="{{route('Imagem.upload', ":id")}}" type="file" id="file" class="notReload" name="file[]" multiple required/>
                                                                            <input type="hidden" id="textbox_id" value="{{ $demanda->id }}"/>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div> --}}
                                                @if ($loggedUser->id == $demanda->criador_id || $showColaborador)
                                                    <div class="col-md-12 col-xl-12">
                                                        {{-- <a href="{{route('Job.delete', ['id' => $demanda->id])}}" class="btn btn-outline-secondary btn-sm edit deleteBt btnDanger" style="background-color: #f73e1d" title="Deletar">
                                                            EXCLUIR JOB
                                                        </a> --}}
                                                        <form class="responseAjax" action="{{ route('Job.delete', ['id' => $demanda->id]) }}" method="POST">
                                                            @csrf
                                                            <button type="submit" class="submitForm btnDeleteJob" style="background-color: #f73e1d" title="Deletar">
                                                                EXCLUIR JOB
                                                            </button>
                                                        </form>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="tab-pane" id="pautas" role="tabpanel">
                                            <div class="row">
                                                <div class="col-xl-12">
                                                    @if(count($demanda['prazosDaPauta']) > 0)
                                                        <div class="adjustPautas">
                                                            <p><strong>Agência: {{ $demanda['agencia']->nome }}</strong></p>
                                                            <div class="progressiveBar">
                                                                <small class="float-end ms-2 font-size-12">{{$demanda->porcentagem}}%</small>
                                                                <div class="progress" style="height: 4.5px">
                                                                    <div
                                                                    class="progress-bar bg-primary"
                                                                    role="progressbar"
                                                                    style="width: {{$demanda->porcentagem}}%"
                                                                    aria-valuenow="{{$demanda->porcentagem}}"
                                                                    aria-valuemin="0"
                                                                    aria-valuemax="100"
                                                                    ></div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                    @foreach ($demanda['prazosDaPauta'] as $key => $item )
                                                        <div class="card">
                                                            <div class="card-body">
                                                                <div class="initalResume">
                                                                    <div class="nameJob">
                                                                        <h5>{{ $item->status }}
                                                                            @if($item->finalizado != null)
                                                                                <i class="mdi mdi-check-circle " style="color:#3dbb3d; font-size: 16px;"></i>
                                                                                @if($item->atrasada == 0)
                                                                                    <span class="noPrazo">(Entregue no prazo)</span>
                                                                                @else
                                                                                    <span class="atrasadoText">(Entregue com atraso)</span>
                                                                                @endif
                                                                            @endif

                                                                        </h5>
                                                                    </div>
                                                                    @if ($showAg && $demanda->pausado == 0)
                                                                        {{-- @if($item->recebido == 0 && $item->code_tempo == 'alteracao')
                                                                            <form class="responseAjax" method="POST" action="{{route('Pauta.receber_alteracao', ['id' => $item->id])}}">
                                                                                @csrf
                                                                                <input type="submit" class="form-control reopenJob fin blockBtn submitQuest activePauta" value="Receber alteração">
                                                                                <input type="hidden" name="demandaId" value="{{$demanda->id}}">
                                                                            </form>
                                                                        @endif --}}
                                                                        @if($item->sugerido != null)
                                                                            @if($item->iniciado == null)
                                                                                <form class="responseAjax" method="POST" action="{{route('Pauta.iniciar_tempo', ['id' => $item->id])}}">
                                                                                    @csrf
                                                                                    <input type="submit" class="form-control reopenJob fin blockBtn submitQuest activePauta" value="Iniciar">
                                                                                    <input type="hidden" name="demandaId" value="{{$demanda->id}}">
                                                                                </form>
                                                                            @endif
                                                                            @if($item->iniciado != null && $item->finalizado == null)
                                                                                @if($deliveriesReq == 0)
                                                                                <form class="responseAjax" method="POST" action="{{route('Pauta.finalizar_tempo', ['id' => $item->id])}}">
                                                                                    @csrf
                                                                                    <input type="submit" class="form-control reopenJob fin blockBtn submitQuest activePauta" value="Concluir">
                                                                                    <input type="hidden" name="demandaId" value="{{$demanda->id}}">
                                                                                </form>
                                                                                @endif
                                                                            @endif
                                                                        @endif
                                                                    @endif
                                                                </div>
                                                                <div class="">
                                                                    @foreach ($item['comentarios'] as $comentario)
                                                                        <h6>Descrição da alteração:</h6>
                                                                        <p class="text-muted font-size-13 text-muted-tiny" style="margin-top: 6px">
                                                                            {{ $comentario['descricao'] }}
                                                                        </p>
                                                                    @endforeach
                                                                </div>
                                                                <div class="contenJob">
                                                                    <div class="contentJobSingle">
                                                                        <h6>Iniciada em</h6>
                                                                            @if($item->iniciado == null)
                                                                            <span class="borderPautas" style="background: #686667">Aguardando...</span>
                                                                            @else
                                                                                <span class="borderPautas" style="background: #3dbb3d">{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $item->iniciado)->format('d/m/Y H:i'); }}</span>
                                                                            @endif
                                                                    </div>
                                                                    <div class="contentJobSingle">
                                                                        @if($item->code_tempo == 'alteracao')
                                                                            <h6>Novo prazo para entrega</h6>
                                                                        @else
                                                                            <h6>Prazo para entrega</h6>
                                                                        @endif
                                                                        @if($item->sugerido == null)
                                                                            <span class="borderPautas" style="background: #686667">Não definido...</span>
                                                                        @else
                                                                            <span class="borderPautas" style="background: #34495E">{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $item->sugerido)->format('d/m/Y H:i'); }}</span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="contentJobSingle">
                                                                        <h6>Finalizada em</h6>
                                                                        @if($item->finalizado == null)
                                                                        <span class="borderPautas" style="background: #686667">Aguardando...</span>
                                                                        @else
                                                                            <span class="borderPautas" style="background: {{ $item->atrasada == 0 ? '#3dbb3d' : '#f73e1d' }}">{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $item->finalizado)->format('d/m/Y H:i'); }}</span>
                                                                        @endif
                                                                    </div>
                                                                    <div class="contentJobSingle">
                                                                        <h6>Tempo entre iniciada e finalizada</h6>
                                                                        @if($item->final != null)
                                                                            <p> {{ $item->final }}  </p>
                                                                            @else <span class="borderPautas" style="background: #686667">Aguardando...</span>
                                                                        @endif
                                                                    </div>
                                                                    @if($demanda->pausado == 0)
                                                                        <div class="contentJobSingle">
                                                                            <div class="acceptRefuseBtn">
                                                                                @if($item->sugerido != null)
                                                                                    @if($showAg)
                                                                                        @if($item->aceitar_agencia == 0)
                                                                                            <form class="responseAjax" method="POST" action="{{route('Pauta.Aceitar_tempo_agencia', ['id' => $item->id])}}">
                                                                                                @csrf
                                                                                                <input class="accept submitQuest activePauta" type="submit" value="Aceitar novo prazo">
                                                                                            </form>
                                                                                        @endif
                                                                                    @endif
                                                                                    @if($loggedUser->id == $demanda->criador_id || $showColaborador)
                                                                                        @if($item->aceitar_colaborador == 0 )
                                                                                            <form class="responseAjax" method="POST" action="{{route('Pauta.Aceitar_tempo_colaborador', ['id' => $item->id])}}">
                                                                                                @csrf
                                                                                                <input class="accept submitQuest activePauta" type="submit" value="Aceitar novo prazo">
                                                                                            </form>
                                                                                        @endif
                                                                                    @endif
                                                                                @endif
                                                                                @if($loggedUser->id == $demanda->criador_id || $showColaborador || $showAg)
                                                                                    @if($item->finalizado == null)
                                                                                        <span data-bs-toggle="modal" data-bs-target=".modalPautaEditar{{ $key }}">{{$item->sugerido != null ? 'Alterar prazo sugerido' : 'Definir um prazo'}}</span>
                                                                                    @endif
                                                                                @endif
                                                                            </div>
                                                                            <div class="col-md-12">
                                                                                <div class="card">
                                                                                    <div class="modal fade modalPautaEditar{{ $key }}"  tabindex="-1" role="dialog">
                                                                                        <div class="modal-dialog" role="document">
                                                                                            <div class="modal-content">
                                                                                                <form method="POST" class="sugeridoForm responseAjax" action="{{route('Demanda.prazo.action', ['id' => $item->id])}}">
                                                                                                    @csrf
                                                                                                    <div class="modal-header">
                                                                                                        <h5 class="modal-title align-self-center modalPautaEditar{{ $key }}"
                                                                                                        >Novo prazo para entrega</h5>
                                                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                                                    </div>
                                                                                                    <div class="modal-body">
                                                                                                        <div class="row">
                                                                                                            <div class="col-md-12">
                                                                                                                <div class="mb-3 no-margin">
                                                                                                                    <input required name="sugerido" value="{{ old('sugerido') }}" class="form-control sugerido" id="sugerido--{{ $key }}" type="datetime-local" />
                                                                                                                    <br/>
                                                                                                                    <label class="form-label">Descreva o motivo da sua nova alteração!</label>
                                                                                                                    <textarea id="sugeridoText--{{ $key }}" class="ckText" id="modalEl3" name="sugeridoAlt" ></textarea>
                                                                                                                </div>
                                                                                                            </div>
                                                                                                        </div>
                                                                                                    </div>
                                                                                                    <div class="modal-footer">
                                                                                                        <button type="button" class="btn btn-light"
                                                                                                            data-bs-dismiss="modal">Fechar</button>
                                                                                                        <button type="submit" class="btn btn-primary submitModal">Editar pauta</button>
                                                                                                    </div>
                                                                                                </form>
                                                                                            </div>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@section('plugins')
    <script src="{{ asset('assets/js/jquery.mask.min.js') }}" ></script>
    <script src="{{ asset('assets/js/pages/form-editor.init.js') }}"></script>
    <script src="{{ asset('assets/js/jqueryui.js') }}" ></script>
    <script src="{{ asset('assets/js/momentlocale.js') }}" ></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/bootstrap-datepicker.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.3.0/js/locales/bootstrap-datepicker.pt-BR.min.js"></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/raphael/2.3.0/raphael.min.js'></script>
@endsection

@section('scripts')
    <script src="{{ asset('assets/js/select2.js') }}" ></script>
    <script src="{{ asset('assets/js/select2pt-br.js') }}" ></script>
    <script type="text/javascript">
        !function(a){a.fn.percentageLoader=function(b){this.each(function(){function q(){p.customAttributes.arc=function(a,b,c){var h,d=360/b*a,e=(90-d)*Math.PI/180,f=j+c*Math.cos(e),g=k-c*Math.sin(e);return h=b==a?[["M",j,k-c],["A",c,c,0,1,1,j-.01,k-c]]:[["M",j,k-c],["A",c,c,0,+(d>180),1,f,g]],{path:h}},p.path().attr({arc:[100,100,l],"stroke-width":d.strokeWidth,stroke:d.bgColor}),e&&(m=p.path().attr({arc:[.01,100,l],"stroke-width":d.strokeWidth,stroke:d.ringColor,cursor:"pointer"}),r(e,100,l,m,2)),n=p.text(j,k,e+"%").attr({font:d.fontWeight+" "+d.fontSize+" Arial",fill:d.textColor})}function r(a,b,c,d){f?d.animate({arc:[a,b,c]},900,">"):a&&a!=b?d.animate({arc:[a,b,c]},750,"elastic"):(a=b,d.animate({arc:[a,b,c]},750,"bounce",function(){d.attr({arc:[0,b,c]})}))}var c=a(this),d=a.extend({},a.fn.percentageLoader.defaultConfig,b),e=parseInt(c.children(d.valElement).text()),f=!0,h=parseInt(c.css("width")),i=parseInt(c.css("height")),j=h/2,k=i/2,l=j-d.strokeWidth/2,m=null,n=null,p=Raphael(this,h,i);q()})},a.fn.percentageLoader.defaultConfig={valElement:"p",strokeWidth:20,bgColor:"#d9d9d9",ringColor:"#d53f3f",textColor:"#9a9a9a",fontSize:"12px",fontWeight:"normal", marginTop:'-50px'}}(jQuery);
        $('.percent').percentageLoader({
		  bgColor: 'rgba(0,0,0,.2)',
		  ringColor: '#0abde3',
		  textColor: 'transparent',
		  fontSize: '0px',
		  strokeWidth: 4
		});

        window.onload = function() {
            var activity = document.querySelector(".activity");
            activity.scrollTop = activity.scrollHeight;
        };

        if (typeof SimpleBar !== 'undefined') {
            // Encontra a div com SimpleBar
            var simpleBarInstance = new SimpleBar(document.querySelector('.commentsScroll'));

            // Ajusta o scroll para o final
            var scrollElement = simpleBarInstance.getScrollElement();
            scrollElement.scrollTop = scrollElement.scrollHeight;
        } else {
            console.error('SimpleBar library is not loaded.');
        }

        $('.date-multiple').datepicker({
            multidate: true,
            format: 'dd-mm-yyyy',
            language: 'pt-BR',
            clearBtn: true,
            daysOfWeekDisabled: [0, 6], // Desabilita domingos (0) e sábados (6)
            multidateSeparator: ' | ',
            todayHighlight: true,
            startDate: new Date(), // Define a data mínima como a data atual
        });

        $('#weekpicker').on('change', function() {
            var selectedDate = $(this).val();
            var date = new Date(selectedDate);
            var dayOfWeek = date.getDay();
            if (dayOfWeek === 6 || dayOfWeek === 0) {
                alert('Por favor, selecione uma semana que não inclua sábado ou domingo.');
                $(this).val('');
            }
        });

        let briefing = @json($demanda->descricoes);

        $(".card-descricao").html(briefing.descricao);
        

        $('.select2').select2({
            minimumResultsForSearch: Infinity
        });

        $('.text-muted-tiny').each(function(){
             var txt = $(this).text();
            $(this).html(txt);
        });

        $('.nav-item').bind('click', function(){
            var initialSlideValue = $('.carousel').slick('slickCurrentSlide');
            $('.carousel').slick('refresh');
            $('.carousel').slick('slickGoTo', initialSlideValue);
        });

        $(document).ready(function () {
            var csrfToken = $('input[name="_token"]').val();

            let briefingCount = @json($briefingCount);

            if(briefingCount == 0){
                $('#openBriefing').on('click', function(){
                    userid = $(this).attr("data-userid");
                    $.ajax({
                        type: 'POST',
                        url: '/ler/briefing/job',
                        data: {
                            user: userid, 
                            demanda_id: @json($demanda->id),
                            _token: csrfToken,
                        },
                        success: function (response) {
                        console.log('Briefing lido com sucesso!')

                        },
                        error: function (error) {
                            console.log('Briefing não foi lido!')

                        }
                    });
                });
            }

            $('.select2-multiple').select2({
                placeholder: "Selecionar usuário(s)",
                allowClear: true,
                templateSelection: function (data, container) {
                    $(container).css("background-color", '#000'); // define a cor de fundo do option
                    return data.text;
                },
            });

            $('.tipoRecorrencia').on('change', function() {
                tipoValue = $('select[name=tipoRecorrencia] option').filter(':selected').val();
                if(tipoValue == 'Mensal'){
                    $('.mensal').removeClass('hidden');
                    $('.anual').addClass('hidden');
                    $('.semanal').addClass('hidden');
                }

                if(tipoValue == 'Anual'){
                    $('.mensal').addClass('hidden');
                    $('.semanal').addClass('hidden');
                    $('.anual').removeClass('hidden');
                }

                if(tipoValue == 'Semanal'){
                    $('.semanal').removeClass('hidden');
                    $('.anual').addClass('hidden');
                    $('.mensal').addClass('hidden');
                    $('.daterangepicker ').addClass('semanalPicker');

                }
            });

            $("p.commentsUsers a").attr("target", "_blank");
            $("p.card-descricao a").attr("target", "_blank");

            $('select[name="tipo"]').on('change', function() {
                var selectedValue = $(this).val();
                if (selectedValue == 'alteracao') {
                $('#sugeridoComment').fadeIn().prop('required', true);
                } else {
                $('#sugeridoComment').fadeOut().prop('required', false);
                }
            });

            if(localStorage.getItem('formSubmitted')) {
                $('#pautasLink').addClass('active').attr('aria-selected', 'true');
                $('#job').removeClass('active');
                $('#projectLink').removeClass('active').attr('aria-selected', 'false');
                $('#anexos').removeClass('active');
                $('#pautas').addClass('active');

                localStorage.removeItem('formSubmitted');
            }

            $('.activePauta').on('click', function(){
                localStorage.setItem('formSubmitted', 'true');
            });

            function validarDataInicial(input) {
                $('#jobs').text('').css('display','none');
                input.on('change', function() {
                    var value = input.val();
                    var selectedDate = new Date(value);
                    var currentDate = new Date();
                    var day = selectedDate.getDay();
                    currentDate.setHours('');
                    // Verificar se a data selecionada é um fim de semana (sábado ou domingo)
                    if (day == 0 || day == 6) {
                    input.val('');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Data inválida',
                        text: 'Favor, selecione uma data em um dia útil.',
                    });
                    } else if (selectedDate < currentDate) {
                    input.val('');
                    Swal.fire({
                        icon: 'warning',
                        title: 'Data inválida',
                        text: 'Favor, selecione uma data posterior ou igual à data atual.',
                    });
                    }
                });
            }

            $('.sugerido').each(function() {
                var input = $(this);
                validarDataInicial(input);
                input.on('focus', function() {
                    validarDataInicial(input);
                });
            });

            function validarDataRecorrencia(input) {
                input.on('change', function() {
                    var value = input.val();

                    var [year, month, day] = value.split('-');
                    var selectedDate = new Date(year, month - 1, day);

                    var currentDate = new Date();
                    currentDate.setHours(0, 0, 0, 0);

                    var selectedDay = selectedDate.getDay();
                    console.log(selectedDate);

                    if (selectedDay == 0 || selectedDay == 6) {
                        input.val('');
                        Swal.fire({
                            icon: 'warning',
                            title: 'Data inválida',
                            text: 'Favor, selecione uma data em um dia útil.',
                        });
                    } else if (selectedDate < currentDate) {
                        input.val('');
                        Swal.fire({
                            icon: 'warning',
                            title: 'Data inválida',
                            text: 'Favor, selecione uma data posterior ou igual à data atual.',
                        });
                    }
                });
            }


            
            $('.dataRecorrencia').each(function() {
                var input = $(this);
                validarDataRecorrencia(input);
                input.on('focus', function() {
                    validarDataRecorrencia(input);
                });
            });

            var inputSugeridoComment = $('#sugeridoComment');
            var inputSugeridoAg = $('#sugeridoAg')
            validarDataInicial(inputSugeridoComment);
            validarDataInicial(inputSugeridoAg);

            $('.form-ag ').on('click', function() {
                var checkbox = $(this);
                var checkboxValue = checkbox.val();
                var isCheck = checkbox.is(':checked');

                $.ajax({
                    type: 'POST',
                    url: '/adicionar/usuario/job',
                    data: {
                        checkboxValue,
                        isCheck,
                        demandaId: @json($demanda->id),
                        _token: csrfToken,
                    },
                    success: function (response) {
                        Swal.fire({
                            icon:  response.type,
                            text: response.message
                        });

                        if (response.type === 'error') {
                            checkbox.prop('checked', !isCheck);
                        }
                    },
                    error: function (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: 'Não foi possível adicionar/remover este usuário!',
                        });
                        // Marque o checkbox novamente em caso de erro
                        checkbox.prop('checked', !isCheck);
                    }
                });
            });

            $('.form-col').on('click', function() {
                var checkbox = $(this);
                var checkboxValue = checkbox.val();
                var isCheck = checkbox.is(':checked');

                $.ajax({
                    type: 'POST',
                    url: '/adicionar/colaborador/job',
                    data: {
                        checkboxValue,
                        isCheck,
                        demandaId: @json($demanda->id),
                        _token: csrfToken,
                    },
                    success: function (response) {
                        Swal.fire({
                            icon:  response.type,
                            text: response.message
                        });

                        if (response.type === 'error') {
                            checkbox.prop('checked', !isCheck);
                        }
                    },
                    error: function (error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Erro',
                            text: 'Não foi possível adicionar/remover este usuário!',
                        });
                        // Marque o checkbox novamente em caso de erro
                        checkbox.prop('checked', !isCheck);
                    }
                });
            });

            var referencia = getUrlParameter('comentario');

            if (referencia) {
                let divElement = $('#' + referencia);
                divElement.addClass('commentActive');
            }

        });

        function getUrlParameter(name) {
            name = name.replace(/[[]/, '\\[').replace(/[\]]/, '\\]');
            var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
            var results = regex.exec(location.search);
            return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
        }   

    </script>
@endsection
