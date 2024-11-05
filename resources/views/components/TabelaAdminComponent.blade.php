<table id="myTable" class="table table-hover table-centered table-nowrap mb-0 showTableJobs ">
    @if(count($demandas) == 0)
        <p style="text-align: left; margin-bottom: 1.5rem; font-weight: bold;">Nenhum job foi encontrado!</p>
    @endif
    <thead>
        <tr id="sortableHead">
            <th data-column-index="0">
                <div data-name="job" data-ordem="{{$ordem ?? 'desc'}}" class="th-content th-coluna">Job</div>
            </th>
            <th data-column-index="1">
                <div data-name="prioridade" data-ordem="{{$ordem ?? 'desc'}}" class="th-content th-coluna">Prioridade</div>
            </th>
            <th data-column-index="2">
                <div data-name="titulo" data-ordem="{{$ordem ?? 'desc'}}" class="th-content th-coluna">Título</div>
            </th>
            <th data-column-index="3">
                <div data-name="status" data-ordem="{{$ordem ?? 'desc'}}" class="th-content th-coluna">Status</div>
            </th>
            <th data-column-index="4">
                <div data-name="inicial" data-ordem="{{$ordem ?? 'desc'}}" class="th-content th-coluna">Prazo inicial</div>
            </th>
            <th data-column-index="5">
                <div data-name="entrega" data-ordem="{{$ordem ?? 'desc'}}" class="th-content th-coluna">Prazo de entrega</div>
            </th>
            <th data-column-index="6">
                <div data-name="criador" data-ordem="{{$ordem ?? 'desc'}}" class="th-content th-coluna">Criador</div>
            </th>
            <th data-column-index="7">
                <div class="th-content">Progresso</div>
            </th>
            <th data-column-index="8"></th>
        </tr>
    </thead>
    <tbody class="{{ $sortableEnabled ? '' : 'sortable' }}">
        @foreach ($demandas as $demanda)
            @if ($demanda['agencia'])
                <tr data-key="{{$demanda->id}}" id="sortableColumns" class="trLink" style="cursor: pointer; background:{{$demanda->count_prazosDaPauta && $loggedUser->id == $demanda->criador_id ? 'rgb(255 219 114 / 85%)' : ''}}" data-href="{{route('Job', ['id' => $demanda->id])}}">
                    <td data-column-index="0"><strong>{{ $demanda->id }}</strong></td>
                    <td data-column-index="1">
                        <span class="badge" style="background-color: {{ $demanda->cor }}">
                           {{$demanda->prioridade}}
                        </span>
                        @if($demanda->recorrente)
                            <span style="font-size: 9px; font-style: italic">Recorrente</span>
                        @endif
                    </td>
                    <td data-column-index="2" class="title">{{ $demanda->titulo }}</td>
                    <td data-column-index="3">
                        @if($demanda->em_pauta == 1 && $demanda->pausado == 0)
                            <span class="statusBadge" style="margin: 0px; background-color: #f9bc0b">EM PAUTA</span>
                        @elseif ($demanda->em_pauta == 0 && $demanda->finalizada == 0 && $demanda->entregue == '0' && $demanda->pausado == 0)
                            <span style="background-color: #ff8538" class="statusBadge" style="margin: 0px">PENDENTE</span>
                        @elseif($demanda->entregue == 1  && $demanda->pausado == 0)
                            <span style="background-color: #3dbb3d"  class="statusBadge" style="margin: 0px">ENTREGUE</span>
                        @elseif($demanda->pausado == 1)
                            <span class="statusBadge" style="margin: 0px; background-color: #a0e5f3">CONGELADO</span>
                        @elseif($demanda->finalizada == 1)
                            <span style="background-color: #cfcfcf" class="statusBadge" style="margin: 0px">FINALIZADO</span>
                        @endif
                    </td>
                    <td data-date="{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $demanda->inicio)->format('d/m/Y H:i:s'); }}"  data-column-index="4">{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $demanda->inicio)->format('d/m/Y H:i'); }}</td>
                    @if($demanda->recorrente == 1 && $demanda->mostRecentDate != null)
                    <td data-date="{{ Carbon\Carbon::createFromFormat('Y-m-d', $demanda->mostRecentDate)->format('d/m/Y') }}" data-column-index="5">
                        {{ Carbon\Carbon::createFromFormat('Y-m-d', $demanda->mostRecentDate)->format('d/m/Y') }}
                        @if(Carbon\Carbon::parse($dataAtualRec)->gt(Carbon\Carbon::parse($demanda->mostRecentDate)))
                            <span class="atrasado">ATRASADO!</span>
                        @endif
                    </td>
                    @else
                    <td data-date="{{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $demanda->final)->format('Y-m-d H:i:s'); }}" data-column-index="5">
                        {{ Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $demanda->final)->format('d/m/Y H:i'); }}
                        @if($demanda->entregue == 0 && $demanda->finalizada == 0 && $dataAtual->greaterThan($demanda->final))
                            <span class="atrasado">ATRASADO!</span>
                        @elseif($demanda->finalizada == 1 && $demanda->atrasada == 1)
                            <span class="atrasado">FINALIZADA COM ATRASO!</span>
                        @elseif($demanda->entregue == 1 && $demanda->finalizada == 0 && $demanda->atrasada == 1)
                            <span class="atrasado">ENTREGUE COM ATRASO!</span>
                        @endif
                    </td>
                    @endif
                    <td data-column-index="6">
                        @if($demanda->subCriador)
                        <img alt="Imagem do usúario" class="avatar-xs rounded-circle me-2" src="{{url('/assets/images/users')}}/{{$demanda->subCriador->avatar }}">
                        {{ $demanda->subCriador->nome }}
                        @else
                        <img alt="Imagem do usúario" class="avatar-xs rounded-circle me-2" src="{{url('/assets/images/users')}}/{{$demanda->criador->avatar }}">
                        {{ $demanda->criador->nome }}
                        @endif
                    </td>
                    <td data-column-index="7">
                        <div style="width: 130px;">
                            <small class="float-end ms-2 font-size-12 numberProgress">{{$demanda->porcentagem}}%</small>
                            <div class="progress mt-2" style="height: 5px">
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
                    </td>
                    <td data-column-index="8">
                        @if($demanda->hasComentariosNaoLidos)
                            <span>
                            <i class="fas fa-comment-dots msg"></i>
                            </span>
                        @endif
                        @if($loggedUser->id == $demanda->criador_id || $demanda->demandaColaboradores->contains('pivot.usuario_id', $loggedUser->id))
                        <span class="myJob">
                            <i class="fas fa fa-bookmark"></i>
                        </span>
                        @endif
                    </td>
                </tr>
            @endif
        @endforeach
    </tbody>
</table>
<div class="adjustPagination">
    <div class="text-primary">
        <div class="showTableJobs">
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
    <input id="pagination" class="form-control" style="width: 65px" type="number" name="porpagina" value="{{ $demandas->perPage() }}">
</div>

<script src="{{ asset('assets/js/helpers/ajaxUrl.js') }}" ></script>
{{-- <script src="{{ asset('assets/js/tablesorter.js') }}"></script> --}}
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
        $(".tablesorter").tablesorter({
            dateFormat: 'ddmmyyyy',
        });

    });
</script>
