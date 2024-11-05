<div class="col-sm-12">
    <div class="card">
        <div class="card-body">
            <div class="adjustBriefing" style="margin-bottom: 10px">
                <h5 class="card-title comments">Etapa 1</h5>
                <a class="arounded" data-bs-toggle="collapse" href="#collapseEtapa1" role="button" aria-expanded="false" aria-controls="collapseBriefing">
                </a>
            </div>
            <div class="collapse" id="collapseEtapa1">
                <div class="mb-3 row">
                    <div class="col-lg-6 mo-b-15">
                        <label for="inputT" class="form-label pt-0">Título</label>
                        <div class="">
                            <input name="titulo" value="{{$demanda->titulo}}" class="form-control" type="text" required id="inputT">
                            <div class="invalid-feedback">
                                Preencha o campo título
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <label for="inputP" class="col-sm-2 form-label">Prioridade</label>
                        <div class="">
                            <select id="inputP" name="prioridade" class="form-select select2" required>
                                <option @if($demanda->prioridade == 'Baixa') selected @endif  value="Baixa">Baixa</option>
                                <option @if($demanda->prioridade == 'Média') selected @endif value="Média">Média</option>
                                <option @if($demanda->prioridade == 'Alta') selected @endif value="Alta">Alta</option>
                                <option @if($demanda->prioridade == 'Urgente') selected @endif value="Urgente">Urgente</option>
                            </select>
                            <div class="invalid-feedback">
                                Preencha o prioridade
                            </div>
                        </div>
                    </div>

                </div>
                <div class="mb-3 row">
                    @if(isset($colaboradorCriador))
                        <div class="col-lg-6">
                            <label for="inputA" class="col-sm-2 form-label">Agência</label>
                            <div class="">
                                <select id="inputA" name="marca" class="form-select select2" required>
                                    @foreach ($userInfos['marcas'] as $marca)
                                        <option @if (empty(old('marca')) && $demanda->marcas[0]->id == $marca->id) selected @elseif (!empty(old('marca')) && in_array($marca->id, old('marca'))) selected @endif value="{{ $marca->id }}">{{ $marca->nome }}</option>
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
                                    @foreach ($colaboradorCriador as $c)
                                        <option @if($c->id == $demanda->criador_id)  selected @endif value="{{ $c->id }}">{{ $c->nome }}</option>
                                    @endforeach
                                </select>
                                <div class="invalid-feedback">
                                    Preencha o campo colaborador
                                </div>
                            </div>
                        </div>
                        @else
                        <div class="mb-3 row">
                            <div class="col-lg-12">
                                <label for="selectColaboradores" class="col-sm-2 form-label">Marca</label>
                                <div class="">
                                    <select id="selectColaboradores" name="marcasColaboradores" class="form-select select2" required>
                                        @foreach ($marcasColaboradores as $marca)
                                         <option @if ($marca->id ==  $demanda->marcas[0]->id) selected @endif value="{{ $marca->id }}">{{ $marca->nome }}</option>
                                        @endforeach
                                    </select>
                                    <div class="invalid-feedback">
                                        Preencha o campo marca
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="mb-3 row">
                    <div class="col-lg-6  mo-b-15">
                        <label for="inputI"
                            class="col-sm-2 form-label">Data inicial</label>
                        <div class="">
                            <input type="hidden" id="demandaInicio" value="{{ $demanda->inicio }}">
                            <input value="{{ $demanda->inicio }}" class="form-control" name="inicio" type="datetime-local" required
                            id="inputI">
                            <div class="invalid-feedback">
                                Preencha o campo data inicial
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <label for="inputF"
                            class="col-sm-2 form-label">Data de entrega</label>
                        <div class="">
                            <input name="final" value="{{ $demanda->final }}" class="form-control" type="datetime-local" required
                                id="inputF">
                            <div class="invalid-feedback">
                                Preencha o campo data de entrega
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="col-sm-12">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title comments">Etapa 2</h5>
            <br/>
            <div class="mb-3 row">
                <div class="col-lg-12  mo-b-15">
                    <label for="inputD" class="col-sm-2 form-label">Link</label>
                    <div class="">
                        <input name="drive" class="form-control" type="text" id="inputD">
                    </div>
                </div>
            </div>
            <div class="mb-3 row">
                <div class="col-lg-12  mo-b-15">
                    <label for="inputO" class="col-sm-2 form-label">Metas e objetivos</label>
                    <div class="">
                        <input required name="objetivos" placeholder="Em uma frase, descrever o que precisamos resolver, qual o problema a ser resolvido? E qual o objetivo, onde queremos chegar?" class="form-control" type="text" id="inputO">
                        <div class="invalid-feedback">
                            Preencha o campo metas e objetivos
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3 row">
                <div class="col-lg-12  mo-b-15">
                    <label for="inputPeca" class="col-sm-2 form-label">Peças necessárias</label>
                    <div class="">
                        <input required name="pecas" placeholder="Existe mais de uma peça para ser produzida? Este é o momento de descrevê-la." class="form-control" type="text" id="inputPeca">
                        <div class="invalid-feedback">
                            Preencha o campo peças necessárias
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3 row">
                <div class="col-lg-12  mo-b-15">
                    <label for="inputFomato" class="col-sm-2 form-label">Formato</label>
                    <div class="">
                        <div class="spaceSelect">
                            <select id="inputFormatoID" name="formato" class="form-select select2" required>
                                <option @if($demanda->formato == 'impresso') selected @endif  value="impresso">Impresso</option>
                                <option @if($demanda->formato == 'digital') selected @endif value="digital">Digital</option>
                                <option @if($demanda->formato == 'campanha') selected @endif value="campanha">Campanha</option>
                            </select>
                        </div>
                        <input required name="formatoInput" placeholder="Existe alguma formatação especial (com dobra, com faca especial....)? Como o arquivo deve ser entregue (JPG, PNG, vídeo, PDF impressão, PDF editável, etc.)" class="form-control" type="text" id="inputFomato">
                        <div class="invalid-feedback">
                            Preencha o campo formato
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3 row">
                <div class="col-lg-12  mo-b-15">
                    <label for="inputDimensoes" class="col-sm-2 form-label">Dimensões (não é item obrigatório)</label>
                    <div class="">
                        <input name="dimensoes" placeholder="Medidas (cm ou px), quando necessário." class="form-control" type="text" id="inputDimensoes">
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-center" style="height: 15px;">
                <div style="margin-top: 20px;" class="spinner-border" role="status">
                    <br/>
                    <span class="sr-only">Carregando...</span>
                </div>
            </div>
            <div class="showBriefing">
                <div class="mb-3 row">
                    <div class="col-lg-12  mo-b-15">
                        <label for="example-datetime-local-input" class="col-sm-2 form-label">Descrição</label>
                        <textarea class="ckText" id="briefing" required name="briefing">
                            <p><em>Descreva sua interpreta&ccedil;&atilde;o do briefing, citando todos os itens das etapas anteriores. Traga exemplos, deixe mais claro suas expectativas e objetivos.</em></p>
                        </textarea>
                        <div class="invalid-feedback">
                            Preencha a descrição
                        </div>
                    </div>
                </div>
            </div>
            <button id="submitButtonEdit" type="submit" class="btn btn-primary w-lg leftAuto verifyBtn">Finalizar etapa 2</button>
        </div>
    </div>
</div>
{{-- <div class="col-md-12 col-xl-12">
    <div class="card">
        <div class="card-body">
             <form class="responseAjax" action="{{route('Imagem.upload', ['id' => $demanda->id])}}" enctype="multipart/form-data"  method="post">
                @csrf
                <div class="row gx-3">
                    <div class="col-md-12">
                        <div class="mb-3">
                            <input data-url="{{route('Imagem.upload', ":id")}}" type="file" class="notReload" name="file[]" multiple />
                            <input type="hidden" id="textbox_id" value="{{ $demanda->id }}"/>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div> --}}
