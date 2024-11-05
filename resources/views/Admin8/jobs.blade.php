@extends('layouts.admin8')
@section('title', 'Jobs criados')

@section('css')
    <link href="{{ asset('assets/css/daterangepicker.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')

    <section>
        <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                   <div class="row">
                        <div class="col-sm-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="initalResume">
                                        <h5>Filtro de pesquisa</h5>
                                    </div>
                                    <div class="general-label">
                                        <form class="row row-cols-lg-auto g-3 align-items-center" method="GET">

                                            <div class="mb-0 adjustSelects">
                                                <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Pesquisar">
                                            </div>

                                            <div class="mb-0 adjustSelects" >
                                                <input type="number" name="jobId" value="{{ $jobId }}" class="form-control" placeholder="Job">
                                            </div>

                                            <div class="mb-0 adjustSelects" >
                                                <input class="form-control filter-daterangepicker"  placeholder="Intervalo de datas" type="text" name="dateRange" value="{{ $dateRange ? $dateRange : '' }}">
                                            </div>

                                            <div class="mb-0 adjustSelects">
                                                <select  class="form-select select2" name="ordem_filtro">
                                                    <option value="">Sem ordenação</option>
                                                    <option  @if($ordem_filtro == 'alfabetica') selected @endif value="alfabetica">Ordem alfabética (Título)</option>
                                                    <option  @if($ordem_filtro == 'crescente') selected @endif value="crescente">Ordem crescente (Job)</option>
                                                    <option  @if($ordem_filtro == 'decrescente') selected @endif value="decrescente">Ordem decrescente (Job)</option>
                                                </select>
                                            </div>

                                            <div class="mb-0 adjustSelects">
                                                <select  class="form-select select2" name="category_id">
                                                    <option @if($priority == '0') selected @endif value="0">Prioridade (todas)</option>
                                                    <option @if($priority == '1') selected @endif  value="1">Baixa</option>
                                                    <option @if($priority == '5') selected @endif  value="5">Média</option>
                                                    <option @if($priority == '7') selected @endif  value="7">Alta</option>
                                                    <option @if($priority == '10') selected @endif  value="10">Urgente</option>
                                                </select>
                                            </div>

                                            <div class="mb-0 adjustSelects">
                                                <select class="form-select select2" name="aprovada">
                                                    <option value="">Status</option>
                                                    <option  @if($aprovada == 'pendentes') selected @endif value="pendentes">Pendentes</option>
                                                    <option  @if($aprovada == 'em_pauta') selected @endif value="em_pauta">Em pauta</option>
                                                    <option  @if($aprovada == 'entregue') selected @endif value="entregue">Entregue</option>
                                                    <option  @if($aprovada == 'pausados') selected @endif value="pausados">Congelados</option>
                                                    <option  @if($aprovada == 'finalizados') selected @endif value="finalizados">Finalizados</option>
                                                </select>
                                            </div>

                                            <div class="mb-0 adjustSelects">
                                                <select class="form-select select2" name="marca_id">
                                                    <option selected="true" value="0">Todas as marcas</option>
                                                    @foreach ($brands as $brand )
                                                            <option @if($marca == $brand->id) selected @endif value="{{ $brand->id }}">{{ $brand->nome }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-0 adjustSelects">
                                                <select class="form-select select2" name="agencia_id">
                                                    <option selected="true" value="0">Agência</option>
                                                    @foreach ($agencies as $agencie )
                                                            <option @if($agencia == $agencie->id) selected @endif value="{{ $agencie->id }}">{{ $agencie->nome }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-0 adjustSelects">
                                                <select class="form-select select2" name="colaborador_id">
                                                    <option selected="true" value="0">Criador (todos)</option>
                                                    @foreach ($colaboradores as $colaborador )
                                                            <option @if($colaboradorActive == $colaborador->id) selected @endif value="{{ $colaborador->id }}">{{ $colaborador->nome }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="mb-0 adjustSelects">
                                                <select class="form-select select2" name="in_tyme">
                                                    <option @if(!$inTime) selected @endif value="">Selecionar prazos</option>
                                                    <option @if($inTime == '1') selected @endif value="1">Finalizadas em atraso</option>
                                                    <option  @if($inTime == '0') selected @endif  value="0">Finalizadas no prazo</option>
                                                    <option  @if($inTime == '2') selected @endif  value="2">Atrasadas</option>
                                                </select>
                                            </div>

                                            <div class="mb-0 adjustSelects">
                                                <a href="{{route('8poroito_Admin.jobs')}}" class="btn btn-danger ">Limpar</a>
                                                <button type="submit" class="btn btn-primary ">Pesquisar</button>
                                            </div>

                                            <input type="hidden" id="porpagina" class="form-control" style="width: 65px" type="number" name="porpagina" value="{{ $demandas->perPage() }}">

                                        </form>
                                        <!-- end form -->
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-xl-12">
                            <div class="card tableHome">
                                <div class="card-body">
                                    <div class="changeDem">
                                        <div class="adjustOrdemBt">
                                        <h5 class="card-title">Todos os jobs</h5>
                                        <button id="openColumnOrderModal" class="btn btn-link" data-bs-toggle="modal" data-bs-target="#columnOrderModal"><i class="fas fa-sort"></i></button>
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
                                                @component('components.TabelaAdmin8Component', ['demandas' => $demandas, 'arrayOrdem' => $arrayOrdem, 'sortableEnabled' => false, 'ordem' => $ordem])@endcomponent
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end col -->
                    </div>
                </div>
                <!-- container-fluid -->
            </div>
        </div>
    </section>
@endsection

@section('plugins')
@endsection

@section('scripts')
    <script src="{{ asset('assets/js/select2.js') }}" ></script>
    <script src="{{ asset('assets/js/momentlocale.js') }}" ></script>
    <script src="{{ asset('assets/js/daterangepicker.js') }}" ></script>
    <script src="{{ asset('assets/js/helpers/ajaxUrl.js') }}" ></script>
    <script src="{{ asset('assets/js/jqueryui.js') }}" ></script>
    <script src="{{ asset('assets/js/helpers/ordemColunas.js') }}" ></script>
<script>

    var table = $("#myTable");
    var defaultOrder = Array.from(Array(table.find("thead th").length).keys());

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
    $('.select2').select2({
        minimumResultsForSearch: Infinity
    });

    let dateRange = @json($dateRange);

    if (dateRange) {
        // Se a variável $dateRange possuir um valor válido
        $(".filter-daterangepicker").val(dateRange); // Define o valor do input usando jQuery
    } else {
        $(".filter-daterangepicker").val(''); // Define o valor do input usando jQuery
    }

    $('#pagination').on('change', function() {
        var numberPage = $(this).val();
        $('#porpagina').val(numberPage);
        var urlAtual = window.location.href;
        updatePaginationParams(urlAtual, true);
    });


});
</script>
@endsection
