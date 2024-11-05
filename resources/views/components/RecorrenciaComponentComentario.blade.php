<form class="responseAjax" method="POST" action="{{route('Recorrencia.comentario_create_action')}}">
    @csrf
    <div class="modal-header">
        <h5 class="modal-title align-self-center submitComentario"
            id="modalReabirJob">Criar comentário</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <input name="id" value="{{$req->id}}" type="hidden"/>
    <div class="modal-body">
        <div class="row">
            <div class="col-md-12">
                <div class="mb-3 no-margin">
                    <label class="mb-1">Descrição</label>
                    <textarea class="ckText" name="descricao"></textarea>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light"
            data-bs-dismiss="modal">Fechar</button>
        <button type="submit" class="btn btn-primary submitModal submitComentario">Criar comentário</button>
    </div>
</form>
