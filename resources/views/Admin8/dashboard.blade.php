@extends('layouts.admin8')
@section('title', 'Dashboard')

@section('css')


@endsection
@section('content')
    <section>
        <div class="main-content">
            <div class="page-content">
                <div class="showContent">
                    <h5>Conteúdo apenas disponível para desktop.</h4>
                </div>
                <div class="dashboardMarcas">
                    @forEach($marcas as $key => $marca)
                        <div class="container-fluid">
                            <div class="row">
                                <div class="col-xl-12">
                                    <div class="card card-adjust">
                                        <div class="card-body">
                                            <h3 style="margin-bottom: 0px">{{$marca->nome}}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row rowEdit rowMarcas adjustRowTable">
                                <div class="col-xl-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <ul class="nav nav-tabs" id="myTab" role="tablist">
                                                <li class="nav-item">
                                                    <a class="nav-link active" id="entregas-hoje--tab" style=" font-size: 12px; color: {{$marca->cor}}"  data-toggle="tab" href="#entregas-hoje-{{$marca->id}}" role="tab" aria-controls="entregas-hoje-{{$marca->id}}" aria-selected="true">Entregas para hoje ({{$marca->entregaHojeCount}})</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" id="atrasadas-tab" style=" font-size: 12px; color: {{$marca->cor}}" data-toggle="tab" href="#atrasadas-{{$marca->id}}" role="tab" aria-controls="atrasadas-{{$marca->id}}" aria-selected="false">Atrasados ({{$marca->atrasadasCount}})</a>
                                                </li>

                                                <li class="nav-item">
                                                    <a class="nav-link" id="entregas-mes--tab" style=" font-size: 12px; color: {{$marca->cor}}" data-toggle="tab" href="#entregas-mes-{{$marca->id}}" role="tab" aria-controls="entregas-mes-{{$marca->id}}" aria-selected="false">Entregas para o mês vigente ({{$marca->entregaMesAtualCount}})</a>
                                                </li>
                                            </ul>
                                            <div class="tab-content">

                                                <div class="tab-pane fade show active" id="entregas-hoje-{{$marca->id}}" role="tabpanel" aria-labelledby="entregas-hoje--tab">
                                                    @if($marca->entregaHojeCount == 0)
                                                        {!! '<p class="textAlert" style="text-align: left; margin-bottom: 1.5rem; font-weight: bold;">Nenhum job com entrega para hoje foi encontrado.</p>' !!}
                                                    @else
                                                    <table id="table-hj-{{$marca->id}}" class="table table-hover table-centered table-nowrap mb-0 showTableJobs ">
                                                        <thead>
                                                            <tr>
                                                                <th>
                                                                    <div>Job</div>
                                                                </th>
                                                                <th>
                                                                    <div>Prioridade</div>
                                                                </th>
                                                                <th>
                                                                    <div>Título</div>
                                                                </th>
                                                                <th>
                                                                    <div>Status</div>
                                                                </th>
                                                                <th>
                                                                    <div>Prazo inicial</div>
                                                                </th>
                                                                <th>
                                                                    <div>Prazo de entrega</div>
                                                                </th>
                                                                <th>
                                                                    <div>Criador</div>
                                                                </th>
                                                                <th>
                                                                    <div>Progresso</div>
                                                                </th>
                                                                <th>
                                                                    <div>Agencia</div>
                                                                </th>
                                                            </tr>
                                                        </thead>
                                                        @if($marca['entregaHoje'])
                                                            @foreach ($marca['entregaHoje'] as $demanda)
                                                                @component('components.TabelaDashboardComponent', ['demanda' => $demanda])@endcomponent
                                                            @endforeach
                                                        @endif
                                                    </table>
                                                    @endif
                                                </div>
                                                <div class="tab-pane fade " id="atrasadas-{{$marca->id}}" role="tabpanel" aria-labelledby="atrasadas-tab">
                                                    @if($marca->atrasadasCount == 0)
                                                    {!! '<p class="textAlert" style="text-align: left; margin-bottom: 1.5rem; font-weight: bold;">Nenhum job atrasado foi encontrado.</p>' !!}
                                                    @else
                                                    <table id="table-at-{{$marca->id}}" class="table table-hover table-centered table-nowrap mb-0 showTableJobs ">
                                                        <thead>
                                                            <tr>
                                                                <th>
                                                                    <div>Job</div>
                                                                </th>
                                                                <th>
                                                                    <div>Prioridade</div>
                                                                </th>
                                                                <th>
                                                                    <div>Título</div>
                                                                </th>
                                                                <th>
                                                                    <div>Status</div>
                                                                </th>
                                                                <th>
                                                                    <div>Prazo inicial</div>
                                                                </th>
                                                                <th>
                                                                    <div>Prazo de entrega</div>
                                                                </th>
                                                                <th>
                                                                    <div>Criador</div>
                                                                </th>
                                                                <th>
                                                                    <div>Progresso</div>
                                                                </th>
                                                                <th>
                                                                    <div>Agencia</div>
                                                                </th>

                                                            </tr>
                                                        </thead>
                                                        @if($marca['atrasadas'])
                                                            @foreach ($marca['atrasadas'] as $demanda)
                                                                @component('components.TabelaDashboardComponent', ['demanda' => $demanda])@endcomponent
                                                            @endforeach
                                                        @endif
                                                    </table>
                                                    @endif
                                                </div>
                                                <div class="tab-pane fade" id="entregas-mes-{{$marca->id}}" role="tabpanel" aria-labelledby="entregas-mes--tab">
                                                    @if($marca->entregaMesAtualCount == 0)
                                                    {!! '<p class="textAlert" style="text-align: left; margin-bottom: 1.5rem; font-weight: bold;">Nenhum job com entrega para este mês foi encontrado.</p>' !!}
                                                    @else
                                                    <table id="table-mes-{{$marca->id}}" class="table table-hover table-centered table-nowrap mb-0 showTableJobs ">
                                                        <thead>
                                                            <tr>
                                                                <th>
                                                                    <div>Job</div>
                                                                </th>
                                                                <th>
                                                                    <div>Prioridade</div>
                                                                </th>
                                                                <th>
                                                                    <div>Título</div>
                                                                </th>
                                                                <th>
                                                                    <div>Status</div>
                                                                </th>
                                                                <th>
                                                                    <div>Prazo inicial</div>
                                                                </th>
                                                                <th>
                                                                    <div>Prazo de entrega</div>
                                                                </th>
                                                                <th>
                                                                    <div>Criador</div>
                                                                </th>
                                                                <th>
                                                                    <div>Progresso</div>
                                                                </th>
                                                                <th>
                                                                    <div>Agencia</div>
                                                                </th>

                                                            </tr>
                                                        </thead>
                                                        @if($marca['entregaMesAtual'])
                                                            @foreach ($marca['entregaMesAtual'] as $demanda)
                                                                @component('components.TabelaDashboardComponent', ['demanda' => $demanda])@endcomponent
                                                            @endforeach
                                                        @endif
                                                    </table>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row rowEdit rowMarcas">
                                <div class="col-xl-3">
                                    <div class="card cardDonult">
                                        <div class="card-body">
                                            <div class="initialEst">
                                                <h5 class="card-title">{{$mes}} {{$ano}}
                                                </h5>
                                                <div class="textPie">
                                                    <p class="at-{{$marca->id}}">Em atraso: {{ $marca->atrasadasMesCount }}</p>
                                                    <p style="background-color:{{$marca->cor}}; ">Em pauta: {{$marca->emPautaMesCount}}</p>
                                                    <p class="en-{{$marca->id}}">Entregues: {{$marca->entreguesMesCount}}</p>
                                                </div>
                                            </div>
                                            <div id="changeChart" style="overflow: auto;">
                                                <div id="changeChart2">
                                                    <div class="pie-chart" id="pie-chart-mes-{{$marca->id}}"></div>
                                                    <div id="pie-chart-labels" class="text-center"></div>
                                                </div>
                                                <div class="adjustPie" id="pie-chart2-mes-{{$marca->id}}" style=""></div>
                                                @if($marca->totalMes > 0)
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3">
                                    <div class="card cardDonult">
                                        <div class="card-body">
                                            <div class="initialEst">
                                                <h5 class="card-title">{{$ano}}
                                                </h5>
                                                <div class="textPie">
                                                    <p class="at-{{$marca->id}}">Em atraso: {{ $marca->atrasadasGeralCount }} </p>
                                                    <p style="background-color: {{$marca->cor}}; ">Em pauta: {{$marca->emPautaGeralCount}}</p>
                                                    <p class="en-{{$marca->id}}">Entregues: {{$marca->entreguesGeralCount}}</p>
                                                </div>
                                            </div>
                                            <div id="changeChart" style="overflow: auto;">
                                                <div id="changeChart2">
                                                    <div class="pie-chart" id="pie-chart-{{$marca->id}}"></div>
                                                    <div id="pie-chart-labels" class="text-center"></div>
                                                </div>
                                                <div class="adjustPie" id="pie-chart2-{{$marca->id}}" style=""></div>
                                                @if($marca->total > 0)
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-6">
                                    <div class="card">
                                        <div class="card-body adjustCardBody">
                                            <div class="initialGraphs">
                                                <h5 class="card-title">Jobs criados e finalizados</h5>
                                            </div>
                                            <div class="col-sm-12 text-center adjustChart">
                                                <canvas id="graph_bar_{{$marca->id}}"  width="100%" height="35"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection

@section('plugins')
@endsection


@section('scripts')
    <script src="{{ asset('assets/js/jqueryui.js') }}" ></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/popper.min.js"></script>
    <script src="{{ asset('assets/js/twitter-bootstrap.js') }}" ></script>
    <script src="{{ asset('assets/js/amcharts.js') }}" ></script>
    <script src="{{ asset('assets/js/pie.js') }}" ></script>
    <script src="{{ asset('assets/js/amcharts-theme.js') }}" ></script>
    <script src="{{ asset('assets/js/chart.js') }}" ></script>
    <script src="{{ asset('assets/js/chartjs-plugin.js') }}" ></script>
<script>

    let marcas = @json($marcas);

    function makeColorLighter(color, factor) {
        var rgb = hexToRgb(color);

        var newR = Math.min(255, rgb.r + factor);
        var newG = Math.min(255, rgb.g + factor);
        var newB = Math.min(255, rgb.b + factor);

        var newColor = rgbToHex(newR, newG, newB);

        return newColor;
    }

    function makeColorDarker(color, factor) {
        var rgb = hexToRgb(color);

        var newR = Math.max(0, rgb.r - factor);
        var newG = Math.max(0, rgb.g - factor);
        var newB = Math.max(0, rgb.b - factor);

        var newColor = rgbToHex(newR, newG, newB);

        return newColor;
    }

    function hexToRgb(hex) {
        var bigint = parseInt(hex.slice(1), 16);
        var r = (bigint >> 16) & 255;
        var g = (bigint >> 8) & 255;
        var b = bigint & 255;
        return { r: r, g: g, b: b };
    }

    function rgbToHex(r, g, b) {
        return "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
    }

    marcas.forEach(function(marca, key) {
        let resultadosDemanda = marcas[key].resultadosDemanda;

        // Morris.Bar({
        //     element: `graph_bar_${marca.id}`,
        //     data: resultadosDemanda,
        //     xkey: 'mes',
        //     ykeys: ['criadas', 'finalizadas'],
        //     labels: ['Criadas', 'Finalizadas'],
        //     hideHover: 'auto',
        //     resize: true,
        //     barColors: [marca.cor, makeColorLighter(marca.cor, 60)],
        // });

        var ctx = document.getElementById(`graph_bar_${marca.id}`);
        var myChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"],
                datasets: [{
                    label: "Demandas Criadas",
                    type: "bar",
                    backgroundColor: marca.cor,
                    data: resultadosDemanda.map(entry => entry.criadas)
                }, {
                    label: "Demandas Finalizadas",
                    type: "bar",
                    backgroundColor: makeColorLighter(marca.cor, 60),
                    backgroundColorHover: makeColorLighter(marca.cor, 60),
                    data: resultadosDemanda.map(entry => entry.finalizadas)
                },]
            },
            options: {
                title: {
                    display: false,
                },
                legend: {
                    display: true,
                    position: 'bottom',
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true,

                        }
                    }]
                },
                layout: {
                    padding: {
                        top: 20,
                    }
                },
                plugins: {
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        offset: 0,
                        display: function(context) {
                            return context.dataset.data[context.dataIndex] !== 0;
                        },
                        formatter: function(value, context) {
                            return value;
                        }
                    }
                }
            }
        });
    });

    marcas.forEach(function(marca) {

        let emPautaCount = marca.emPautaGeralCount;
        let entregueCount = marca.entreguesGeralCount;
        let atrasadoCount = marca.atrasadasGeralCount;

        let emPautaCountMes = marca.emPautaMesCount;
        let entregueCountMes = marca.entreguesMesCount;
        let atrasadoCountMes = marca.atrasadasMesCount;

        var originalColor = marca.cor;
        var lighterColor = makeColorLighter(originalColor, 60);
        var darkerColor = makeColorDarker(originalColor, 90);

        $(`p.at-${marca.id}`).css("background-color", darkerColor);
        $(`p.en-${marca.id}`).css("background-color", lighterColor);

        // Verificar se há estatísticas para a marca
        if (emPautaCount == 0 && entregueCount == 0 && atrasadoCount == 0) {
            $(`#pie-chart-${marca.id}`).html('<div style="width: 100% !important; text-align: center;"><p>Não há dados para essas estatísticas!</p></div>');
        } else {
            // Calcular porcentagens
            var total = emPautaCount + entregueCount + atrasadoCount;
            var emPautaPercent = ((emPautaCount / total) * 100).toFixed(2);
            var entreguePercent = ((entregueCount / total) * 100).toFixed(2);
            var atrasadoPercent = ((atrasadoCount / total) * 100).toFixed(2);

            var mockData = {
                data: [
                {
                    "label": `Em pauta (${emPautaCount})` ,
                    "text": `Em pauta` ,
                    "value": emPautaPercent,
                    "color": originalColor
                },

                {
                    "label": `Entregues (${entregueCount})` ,
                    "text": `Entregues` ,
                    "value": entreguePercent,
                    "color": lighterColor
                },

                {
                    "label": `Em atraso (${atrasadoCount})` ,
                    "text": `Em atraso` ,
                    "value": atrasadoPercent,
                    "color": darkerColor
                },

            ] };


            AmCharts.makeChart(`pie-chart2-${marca.id}`, {
            "type": "pie",
            "balloonText": "[[title]]<br><span style='font-size:12px'><b>[[value]]</b>%</span>",
            "innerRadius": "90%",
            "titleField": "text",
            "valueField": "value",
            "theme": "light",
            "colorField": "color",
            "allLabels": [],
            "balloon": {},
            "startDuration": 2,
            "startEffect": "elastic",
            "titles": mockData.titles,
            "dataProvider": mockData.data,
            "allLabels": [{
                    "y": "48%",
                    "align": "center",
                    "size": 11,
                    "bold": true,
                    "text": `Total de jobs: ${total}`,
                    "color": '#000000'
                }, {
                    "y": "52%",
                    "align": "center",
                    "size": 11,
                    "text": '',
                    "color": '#000000'
                }]
            });

        }

        if (emPautaCountMes == 0 && entregueCountMes == 0 && atrasadoCountMes == 0) {
            $(`#pie-chart-mes-${marca.id}`).html('<div style="width: 100% !important; text-align: center;"><p>Não há dados para essas estatísticas!</p></div>');
        } else {
            // Calcular porcentagens
            var total = emPautaCountMes + entregueCountMes + atrasadoCountMes;
            var emPautaPercent = ((emPautaCountMes / total) * 100).toFixed(2);
            var entreguePercent = ((entregueCountMes / total) * 100).toFixed(2);
            var atrasadoPercent = ((atrasadoCountMes / total) * 100).toFixed(2);

            var mockDataMes = {
                data: [
                {
                    "label": `Em pauta (${emPautaCountMes})` ,
                    "text": `Em pauta` ,
                    "value": emPautaPercent,
                    "color": originalColor
                },

                {
                    "label": `Entregues (${entregueCountMes})` ,
                    "text": `Entregues` ,
                    "value": entreguePercent,
                    "color": lighterColor
                },

                {
                    "label": `Em atraso (${atrasadoCountMes})` ,
                    "text": `Em atraso` ,
                    "value": atrasadoPercent,
                    "color": darkerColor
                },

            ] };


            AmCharts.makeChart(`pie-chart2-mes-${marca.id}`, {
            "type": "pie",
            "balloonText": "[[title]]<br><span style='font-size:12px'><b>[[value]]</b>%</span>",
            "innerRadius": "90%",
            "titleField": "text",
            "valueField": "value",
            "theme": "light",
            "colorField": "color",
            "allLabels": [],
            "balloon": {},
            "startDuration": 2,
            "diameter": 500,
            "startEffect": "elastic",
            "titles": mockDataMes.titles,
            "dataProvider": mockDataMes.data,
            "allLabels": [{
                    "y": "48%",
                    "align": "center",
                    "size": 11,
                    "bold": true,
                    "text": `Total de jobs: ${total}`,
                    "color": '#000000'
                }, {
                    "y": "52%",
                    "align": "center",
                    "size": 11,
                    "text": '',
                    "color": '#000000'
                }]
            });

        }

    });

    // var slickInstance = $('.dashboardMarcas').slick('getSlick');

    // var totalSlides = slickInstance.slideCount;

    // function atualizarPagina() {
    //     location.reload(true);
    // }

    // setTimeout(atualizarPagina, totalSlides * 300000);

    $(document).ready(function() {
        $('.select2').select2({
            minimumResultsForSearch: Infinity
        });

        $('.nav-link').on('click', function() {
            var tabId = $(this).attr('id');

            $('.textAlert').css('display', 'block');
            $('.tab-pane:not([aria-labelledby="' + tabId + '"]) .textAlert').css('display', 'none');

        });



    });


</script>
@endsection
