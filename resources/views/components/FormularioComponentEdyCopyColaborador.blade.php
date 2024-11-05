<div class="mb-3 row">
    <div class="col-lg-6">
        <label for="inputT" class="form-label pt-0">Título</label>
        <div class="">
            <input name="titulo" value="{{ old('titulo', $demanda->titulo) }}" class="form-control" type="text" required id="inputT">
            <div class="invalid-feedback">
                Preencha o campo título
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <label for="inputP" class="col-sm-2 form-label">Prioridade</label>
        <div class="">
            <select id="inputP" name="prioridade" class="form-select select2" required>
                <option @if(old('prioridade', $demanda->prioridade) == 'Baixa') selected @endif value="Baixa">Baixa</option>
                <option @if(old('prioridade', $demanda->prioridade) == 'Média') selected @endif value="Média">Média</option>
                <option @if(old('prioridade', $demanda->prioridade) == 'Alta') selected @endif value="Alta">Alta</option>
                <option @if(old('prioridade', $demanda->prioridade) == 'Urgente') selected @endif value="Urgente">Urgente</option>
            </select>
            <div class="invalid-feedback">
                Preencha o prioridade
            </div>
        </div>
    </div>
</div>
@isset($marcasC)
<div class="mb-3 row">
    <div class="col-lg-12">
        <label for="inputC" class="col-sm-2 form-label">Marca</label>
        <div class="">
            <select id="inputC" name="marcasColaboradores" class="form-select select2" required>
                @foreach ($marcasC as $marca)
                    <option  @if ($marca->id == $demanda->marcas[0]->id) selected  @endif value="{{ $marca->id }}">{{ $marca->nome }}</option>
                @endforeach
            </select>
            <div class="invalid-feedback">
                Preencha o campo agência
            </div>
        </div>
    </div>
</div>
@endisset
<div class="mb-3 row">
    <div class="col-lg-6  mo-b-15">
        <label for="inputI"
            class="col-sm-2 form-label">Data inicial</label>
        <div class="">
            <input type="hidden" id="demandaInicio" value="{{ $demanda->inicio }}">
            <input value="{{ Str::contains(request()->url(), 'copiar') ? $dataAtual : old('inicio', $demanda->inicio) }}" class="form-control" name="inicio" type="datetime-local" required id="inputI">
            <div class="invalid-feedback">
                Preencha o campo data inicial
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <label for="inputF"
            class="col-sm-2 form-label">Data entrega</label>
        <div class="">
            <input name="final" value="{{ Str::contains(request()->url(), 'copiar') ? "" : old('final', $demanda->final) }}" class="form-control" type="datetime-local" required
                id="inputF">
            <div class="invalid-feedback">
                Preencha o campo data entrega
            </div>
        </div>
    </div>
</div>
<div class="mb-3 row">
    <div class="col-lg-6">
        <label for="inputDimensoes" class="col-sm-2 form-label">Dimensões (não é item obrigatório)</label>
        <div class="">
            <input value="{{ empty($demanda->descricoes->dimensoes) ? old('dimensoes', '') : $demanda->descricoes->dimensoes }}" name="dimensoes" placeholder="Medidas (cm ou px), quando necessário." class="form-control" type="text" id="inputDimensoes">
        </div>
    </div>
    {{-- <div class="col-lg-6">
    <label for="inputA" class="col-sm-2 form-label">Novos anexos</label>
        <div class="">
            <input id="inputA" type="file" name="arquivos[]" class="form-control" multiple/>
        </div>
    </div> --}}
    <div class="col-lg-6">
        <label for="inputD" class="col-sm-2 form-label">Link</label>
        <div class="">
            <input name="drive" value="{{ $demanda->drive }}" class="form-control" type="text"  id="inputD">
        </div>
    </div>
</div>
<div class="mb-3 row">
    <div class="col-lg-6">
        <label for="inputO" class="col-sm-2 form-label">Metas e objetivos</label>
        <div class="">
            <input value="{{ empty($demanda->descricoes->metas_objetivos) ? old('objetivos', '') : $demanda->descricoes->metas_objetivos }}" required name="objetivos" placeholder="Em uma frase, descrever o que precisamos resolver, qual o problema a ser resolvido? E qual o objetivo, onde queremos chegar?" class="form-control" type="text" id="inputO">
            <div class="invalid-feedback">
                Preencha o campo metas e objetivos
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <label for="inputPeca" class="col-sm-2 form-label">Peças necessárias</label>
        <div class="">
            <input value="{{ empty($demanda->descricoes->peças) ? old('pecas', '') :  $demanda->descricoes->peças }}" required name="pecas" placeholder="Existe mais de uma peça para ser produzida? Este é o momento de descrevê-la." class="form-control" type="text" id="inputPeca">
            <div class="invalid-feedback">
                Preencha o campo peças necessárias
            </div>
        </div>
    </div>
</div>

<div class="mb-3 row">
    <div class="col-lg-6">
        <label for="inputFomato" class="col-sm-2 form-label">Formato</label>
        <div class="">
            <div class="spaceSelect">
                <select id="inputFormatoID" name="formato" class="form-select select2" required>
                    <option @if(!empty($demanda->descricoes->formato) && old('formato', $demanda->descricoes->formato) == 'impresso') selected @endif value="impresso">Impresso</option>
                    <option @if(!empty($demanda->descricoes->formato) && old('formato', $demanda->descricoes->formato) == 'digital') selected @endif value="digital">Digital</option>
                    <option @if(!empty($demanda->descricoes->formato) && old('formato', $demanda->descricoes->formato) == 'campanha') selected @endif value="campanha">Campanha</option>
                </select>
            </div>
            <div class="invalid-feedback">
                Preencha o campo formato
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <label for="inputFomatoText" class="col-sm-2 form-label">Formato</label>
        <div class="">
            <input value="{{ empty($demanda->descricoes->formato_texto) ? old('formatoInput', '') :  $demanda->descricoes->formato_texto }}" required name="formatoInput" placeholder="Existe alguma formatação especial (com dobra, com faca especial....)? Como o arquivo deve ser entregue (JPG, PNG, vídeo, PDF impressão, PDF editável, etc.)" class="form-control" type="text" id="inputFomatoText">
            <div class="invalid-feedback">
                Preencha o campo formato
            </div>
        </div>
    </div>
</div>

<div class="mb-3 row">
    <div class="col-lg-12">
        <div class="d-flex justify-content-center adjustTopDescricao">
            <div style="margin-top: 20px;" class="spinner-border" role="status">
                <br/>
                <span class="sr-only">Carregando...</span>
            </div>
        </div>
        <div class="showBriefing">
            <label for="example-datetime-local-input" class="col-sm-2 form-label">Descrição</label>
            <textarea class="ckText" id="briefing" required name="briefing">{{ $demanda->descricoes->descricao }}</textarea>
            <div class="invalid-feedback">
                Preencha a descrição
            </div>
        </div>
    </div>
</div>

<button id="submitButtonEdit" type="submit" class="btn btn-primary w-lg leftAuto verifyBtn">Atualizar</button>
