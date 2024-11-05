@php
    // $layout = $loggedUser->tipo == 'agencia' ? 'layouts.agencia' : ($loggedUser->tipo == 'admin' || $loggedUser->tipo == 'admin_8' ? 'layouts.admin' : 'layouts.colaborador');
    $layout = ($loggedUser->tipo == 'agencia') ? 'layouts.agencia' :
    (($loggedUser->tipo == 'admin') ? 'layouts.admin' :
    (($loggedUser->tipo == 'admin_8') ? 'layouts.admin8' : 'layouts.colaborador'));

@endphp
@extends($layout)
@section('title', 'Editar atualizações')

@section('css')
    <link href="{{ asset('assets/css/jqueryui.css') }}" rel="stylesheet" type="text/css" />
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
                                    <form id="formCreate" style="margin-top: 15px" method="POST" action="{{route('Atualizacoes.editar', ['id' => $atualizations->id])}}" enctype="multipart/form-data" class="needs-validation responseAjax" novalidate>
                                        @csrf
                                        <div class="mb-3 row">
                                            <div class="col-lg-12">
                                               <label for="inputT" class="form-label pt-0">Título</label>
                                                <div class="adjustCount">
                                                    <input name="titulo" value="{{$atualizations->titulo}}"class="form-control" type="text" required id="inputT">
                                                    <div class="inputCount">
                                                        <span id="charCount">0</span> | 255
                                                    </div>
                                                    <div class="invalid-feedback">
                                                        Preencha o campo título
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-center" >
                                            <div style="margin-top: 20px;" class="spinner-border" role="status">
                                                <br/>
                                                <span class="sr-only">Carregando...</span>
                                            </div>
                                        </div>
                                        <div class="showBriefing">
                                            <div class="mb-3 row">
                                                <div class="col-lg-12">
                                                    <label for="example-datetime-local-input" class="col-sm-2 form-label">Descrição</label>
                                                    <textarea id="textAreaTiny" class="elm2" required name="descricao">
                                                        {!! $atualizations->descricao !!}
                                                    </textarea>
                                                    <div class="invalid-feedback">
                                                        Preencha a descrição
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <button id="submitButtonEdit" type="submit" class="btn btn-primary w-lg leftAuto verifyBtn">Editar</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- container-fluid -->
            </div>
        </div>
    </section>
@endsection


@section('plugins')
    <script src="{{ asset('assets/libs/tinymce/tinymce.min.js') }}"></script>
    <script src="{{ asset('assets/js/pages/form-editor.init.js') }}"></script>
@endsection

@section('scripts')
    <script src="{{ asset('assets/js/jqueryui.js') }}" ></script>
    <script src="{{ asset('assets/js/select2.js') }}" ></script>
    <script src="{{ asset('assets/js/editar-copy-etapa-2-job.js') }}" ></script>
    <script>

    $(document).ready(function() {
        var inputTitulo = $('#inputT');
        var charCountSpan = $('#charCount');
        var initialLength = inputTitulo.val().length;
        charCountSpan.text(initialLength);

        inputTitulo.on('input', function() {
            var currentLength = inputTitulo.val().length;
            charCountSpan.text(currentLength);

            if(currentLength > '255'){
                $('.inputCount').addClass('redCount')
            }else{
                $('.inputCount').removeClass('redCount')
            }
        });
    });



    </script>
@endsection
