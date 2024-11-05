<tbody class="">
    @if ($demanda['agencia'])
        <tr data-key="{{$demanda->id}}" class="trLink" style="cursor: pointer;" data-href="{{route('Job', ['id' => $demanda->id])}}">
            <td data-column-index="0"><strong>{{ $demanda->id }}</strong></td>
            <td data-column-index="1">
                <span class="badge" style="background-color: {{ $demanda->cor }}">
                {{$demanda->prioridade}}
                </span>
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
            <td data-column-index="6" style="display: flex; align-items: center;">
                @if($demanda->subCriador)
                <img alt="Imagem do usúario" class="avatar-xs rounded-circle me-2" src="{{url('/assets/images/users')}}/{{$demanda->subCriador->avatar }}">
                {{ $demanda->subCriador->nome }}
                @else
                <img alt="Imagem do usúario" class="avatar-xs rounded-circle me-2" src="{{url('/assets/images/users')}}/{{$demanda->criador->avatar }}">
                {{ $demanda->criador->nome }}
                @endif
            </td>
            <td data-column-index="8">
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
            <td data-column-index="9">
                {{ $demanda['agencia']->nome }}
            </td>
        </tr>
    @endif

</tbody>
