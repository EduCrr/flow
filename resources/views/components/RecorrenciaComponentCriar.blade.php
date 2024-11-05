<div class="modal-body">
    <div class="row">
        <div class="col-md-12">
            <!-- <div class="mb-3 no-margin">
                <label class="mb-1">Título</label>
                @if($tipo == 'mensal')
                <input name="titulo" class="form-control tituloMensal"  type="text" />
                @elseif($tipo == 'anual')
                <input name="titulo" class="form-control tituloAnual"  type="text" />
                @elseif($tipo == 'semanal')
                <input name="titulo" class="form-control tituloSemanal"  type="text" />
                @endif
            </div> -->
        </div>
        <!-- <div class="col-md-12">
            <div class="mb-3 no-margin">
                <label class="mb-1">Descrição</label>
                <textarea  class="form-control field-7 elm1" name="descricao"></textarea>
            </div>
        </div> -->
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
        @if($tipo == 'mensal')
        <div class="col-md-6 mensal">
            <div class="mb-3 no-margin">
                <label class="mb-1">Começa em:</label>
                <input name="inicio" class="form-control" type="month"/>
            </div>
        </div>
        <div class="col-md-6 mensal">
            <div class="mb-3 no-margin">
                <label class="mb-1">Termina em:</label>
                <input name="final" class="form-control" type="month"/>
            </div>
        </div>
        <div class="col-md-12 mensal">
            <div class="mb-3 no-margin">
                <label class="mb-1">Dia das entregas:</label>
                <input name="dia_ocorrencia" class="form-control" type="number" min="1" max="31"/>
            </div>
        </div>
        @elseif($tipo == 'semanal')
        <div class="col-md-12">
            <div class="mb-3 no-margin">
                <label class="mb-1">Intervalo de datas</label>
                <input type="text" class="form-control date-multiple" placeholder="Intervalo de datas" name="dateRange">
            </div>
        </div>
        @elseif($tipo == 'anual')
        <div class="col-md-6 anual">
            <div class="mb-3 no-margin">
                <label class="mb-1">Selecione o ano inicial e o dia:</label>
                <input name="anoInicial" class="form-control" type="date"/>
            </div>
        </div>
        <div class="col-md-6 anual">
            <div class="mb-3 no-margin">
                <label class="mb-1">Selecione o ano final:</label>
                <select name="anoFinal" id="yearpicker" class="form-select select2">
                    <?php
                        $startYear = date("Y");
                        $lastYear = $startYear + 6;
                        for ($i = $startYear; $i <= $lastYear; $i++) {
                            echo "<option value='$i'>$i</option>";
                        }
                    ?>
                </select>
            </div>
        </div>
        @endif
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light"
        data-bs-dismiss="modal">Fechar</button>
    <button type="submit" class="btn btn-primary submitModal">Confirmar</button>
</div>
