@extends('layouts.colaborador')
@section('title', 'Minhas pautas')

@section('css')
    <link href="{{ asset('assets/css/calendar.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/daterangepicker.css') }}" rel="stylesheet" type="text/css" />
@endsection

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
                                        <div class="adjustTablePage">
                                            <div class="adjustOrdemBt">
                                                <h5 class="card-title">Jobs recentes</h5>
                                                <button id="openColumnOrderModal" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#columnOrderModal"><i class="fas fa-sort"></i></button>
                                            </div>
                                            <a href="{{route('Jobs')}}" class="text-primary btnHome">Ver todos <i class="mdi mdi-arrow-right"></i></a>
                                        </div>
                                        <div id="columnOrderModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-centered" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Ordenar Colunas</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <ul id="sortableColumns" class="list-group">
                                                           <li class="list-group-item" data-column-index="0">Job</li>
                                                           <li class="list-group-item" data-column-index="1">Ação</li>
                                                           <li class="list-group-item" data-column-index="2">Prioridade</li>
                                                           <li class="list-group-item" data-column-index="3">Título</li>
                                                           <li class="list-group-item" data-column-index="4">Status</li>
                                                           <li class="list-group-item" data-column-index="5">Prazo inicial</li>
                                                           <li class="list-group-item" data-column-index="6">Prazo de entrega</li>
                                                           <li class="list-group-item" data-column-index="7">Progresso</li>
                                                           <li class="list-group-item" data-column-index="8">Agencia</li>
                                                           <li class="list-group-item" style="display: none" data-column-index="9"></li>
                                                        </ul>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <form method="POST" action="{{route('Job.ordem')}}" enctype="multipart/form-data" class="responseAjax">
                                                            @csrf
                                                            <input id="columnOrderInput" name="ordem" type="hidden" value="{{$ordemValue ? $ordemValue : ''}}" />
                                                            <button type="submit" class="btn btn-primary saveOrdem">Salvar</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-sm-12">
                                            <div class="table-responsive" id="jobOrdem">
                                                <div class="d-flex justify-content-center loadingHelper" style="height: 15px;">
                                                    <div class="spinner-border" role="status">
                                                        <span class="sr-only">Carregando...</span>
                                                    </div>
                                                </div>
                                                @component('components.TabelaColaboradorComponent', ['demandas' => $demandas, 'arrayOrdem' => $arrayOrdem, 'ordem' => $ordem])@endcomponent
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card card-adjust">
                                <div class="card-body">
                                    <div class="initialEst">
                                        <h5 class="card-title">Jobs estatísticas</h5>
                                        </h5>
                                        <div class="textPie">
                                            <p class="atrasadoCount" style="background: #c7d2db; padding: 5px; color:white;">Em atraso: {{$atrasadoCount}}</p>
                                            <p class="emPautaCount" style="background: #34495E; padding: 5px; color:white;">Em pauta: {{$emPautaCount }}</p>
                                            <p class="entregueCount" style="background: #0acf97; padding: 5px; color:white;">Entregues: {{$entregueCount}}</p>
                                        </div>
                                    </div>
                                    @if($emPautaCount > 0 ||$entregueCount > 0 || $atrasadoCount > 0)
                                    <div id="changeChart">
                                        <div id="loadingIndicator">
                                            <div class="d-flex justify-content-center" >
                                                <div class="spinner-border" style="position: absolute; top:50%; z-index: 99;" role="status">
                                                    <span class="sr-only">Carregando...</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="pie-chart"></div>
                                        <div id="pie-chart-labels" class="text-center"></div>
                                    </div>
                                    @else
                                    <p class="text-center">Você não possui nenhuma estatística disponível.</p>
                                    @endif
                                </div>
                            </div>
                        </div>
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
    <script src="{{ asset('assets/js/daterangepicker.js') }}" ></script>
    <script src="{{ asset('assets/js/helpers/ajaxUrl.js') }}" ></script>
    <script src="{{ asset('assets/js/helpers/ordemColunas.js') }}" ></script>
    <script>

        var table = $("#myTable");
        var defaultOrder = Array.from(Array(table.find("thead th").length).keys());

        // Carregar a ordem das colunas do localStorage ou usar a ordem padrão
        var columnOrder = @json($arrayOrdem);
        if (!columnOrder) {
            columnOrder = defaultOrder;
        } else {
            columnOrder = @json($arrayOrdem);
        }

        // Atualizar a ordem inicial das colunas
        updateColumnOrder(columnOrder);
        updateModalColumnOrder(columnOrder);

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

            $('#pagination').on('change', function() {
                var urlAtual = window.location.href;
                updatePaginationParams(urlAtual, true);
            });

            let emPautaCount = @json($emPautaCount);
            let entregueCount = @json($entregueCount);
            let atrasadoCount = @json($atrasadoCount);

            // Calcular porcentagens
            var total = emPautaCount + entregueCount + atrasadoCount;
            var emPautaPercent = ((emPautaCount / total) * 100).toFixed(2);
            var entreguePercent = ((entregueCount / total) * 100).toFixed(2);
            var atrasadoPercent = ((atrasadoCount / total) * 100).toFixed(2);

            Morris.Donut({
            element: 'pie-chart',
            data: [
                { label: `EM PAUTA`, value: emPautaPercent },
                { label: `ENTREGUES`, value: entreguePercent },
                { label: `EM ATRASO`, value: atrasadoPercent }
            ],
            formatter: function (y, data) {
                return data.value + '%';
            },
            colors: ['#34495E', '#0acf97', '#e6edf3'],
            });

        });
    </script>
@endsection


