@php
    $layout = $isAdminAg > 0 ? 'layouts.agencia' : 'layouts.colaborador';
@endphp

@extends($layout)
@section('title', 'Criar etapa 1')

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
                                    <h5 class="card-title">Criação de job: Etapa 1</h5>
                                    <form id="formCreate" style="margin-top: 15px" method="POST" action="{{route('Agencia.criar_action')}}" enctype="multipart/form-data" class="needs-validation responseAjax" novalidate>
                                        @csrf
                                        @component('components.FormularioComponentCriar', ['users' => $users, 'dataAtual' => $dataAtual, 'userInfos' => $userInfos])@endcomponent
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
@endsection

@section('scripts')
    <script src="{{ asset('assets/js/jqueryui.js') }}" ></script>
    <script src="{{ asset('assets/js/select2.js') }}" ></script>
    <script src="{{ asset('assets/js/select2pt-br.js') }}" ></script>
    <script src="{{ asset('assets/js/editar-copy-etapa-2-job.js') }}" ></script>
    <script src="{{ asset('assets/js/helpers/datas.js') }}" ></script>
    <script>

        $(document).ready(function() {

            $('#inputA').on('change', function() {
                let id = $(this).val();
                if(id != ''){
                    carregaUsuarios($(this).val());
                }else{
                    $('.selectColaborador').empty();
                }
            });

            function carregaUsuarios(id) {
                $('#loading').show();
                $.ajax({
                    url: '/usuarios/busca',
                    type: 'post',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    data: { id: id },
                    success: function(data) {
                    $('.selectColaborador').empty();

                    // Limpa os options existentes
                    $('.selectColaborador option').not(':first-child').remove();
                    // Adiciona os novos options
                    $.each(data, function(key, usuario) {
                        var option = new Option(usuario.nome, usuario.id, true);
                        $('.selectColaborador').append(option).trigger('change');
                    });
                    },
                    complete: function() {
                        $('#loading').hide();
                    }
                });
            }
        });

    </script>
@endsection
