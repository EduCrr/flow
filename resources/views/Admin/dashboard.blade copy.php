@extends('layouts.admin')
@section('title', 'Apresentações')

@section('css')

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css">
<link rel="stylesheet" href="https://kenwheeler.github.io/slick/slick/slick-theme.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">
@endsection
@section('content')
    <section>
        <div class="main-content">
            <div class="page-content">
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
                                                    <a class="nav-link active" id="entregas-hoje--tab"  data-toggle="tab" href="#entregas-hoje-{{$marca->id}}" role="tab" aria-controls="entregas-hoje-{{$marca->id}}" aria-selected="true">Entregas para hoje ({{$marca->entregaHojeCount}})</a>
                                                </li>
                                                <li class="nav-item">
                                                    <a class="nav-link" id="atrasadas-tab" data-toggle="tab" href="#atrasadas-{{$marca->id}}" role="tab" aria-controls="atrasadas-{{$marca->id}}" aria-selected="false">Atrasados ({{$marca->atrasadasCount}})</a>
                                                </li>

                                                <li class="nav-item">
                                                    <a class="nav-link" id="entregas-mes--tab" data-toggle="tab" href="#entregas-mes-{{$marca->id}}" role="tab" aria-controls="entregas-mes-{{$marca->id}}" aria-selected="false">Entregas para o mês vigente ({{$marca->entregaMesAtualCount}})</a>
                                                </li>
                                            </ul>
                                            <div class="tab-content">

                                                <div class="tab-pane fade show active" id="entregas-hoje-{{$marca->id}}" role="tabpanel" aria-labelledby="entregas-hoje--tab">
                                                    @if($marca->entregaHojeCount == 0)
                                                        {!! '<p class="textAlert" style="text-align: left; margin-bottom: 1.5rem; font-weight: bold;">Nenhum job com a entrega para hoje foi encontrado!</p>' !!}
                                                    @else
                                                    <table id="table-hj-{{$marca->id}}" class="table table-hover table-centered table-nowrap mb-0 showTableJobs ">
                                                        <thead>
                                                            <tr>
                                                                <th data-column-index="0">
                                                                    <div data-name="job">Job</div>
                                                                </th>
                                                                <th data-column-index="1">
                                                                    <div data-name="prioridade">Prioridade</div>
                                                                </th>
                                                                <th data-column-index="2">
                                                                    <div data-name="titulo">Título</div>
                                                                </th>
                                                                <th data-column-index="3">
                                                                    <div data-name="status">Status</div>
                                                                </th>
                                                                <th data-column-index="4">
                                                                    <div data-name="inicial">Prazo inicial</div>
                                                                </th>
                                                                <th data-column-index="5">
                                                                    <div data-name="entrega">Prazo de entrega</div>
                                                                </th>
                                                                <th data-column-index="6">
                                                                    <div data-name="criador">Criador</div>
                                                                </th>
                                                                <th data-column-index="7">
                                                                    <div data-name="marca">Marca(s)</div>
                                                                </th>
                                                                <th data-column-index="8">
                                                                    <div>Progresso</div>
                                                                </th>
                                                                <th data-column-index="9">
                                                                    <div data-name="agencia">Agencia</div>
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
                                                    {!! '<p class="textAlert" style="text-align: left; margin-bottom: 1.5rem; font-weight: bold;">Nenhum job foi atrasado foi encontrado!</p>' !!}
                                                    @else
                                                    <table id="table-at-{{$marca->id}}" class="table table-hover table-centered table-nowrap mb-0 showTableJobs ">
                                                        <thead>
                                                            <tr>
                                                                <th data-column-index="0">
                                                                    <div data-name="job">Job</div>
                                                                </th>
                                                                <th data-column-index="1">
                                                                    <div data-name="prioridade">Prioridade</div>
                                                                </th>
                                                                <th data-column-index="2">
                                                                    <div data-name="titulo">Título</div>
                                                                </th>
                                                                <th data-column-index="3">
                                                                    <div data-name="status">Status</div>
                                                                </th>
                                                                <th data-column-index="4">
                                                                    <div data-name="inicial">Prazo inicial</div>
                                                                </th>
                                                                <th data-column-index="5">
                                                                    <div data-name="entrega">Prazo de entrega</div>
                                                                </th>
                                                                <th data-column-index="6">
                                                                    <div data-name="criador">Criador</div>
                                                                </th>
                                                                <th data-column-index="7">
                                                                    <div data-name="marca">Marca(s)</div>
                                                                </th>
                                                                <th data-column-index="8">
                                                                    <div>Progresso</div>
                                                                </th>
                                                                <th data-column-index="9">
                                                                    <div data-name="agencia">Agencia</div>
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
                                                    {!! '<p class="textAlert" style="text-align: left; margin-bottom: 1.5rem; font-weight: bold;">Nenhum job com a entrega para esse mês foi encontrado!</p>' !!}
                                                    @else
                                                    <table id="table-mes-{{$marca->id}}" class="table table-hover table-centered table-nowrap mb-0 showTableJobs ">
                                                        <thead>
                                                            <tr>
                                                                <th data-column-index="0">
                                                                    <div data-name="job">Job</div>
                                                                </th>
                                                                <th data-column-index="1">
                                                                    <div data-name="prioridade">Prioridade</div>
                                                                </th>
                                                                <th data-column-index="2">
                                                                    <div data-name="titulo">Título</div>
                                                                </th>
                                                                <th data-column-index="3">
                                                                    <div data-name="status">Status</div>
                                                                </th>
                                                                <th data-column-index="4">
                                                                    <div data-name="inicial">Prazo inicial</div>
                                                                </th>
                                                                <th data-column-index="5">
                                                                    <div data-name="entrega">Prazo de entrega</div>
                                                                </th>
                                                                <th data-column-index="6">
                                                                    <div data-name="criador">Criador</div>
                                                                </th>
                                                                <th data-column-index="7">
                                                                    <div data-name="marca">Marca(s)</div>
                                                                </th>
                                                                <th data-column-index="8">
                                                                    <div>Progresso</div>
                                                                </th>
                                                                <th data-column-index="9">
                                                                    <div data-name="agencia">Agencia</div>
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
                                                <h5 class="card-title mb-3">{{$mes}} {{$ano}}
                                                </h5>
                                                <div class="textPie">
                                                    <p class="at-{{$marca->id}}" style="padding: 5px; color:white;">Em atraso: {{ $marca->atrasadasMesCount }}</p>
                                                    <p style="background-color:{{$marca->cor}}; padding: 5px; color:white;">Em pauta: {{$marca->emPautaMesCount}}</p>
                                                    <p class="en-{{$marca->id}}" style="padding: 5px; color:white;">Entregues: {{$marca->entreguesMesCount}}</p>
                                                </div>
                                            </div>
                                            <div id="changeChart">
                                                <div id="loadingIndicator">
                                                    <div class="d-flex justify-content-center" style="">
                                                        <div class="spinner-border" style="position: absolute; top:50%; z-index: 99;" role="status">
                                                            <span class="sr-only">Carregando...</span>
                                                        </div>
                                                    </div>
                                                <div id="pie-chart2-mes-{{$marca->id}}" style="width: 100%; height: 100%; position: absolute; top: 50%; left: 50%;   transform: translate(-50%, -50%);"></div>
                                                </div>
                                                <div id="changeChart2">
                                                    <div class="pie-chart" id="pie-chart-mes-{{$marca->id}}"></div>
                                                    <div id="pie-chart-labels" class="text-center"></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-xl-3">
                                    <div class="card cardDonult">
                                        <div class="card-body">
                                            <div class="initialEst">
                                                <h5 class="card-title mb-3">{{$ano}}
                                                </h5>
                                                <div class="textPie">
                                                    <p class="at-{{$marca->id}}" style="padding: 5px; color:white;">Em atraso: {{ $marca->atrasadasGeralCount }} </p>
                                                    <p style="background-color: {{$marca->cor}}; padding: 5px; color:white;">Em pauta: {{$marca->emPautaGeralCount}}</p>
                                                    <p class="en-{{$marca->id}}" style="padding: 5px; color:white;">Entregues: {{$marca->entreguesGeralCount}}</p>
                                                </div>
                                            </div>
                                            <div id="changeChart">
                                                <div id="loadingIndicator">
                                                    <div class="d-flex justify-content-center" style="">
                                                        <div class="spinner-border" style="position: absolute; top:50%; z-index: 99;" role="status">
                                                            <span class="sr-only">Carregando...</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div id="changeChart2">
                                                    <div class="pie-chart" id="pie-chart-{{$marca->id}}"></div>
                                                    <div id="pie-chart-labels" class="text-center"></div>
                                                </div>
                                                <div id="pie-chart2-{{$marca->id}}" style="width: 100%; height: 100%; position: absolute; top: 50%; left: 50%;   transform: translate(-50%, -50%);"></div>
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
                                                <div id="loadingIndicator">
                                                    <div class="d-flex justify-content-center" style="">
                                                        <div class="spinner-border" style="position: absolute; top:50%; left: 50%; z-index: 99;" role="status">
                                                            <span class="sr-only">Carregando...</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <canvas id="graph_bar_{{$marca->id}}"  width="100%" height="35"></canvas>
                                                {{-- <canvas id="mixedChart" width="100%" height="35"></canvas> --}}
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.4.1/js/bootstrap.min.js"></script>

    <script src='https://www.amcharts.com/lib/3/amcharts.js'></script>
    <script src='https://www.amcharts.com/lib/3/pie.js'></script>
    <script src='https://www.amcharts.com/lib/3/themes/light.js'></script>
    <script src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.0/Chart.min.js'></script>
<script>


// var ctx = document.getElementById("mixedChart");
// var myChart = new Chart(ctx, {
//     type: 'bar',
//     data: {
//         datasets: [
//           {
//             label: 'Goal',
//             data: [1250, 1350, 1300, 1700, 1900, 2700, 2150],
//             type: 'line',
//             backgroundColor: '#23B7E5'
//           },
//           {
//             label: 'Sales',
//             data: [1200, 1300, 1277, 1690, 1898, 2740, 2263],
//           },
//         ],
//         labels: [ 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
//     },
//     options: {}
// });

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

        let meses = ["Jan", "Fev", "Mar", "Abr", "Mai", "Jun", "Jul", "Ago", "Set", "Out", "Nov", "Dez"];

        let datasets = [{
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
        }, {
            label: "Demandas Criadas (Linha)",
            type: "line",
            borderColor: marca.cor,
            data: resultadosDemanda.map(entry => entry.criadas),
            fill: false
        }, {
            label: "Demandas Finalizadas (Linha)",
            type: "line",
            borderColor: makeColorLighter(marca.cor, 60),
            data: resultadosDemanda.map(entry => entry.finalizadas),
            fill: false
        }];

        new Chart(document.getElementById(`graph_bar_${marca.id}`), {
            type: 'bar',
            data: {
                labels: meses,
                datasets: datasets
            },
            options: {
                title: {
                    display: false,
                },
                legend: {
                    display: true,
                    position: 'top'
                },
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                },
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
                    "value": emPautaPercent,
                    "color": originalColor
                },

                {
                    "label": `Entregues (${entregueCount})` ,
                    "value": entreguePercent,
                    "color": lighterColor
                },

                {
                    "label": `Em atraso (${atrasadoCount})` ,
                    "value": atrasadoPercent,
                    "color": darkerColor
                },

            ] };


            AmCharts.makeChart(`pie-chart2-${marca.id}`, {
            "type": "pie",
            "balloonText": "[[title]]<br><span style='font-size:12px'><b>[[value]]</b> ([[percents]]%)</span>",
            "innerRadius": "80%",
            "titleField": "label",
            "valueField": "value",
            "theme": "light",
            "colorField": "color",
            "allLabels": [],
            "balloon": {},
            "legend": {
                "enabled": true,
                "align": "center",
                "markerType": "circle",
                "valueText": "[[count]][[percents]]%",
            },
            "startDuration": 0,
            "titles": mockData.titles,
            "dataProvider": mockData.data
            });

        }

        if (emPautaCountMes == 0 && entregueCountMes == 0 && atrasadoCount == 0) {
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
                    "label": `Em pauta (${emPautaCount})` ,
                    "value": emPautaPercent,
                    "color": originalColor
                },

                {
                    "label": `Entregues (${entregueCount})` ,
                    "value": entreguePercent,
                    "color": lighterColor
                },

                {
                    "label": `Em atraso (${atrasadoCount})` ,
                    "value": atrasadoPercent,
                    "color": darkerColor
                },

            ] };


            AmCharts.makeChart(`pie-chart2-mes-${marca.id}`, {
            "type": "pie",
            "balloonText": "[[title]]<br><span style='font-size:12px'><b>[[value]]</b> ([[percents]]%)</span>",
            "innerRadius": "80%",
            "titleField": "label",
            "valueField": "value",
            "theme": "light",
            "colorField": "color",
            "allLabels": [],
            "balloon": {},
            "legend": {
                "enabled": true,
                "align": "center",
                "markerType": "circle",
                "valueText": "[[count]][[percents]]%",
            },
            "startDuration": 0,
            "titles": mockDataMes.titles,
            "dataProvider": mockDataMes.data
            });

        }

    });

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

    new Chart(document.getElementById("mixed-chart"), {
  type: 'bar',
  data: {
    labels: ["1900", "1950", "1999", "2050"],
    datasets: [{
      label: "Europe",
      type: "line",
      borderColor: "#8e5ea2",
      data: [408, 547, 675, 734],
      fill: false },
    {
      label: "Africa",
      type: "line",
      borderColor: "#3e95cd",
      data: [133, 221, 783, 500],
      fill: false },
    {
      label: "Europe",
      type: "bar",
      backgroundColor: "rgba(0,0,0,0.2)",
      data: [408, 547, 675, 734] },
    {
      label: "Africa",
      type: "bar",
      backgroundColor: "rgba(0,0,0,0.2)",
      backgroundColorHover: "#3e95cd",
      data: [133, 221, 783, 500] }] },



  options: {
    title: {
      display: true,
    },

    legend: { display: false } } });




</script>
@endsection
