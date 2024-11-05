<form class="responseAjax" method="POST" action="{{route($post, ['id' => $req->id])}}">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title align-self-center"
            id="modalReabirJob">Editar recorrência</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="mb-3 no-margin">
                    <label class="mb-1">Campanha</label>
                    @if($tipo == 'mensal')
                    <input value="{{$item->titulo}}" name="campanha" class="form-control campanhaMensal" type="text" />
                    @elseif($tipo == 'anual')
                    <input value="{{$item->titulo}}" name="campanha" class="form-control campanhaAnual" type="text" />
                    @elseif($tipo == 'semanal')
                    <input value="{{$item->titulo}}" name="campanha" class="form-control campanhaSemanal" type="text" />
                    @endif
                </div>
            </div>
            <div class="col-md-12">
                <div class="mb-3 no-margin">
                    <label class="mb-1">Título</label>
                    @if($tipo == 'mensal')
                    <input value="{{$req->titulo}}" name="titulo" class="form-control tituloMensal"  type="text" />
                    @elseif($tipo == 'anual')
                    <input name="titulo" value="{{$req->titulo}}" class="form-control tituloAnual"  type="text" />
                    @elseif($tipo == 'semanal')
                    <input value="{{$req->titulo}}" name="titulo" class="form-control tituloSemanal"  type="text" />
                    @endif
                </div>
            </div>
            <div class="col-md-12">
                <div class="mb-3 no-margin">
                    <label class="mb-1">Descrição</label>
                    @if($tipo == 'mensal')
                    <textarea class="ckText" id="descricaoMensal-{{$req->id}}" name="descricao">
                        {{$req->descricao}}
                    </textarea>
                    @elseif($tipo == 'anual')
                    <textarea  class="ckText" id="descricaoAnual-{{$req->id}}" name="descricao">
                        {{$req->descricao}}
                    </textarea>
                    @elseif($tipo == 'semanal')
                    <textarea class="ckText" id="descricaoSemanal-{{$req->id}}" name="descricao">
                        {{$req->descricao}}
                    </textarea>
                    @endif
                </div>
            </div>
            <div class="col-md-12">
                <div class="mb-3 no-margin">
                    <label class="mb-1">Padrão de recorrência</label>
                    <select name="tipoRecorrencia" class="form-select select2 tipoRecorrencia">
                        @if($tipo == 'mensal')
                        <option value="Mensal">Mensal</option>
                        @elseif($tipo == 'anual')
                        <option value="Anual">Anual</option>
                        @elseif($tipo == 'semanal')
                        <option value="Semanal">Semanal</option>
                        @endif
                    </select>
                </div>
            </div>
            <div class="col-md-12">
                <div class="mb-3 no-margin">
                    <label class="mb-1">Data</label>
                    <input name="data" value="{{$req->data}}" class="form-control dataRecorrencia" type="date"/>
                </div>
            </div>
            <div class="col-md-12">
                @if($req->entregue == 1)
                    <div class="mt-3 no-margin">
                        <label class="mb-1">Entregue: {{ \Carbon\Carbon::parse($req->data_entrega)->locale('pt_BR')->isoFormat('DD/MM/YYYY HH:mm')}}
                            @if($req->atrasada == 1) <span class="atrasado">Entregue com atraso</span> @endif
                        </label>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light"
            data-bs-dismiss="modal">Fechar</button>
        <button type="submit" class="btn btn-primary submitModal">Confirmar</button>
    </div>
</form>
<div class="modal-footer" style="margin-right: auto">
<form class="responseAjax" action="{{ route('Recorrencia.single_delete', ['id' => $req->id]) }}" method="POST">
    @csrf
    @method('DELETE')
    <button type="submit" class="submitForm btnDeleteJob" style="background-color: #f73e1d;" title="Excluir">
        Excluir
    </button>
</form>
</div>

