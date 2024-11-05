<div class="mb-3 row">
    <div class="col-lg-6 mo-b-15">
       <label for="inputT" class="form-label pt-0">Título</label>
        <div>
            <input name="titulo" value="{{ old('titulo') }}" class="form-control" type="text" required  id="inputT">
            <div class="invalid-feedback">
                Preencha o campo título
            </div>
        </div>
    </div>
    <div class="<?php echo isset($userInfos) ? 'col-lg-6' : 'col-lg-6'; ?>">
        <label for="inputP" class="col-sm-2 form-label">Prioridade</label>
        <div>
            <select id="inputP" name="prioridade" class="form-select select2" required>
                <option value="Baixa" {{ old('prioridade') == 'Baixa' ? 'selected' : '' }}>Baixa</option>
                <option value="Média" {{ old('prioridade') == 'Média' ? 'selected' : '' }}>Média</option>
                <option value="Alta" {{ old('prioridade') == 'Alta' ? 'selected' : '' }}>Alta</option>
                <option value="Urgente" {{ old('prioridade') == 'Urgente' ? 'selected' : '' }}>Urgente</option>
            </select>
            <div class="invalid-feedback">
                Preencha o campo prioridade
            </div>
        </div>
    </div>
</div>
@if(isset($users)) {{--AGENCIA--}}
    <div class="mb-3 row">
        <div class="col-lg-6">
            <label for="inputA" class="col-sm-2 form-label">Agência</label>
            <div class="">
                <select id="inputA" name="marca" class="form-select select2" required>
                    <option value="" selected="true">Selecione uma agência</option>
                    @foreach ($userInfos['marcas'] as $marca)
                        <option  @if (!empty(old('marca')) && in_array($marca->id, old('marca'))) selected  @endif value="{{ $marca->id }}">{{ $marca->nome }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback">
                    Preencha o campo agência
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <label for="colaborador" class="col-sm-2 form-label flexLoading">Colaborador
                <div id="loading" style="display:none; margin-left: 5px">
                    <i style="color: #000" class="fa fa-spinner fa-spin"></i>
                </div>
            </label>
            <div>
                <select id="colaborador" class="form-select select2 selectColaborador" name="colaborador" required id="select2MultipleUser">
                </select>
                <div class="invalid-feedback">
                    Preencha o campo colaborador
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="mb-3 row">
        <div class="col-lg-12">
            <label for="selectColaboradores" class="col-sm-2 form-label">Marca</label>
            <div class="">
                <select id="selectColaboradores" name="marcasColaboradores" class="form-select select2" required>
                    @foreach ($userInfos['marcasColaborador'] as $marca)
                        <option  @if (!empty(old('marcasColaboradores')) && in_array($marca->id, old('marcasColaboradores'))) selected  @endif value="{{ $marca->id }}">{{ $marca->nome }}</option>
                    @endforeach
                </select>
                <div class="invalid-feedback">
                    Preencha o campo agência
                </div>
            </div>
        </div>
    </div>
@endif

<div class="mb-3 row">
    <div class="col-lg-6  mo-b-15">
        <label for="inputI"
            class="col-sm-2 form-label">Data inicial</label>
        <div>
            <input class="form-control" value="{{$dataAtual}}" name="inicio" type="datetime-local" required id="inputI">
            <div class="invalid-feedback">
                Preencha o campo data inicial
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="showInfojobs">
            <label for="inputF" class="col-sm-2 form-label">Data de entrega</label>
        </div>

        <div>
            <input name="final" value="{{$data16}}" class="form-control" type="datetime-local" id="inputF" required>
            <div class="invalid-feedback">
                Preencha o campo data entrega
            </div>
        </div>
    </div>
</div>
<button type="submit" class="btn btn-primary w-lg leftAuto verifyBtn" id="submitButtonCreate">Criar etapa 1</button>
