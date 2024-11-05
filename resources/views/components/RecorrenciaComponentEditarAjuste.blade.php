<form class="responseAjax" method="POST" action="{{route('Recorrencia.ajuste_edit_action', ['id' => $req->id])}}">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title align-self-center submitAjuste"
            id="modalReabirJob">Editar alteração</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <input name="id" value="{{$req->id}}" type="hidden"/>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="mb-3 no-margin">
                    <label class="mb-1">Descrição</label>
                    <textarea class="ckText descricaoAjuste" name="descricao">
                        {!! $req->descricao !!}
                    </textarea>
                </div>
            </div>
            <div class="col-md-12">
                <div class="mb-3 no-margin">
                    <label class="mb-1">Data</label>
                    <input name="data" value="{{$req->data}}" class="form-control dataAjuste dataRecorrencia" type="date"/>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light"
            data-bs-dismiss="modal">Fechar</button>
        <button type="submit" class="btn btn-primary submitModal submitAjuste">Editar alteração</button>
    </div>
</form>