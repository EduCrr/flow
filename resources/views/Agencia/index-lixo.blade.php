@php
    $layout = $isAdminAg > 0 ? 'layouts.agencia' : 'layouts.colaborador';
@endphp

@extends($layout)
@section('title', 'Minhas pautas')

@section('css')
    {{-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.4.0/fullcalendar.css" /> --}}
    <link href="{{ asset('assets/css/calendar.css') }}" rel="stylesheet" type="text/css" />

@endsection
{{-- 
@section('content')

    <section>
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card tableHome">
                                <div class="card-body">
                                   <div class="changeDem">
                                        <h5 class="card-title">Jobs recentes</h5>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="table-responsive" id="jobs" >
                                                <table class="table table-hover table-centered table-nowrap mb-0">
                                                     @if(count($demandas) === 0)
                                                        <p>Nenhum job foi encontrado!</p>
                                                        @else
                                                        <thead>
                                                            <tr>
                                                                <th>Título</th>
                                                                <th>Prioridade</th>
                                                                <th>Status</th>
                                                                <th>Prazo inicial</th>
                                                                <th>Prazo de entrega</th>
                                                                <th>Agencia</th>
                                                                <th>Marca(s)</th>
                                                                <th>Editar</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($demandas as $demanda )
                                                                @if ($demanda['agencia'])
                                                                <tr class="trLink" style="cursor: pointer;" data-href="{{route('Job', ['id' => $demanda->id])}}">
                                                                    <td class="title"> {{ $demanda->titulo }}</td>
                                                                        <td>
                                                                            <span class="badge" style="background-color: {{ $demanda->cor }}">
                                                                                @if($demanda->prioridade === 10)
                                                                                    URGENTE 
                                                                                    @elseif($demanda->prioridade === 5)
                                                                                    MÉDIA 
                                                                                    @elseif($demanda->prioridade === 1)
                                                                                    BAIXA 
                                                                                    @elseif($demanda->prioridade === 7)
                                                                                    ALTA 
                                                                                @endif
                                                                            </span>
                                                                        </td>
                                                                        <td>
                                                                            @if($demanda->em_pauta == 0 && $demanda->recebido == 1 && $demanda->finalizada == 0 && $demanda->entregue_recebido == 0 && $demanda->entregue == 0 && $demanda->em_alteracao == 0 && $demanda->pausado == 0)
                                                                                <span class="statusBadge" style="margin: 0px; background-color: #ffc7a5" style="margin: 0px">RECEBIDO</span>
                                                                            @elseif($demanda->em_pauta == 1 && $demanda->pausado == 0)
                                                                                <span class="statusBadge" style="margin: 0px; background-color: #ffa76d">EM PAUTA</span>
                                                                            @elseif ($demanda->em_pauta == 0 && $demanda->finalizada == 0 && $demanda->entregue == '0' && $demanda->pausado == 0)
                                                                                <span style="background-color: #ffb887" class="statusBadge" style="margin: 0px">PENDENTE</span>
                                                                            @elseif($demanda->entregue == 1  && $demanda->pausado == 0)
                                                                                <span style="background-color: #ff9652"  class="statusBadge" style="margin: 0px">ENTREGUE</span> 
                                                                            @elseif($demanda->pausado == 1)
                                                                                <span class="statusBadge" style="margin: 0px; background-color: #ffd5bf">CONGELADO</span> 
                                                                            @elseif($demanda->finalizada == 1)
                                                                                <span style="background-color: #ff8538" class="statusBadge" style="margin: 0px">FINALIZADO</span> 
                                                                            @endif
                                                                        </td>
                                                                        <td>{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $demanda->inicio)->format('d/m/Y H:i'); }}</td>
                                                                        <td>
                                                                            {{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $demanda->final)->format('d/m/Y H:i'); }}
                                                                            @if($demanda->entregue == 0 && $demanda->finalizada == 0 && $dataAtual->greaterThan($demanda->final))
                                                                                <span class="atrasado">ATRASADO!</span>
                                                                                @elseif($demanda->finalizada == 1 && $demanda->atrasada == 1)
                                                                                <span class="atrasado">ENTREGUE COM ATRASO!</span>
                                                                                @elseif($demanda->entregue == 1 && $demanda->finalizada == 0 && $demanda->atrasada == 1)
                                                                                <span class="atrasado">ATRASADO!</span>
                                                                            @endif
                                                                        </td>
                                                                        <td>
                                                                            {{ $demanda['agencia']->nome }}
                                                                        </td>
                                                                        <td>  
                                                                            @foreach ($demanda['marcas'] as $marca )
                                                                                <span>{{ $marca->nome }}</span>
                                                                            @endforeach
                                                                        </td>
                                                                       
                                                                        <td>
                                                                            <a href="{{route('Job.copiar', ['id' => $demanda->id])}}" class="btn btn-outline-secondary btn-sm edit" style="background-color: #a1a1a1" title="Copiar">
                                                                                <i class="fas fa-copy"></i>
                                                                            </a>
                                                                        </td>
                                                                        <td>
                                                                            @if($demanda->count_questionamentos > 0 )
                                                                                <span>
                                                                                    <i class="fas fa-comment-dots msg"></i>
                                                                                </span>
                                                                            @endif
                                                                            
                                                                        </td>
                                                                    </tr>
                                                                @endif
                                                            @endforeach
                                                            
                                                        </tbody>
                                                    @endif
                                                </table>
                                               
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="adjustPagination">
                                    <div class="text-primary">
                                        <div>
                                            <ul class="pagination">
                                                @if ($demandas->currentPage() > 1)
                                                    <li class="page-item">
                                                        <a class="page-link" href="{{ $demandas->previousPageUrl() }}" aria-label="Anterior">
                                                            <span aria-hidden="true">&laquo;</span>
                                                        </a>
                                                    </li>
                                                @endif
                                        
                                                @if ($demandas->currentPage() > 3)
                                                    <li class="page-item">
                                                        <a class="page-link" href="{{ $demandas->url(1) }}">1</a>
                                                    </li>
                                                    @if ($demandas->currentPage() > 4)
                                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                                    @endif
                                                @endif
                                        
                                                @for ($i = max(1, $demandas->currentPage() - 2); $i <= min($demandas->currentPage() + 2, $demandas->lastPage()); $i++)
                                                    <li class="page-item {{ ($demandas->currentPage() == $i) ? 'active' : '' }}">
                                                        <a class="page-link" href="{{ $demandas->url($i) }}">{{ $i }}</a>
                                                    </li>
                                                @endfor
                                        
                                                @if ($demandas->currentPage() < $demandas->lastPage() - 2)
                                                    @if ($demandas->currentPage() < $demandas->lastPage() - 3)
                                                        <li class="page-item disabled"><span class="page-link">...</span></li>
                                                    @endif
                                                    <li class="page-item">
                                                        <a class="page-link" href="{{ $demandas->url($demandas->lastPage()) }}">{{ $demandas->lastPage() }}</a>
                                                    </li>
                                                @endif
                                        
                                                @if ($demandas->currentPage() < $demandas->lastPage())
                                                    <li class="page-item">
                                                        <a class="page-link" href="{{ $demandas->nextPageUrl() }}" aria-label="Próxima">
                                                            <span aria-hidden="true">&raquo;</span>
                                                        </a>
                                                    </li>
                                                @endif
                                            </ul>
                                        </div>
                                    </div>
                                    <a href="{{route('Jobs')}}" class="text-primary btnHome">Ver todos <i class="mdi mdi-arrow-right"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card mb-0">
                                <div class="card-body">
                                    <h5 class="card-title mb-4 ">Jobs em pauta</h5>
                                    <div id="calendar"></div>
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
@endsection

@section('scripts')

    <script src="{{ asset('assets/js/jqueryui.js') }}" ></script>
    <script src="{{ asset('assets/js/moment.js') }}" ></script>
    <script src="{{ asset('assets/js/fullcalendar.js') }}" ></script>
    <script src="{{ asset('assets/js/select2.js') }}" ></script>

 
    <script>
        $(document).ready(function() {
            let demandas = @json($events);

            
            $('#calendar').fullCalendar({
                header:{
                    'left' : 'prev, next, today',
                    'center':  'title',
                    'right': 'month, agendaWeek, agendaDay',
                },
                events: demandas,
                monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
                monthNamesShort: ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Aug', 'Set', 'Out', 'Nov', 'Dez'],
                dayNames: ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'],
                dayNamesShort: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sab'],
                buttonText: {
                    today:    'Hoje',
                    month:    'Mês',
                    week:     'Semana',
                    day:      'Dia'
                },
                displayEventTime : false
            });
            
            $('.text-muted-tiny').each(function(){
                var txt = $(this).text();
                $(this).html(txt);
            });

        });
    </script>
@endsection --}}


