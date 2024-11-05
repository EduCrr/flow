@extends('layouts.admin')
@section('title', 'Meus jobs')

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
                                            <a href="{{route('Admin.jobs')}}" class="text-primary btnHome">Ver todos <i class="mdi mdi-arrow-right"></i></a>
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
                                                            <li class="list-group-item" data-column-index="1">Prioridade</li>
                                                            <li class="list-group-item" data-column-index="2">Título</li>
                                                            <li class="list-group-item" data-column-index="3">Status</li>
                                                            <li class="list-group-item" data-column-index="4">Prazo inicial</li>
                                                            <li class="list-group-item" data-column-index="5">Prazo de entrega</li>
                                                            <li class="list-group-item" data-column-index="6">Criador</li>
                                                            <li class="list-group-item" data-column-index="7">Marca(s)</li>
                                                            <li class="list-group-item" data-column-index="8">Progresso</li>
                                                            <li class="list-group-item" data-column-index="9">Agencia</li>
                                                            <li class="list-group-item" style="display: none" data-column-index="10"></li>
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
                                                @component('components.TabelaAdminComponent', ['demandas' => $demandas, 'arrayOrdem' => $arrayOrdem, 'sortableEnabled' => false, 'ordem' => $ordem])@endcomponent
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row rowEdit">
                        <div class="col-xl-6">
                            <div class="card">
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
                                    <div class="sectionChart">
                                        <select class="form-select select2 form-select-ajax" name="usuario">
                                            <option value="">Usuário</option>
                                            @foreach($users as $u)
                                                <option value="{{$u->id}}">{{$u->nome}}</option>
                                            @endforeach
                                        </select>
                                        <input id="dataGrafico" class="form-control filter-daterangepicker form-select-ajax" placeholder="Intervalo de datas" type="text" name="dataGrafico" value="null">
                                    </div>
                                    <div id="changeChart">
                                        <div id="loadingIndicator">
                                            <div class="d-flex justify-content-center" style="">
                                                <div class="spinner-border" style="position: absolute; top:50%; z-index: 99;" role="status">
                                                    <span class="sr-only">Carregando...</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="pie-chart"></div>
                                        <div id="pie-chart-labels" class="text-center"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-body adjustCardBody">
                                    <div>
                                        <span class="float-end text-muted font-size-13"> <script>document.write(new Date().getFullYear())</script></span>
                                        <h5 class="card-title mb-3">Jobs</h5>
                                    </div>
                                    <div class="col-sm-12 text-center adjustChart">
                                        <div id="loadingIndicator">
                                            <div class="d-flex justify-content-center" style="">
                                                <div class="spinner-border" style="position: absolute; top:50%; left: 50%; z-index: 99;" role="status">
                                                    <span class="sr-only">Carregando...</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="chart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card mb-0">
                                <div class="card-body">
                                    <h5 class="card-title mb-4">Meus jobs em pauta</h5>
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
    <script src="{{ asset('assets/js/select2.js') }}" ></script>
    <script src="{{ asset('assets/js/jqueryui.js') }}" ></script>
    <script src="{{ asset('assets/js/moment.js') }}" ></script>
    <script src="{{ asset('assets/js/fullcalendar.js') }}" ></script>
    <script src="{{ asset('assets/js/daterangepicker.js') }}" ></script>
    <script src="{{ asset('assets/js/momentlocale.js') }}" ></script>
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


    //jeito 1
    // let dataBar = [];

    // let teste = @json($jobsPerMonths);

    // for(let i = 0; i < teste.length; i++){
    //     dataBar.push({
    //         month: `${teste[i].month}`,
    //         jobs: `${teste[i].jobs}`,
    //     });
    // }



    //jeito 2

    // let chart = new Morris.Bar({
    //     element: 'chart',

    //     data: [
    //         @foreach ($jobsPerMonths as $job)
    //             { month: '{{ $job['month'] }}', jobs: {{ $job['jobs'] }} },
    //         @endforeach
    //     ],
    //     xkey: 'month',
    //     ykeys: ['jobs'],
    //     labels: ['Jobs Criados'],
    //     hideHover: 'auto',
    //     barColors: ['#0acf97'],
    //     resize: true
    // });

    Morris.Bar({
        element: 'chart',
        data: <?php echo json_encode($resultadosDemanda); ?>,
        xkey: 'mes',
        ykeys: ['criadas', 'finalizadas'],
        labels: ['Criadas', 'Finalizadas'],
        hideHover: 'auto',
        resize: true,
        barColors: ['#34495E', '#0acf97'],
    });


    let emPautaCount = @json($emPautaCount);
    let entregueCount = @json($entregueCount);
    let atrasadoCount = @json($atrasadoCount);



    // Calcular porcentagens
    var total = emPautaCount + entregueCount + atrasadoCount;
    var emPautaPercent = ((emPautaCount / total) * 100).toFixed(2);
    var entreguePercent = ((entregueCount / total) * 100).toFixed(2);
    var atrasadoPercent = ((atrasadoCount / total) * 100).toFixed(2);

    if(total > 0){
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
    }else{
        $('#pie-chart').html('<p>Essa consulta não possui nenhum dado!</p>');
    }


    const monthNames = ["","Jan", "Fev", "Mar", "Abr", "Mai", "Jun",
        "Jul", "Ago", "Set", "Out", "Nov", "Dez"
    ];


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

    $(document).ready(function() {
        $(".filter-daterangepicker").val('');

        $('#pagination').on('change', function() {
            updatePaginationParams("/admin/ordem", true);
        });

        $('.form-select-ajax').on('change', function() {
            var usuario = $('select[name="usuario"]').val();
            dataGrafico = $(".filter-daterangepicker").val();
            function atualizarGrafico() {
                $.ajax({
                    url: '/flow/atualizar-grafico',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        dataGrafico: dataGrafico,
                        usuario: usuario
                    },

                    success: function(data) {
                        var total = data['emPautaCount'] + data['entregueCount'] +  data['atrasadoCount'];
                        var emPautaPercent = ((data['emPautaCount'] / total) * 100).toFixed(2);
                        var entreguePercent = ((data['entregueCount'] / total) * 100).toFixed(2);
                        var atrasadoPercent = ((data['atrasadoCount'] / total) * 100).toFixed(2);
                        $('.entregueCount').text('Entregues: '+data['entregueCount'])
                        $('.emPautaCount').text('Em pauta: '+data['emPautaCount'])
                        $('.atrasadoCount').text('Em atraso: '+data['atrasadoCount'])

                        $('#pie-chart').empty();

                        if(data['emPautaCount'] == 0 && data['entregueCount'] == 0 && data['atrasadoCount'] == 0 ){
                            $('#pie-chart').html('<p>Essa consulta não possui nenhum dado!</p>');
                        }else{
                            Morris.Donut({
                            element: 'pie-chart',
                            data: [
                                { label: 'EM PAUTA', value: emPautaPercent },
                                { label: 'ENTREGUES', value: entreguePercent },
                                { label: 'EM ATRASO', value: atrasadoPercent }
                            ],
                            formatter: function (y, data) {
                                return data.value + '%';
                            },
                            colors: ['#34495E', '#0acf97', '#e6edf3'],
                        });
                        }


                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
            }
            atualizarGrafico();
        });

    });

</script>

@endsection

