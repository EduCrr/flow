@extends('layouts.admin8')
@section('title', 'Agência '. $agencia->nome )

@section('css')
@endsection

@section('content')

    <section>
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">{{$agencia->nome}}</h5>
                                    <br/>
                                    <p class="mt-1">Média geral de dias para entrega das pautas: <strong>@if ($media >= 2)   {{$media }} dias  @else     {{$media}} dia    @endif</strong></p>
                                    <p class="mt-1">Jobs finalizados: <strong>{{$demandasCount}}</strong></p>
                                    <p class="mt-1">Jobs entregues em atraso: <strong>{{$demandasAtrasadasCount}}</strong></p>
                                    <p class="mt-1">Jobs entregues dentro do prazo: <strong>{{$demandasEmPrazoCount}}</strong></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="initialGraphs">
                                        <h5 class="card-title">Média de dias para a entrega das pautas</h5>
                                        <a href="{{ route('admin.export', ['id' => $agencia->id]) }}" class="btn btn-success">Exportar para Excel</a>
                                    </div>
                                    <div id="loadingIndicator">
                                        <div class="d-flex justify-content-center" style="">
                                            <div class="spinner-border" style="position: absolute; top:50%; z-index: 99;" role="status">
                                                <span class="sr-only">Carregando...</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="chart"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card">
                                <div class="card-body">
                                    <div class="initialGraphs">
                                        <h5 class="card-title">Jobs criados e finalizados</h5>
                                        <a href="{{ route('admin.export.jobs', ['id' => $agencia->id]) }}" class="btn btn-success">Exportar para Excel</a>
                                    </div>
                                    <div id="loadingIndicator">
                                        <div class="d-flex justify-content-center" style="">
                                            <div class="spinner-border" style="position: absolute; top:50%; z-index: 99;" role="status">
                                                <span class="sr-only">Carregando...</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="graph_bar"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="initialGraphs">
                                        <h5 class="card-title">Jobs entregues com atraso e entregues dentro do prazo.</h5>
                                        <a href="{{ route('admin.export.prazos', ['id' => $agencia->id]) }}" class="btn btn-success">Exportar para Excel</a>
                                    </div>
                                    <div id="loadingIndicator">
                                        <div class="d-flex justify-content-center" style="">
                                            <div class="spinner-border" style="position: absolute; top:50%; z-index: 99;" role="status">
                                                <span class="sr-only">Carregando...</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="graph_bar_prazos"></div>
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
 <script src="{{ asset('assets/js/helpers/ajaxUrl.js') }}" ></script>
 <script src="{{ asset('assets/js/tablesorter.js') }}" ></script>
 <script>

    $(function() {
        let chart = new Morris.Bar({
            element: 'chart',
            data: [
                @foreach ($mediaMeses as $job)
                    { mes: '{{ htmlspecialchars($job['mes']) }}', dias: {{ $job['dias'] }} },
                @endforeach
            ],
            xkey: 'mes',
            ykeys: ['dias'],
            labels: ['Média de dias'],
            hideHover: 'auto',
            barColors: ['#0acf97'],
            resize: true
        });

        Morris.Bar({
            element: 'graph_bar',
            data: <?php echo json_encode($resultadosDemanda); ?>,
            xkey: 'mes',
            ykeys: ['criadas', 'finalizadas'],
            labels: ['Criadas', 'Finalizadas'],
            hideHover: 'auto',
            resize: true,
            barColors: ['#34495E', '#0acf97'],
        });

        Morris.Bar({
            element: 'graph_bar_prazos',
            data: <?php echo json_encode($resultadosDemandaPrazos); ?>,
            xkey: 'mes',
            ykeys: ['atrasadas', 'prazo'],
            labels: ['Atrasados', 'No prazo'],
            hideHover: 'auto',
            resize: true,
            barColors: ['#e53d1f', '#0acf97'],
        });
    });

    $(document).ready(function() {
        $(".tablesorter").tablesorter({
            dateFormat: 'ddmmyyyy',
        });
    });

 </script>

@endsection
