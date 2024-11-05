@php
    $layout = $loggedUser->tipo == 'colaborador' ? 'layouts.colaborador' : 'layouts.admin';
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
                                    <form id="formCreate" style="margin-top: 15px" method="POST" action="{{route('Job.criar_action')}}" enctype="multipart/form-data" class="needs-validation responseAjax" novalidate>
                                        @csrf
                                        @component('components.FormularioComponentCriar', ['userInfos' => $userInfos, 'dataAtual' => $dataAtual])@endcomponent
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

        // $('#agencia').on('change', function() {
        //     let id = $(this).val();
        //     if(id != ''){
        //         carregaUsuarios($(this).val());
        //     }else{
        //         $('.select2-multiple-user').empty();
        //     }
        // });

        // function carregaUsuarios(id) {
        //     $('#loading').show();
        //     $.ajax({
        //         url: '/usuarios/busca',
        //         type: 'post',
        //         headers: {
        //             'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        //         },
        //         data: { id: id },
        //         success: function(data) {
        //         $('.select2-multiple-user').empty();

        //         // Limpa os options existentes
        //         $('.select2-multiple-user option').not(':first-child').remove();
        //         // Adiciona os novos options
        //         $.each(data, function(key, usuario) {
        //             var option = new Option(usuario.nome, usuario.id, true);
        //             $('.select2-multiple-user').append(option).trigger('change');
        //         });
        //         },
        //         complete: function() {
        //             $('#loading').hide();
        //         }
        //     });
        // }

        // $('#inputF').on('change', function() {
        //     let date = $(this).val();
        //     let brand = $('#selectColaboradores').val();
        //     carregaData(date, brand)
        // });

        // function carregaData(date, brand) {
        //     $('#loading').show();
        //     $.ajax({
        //         url: '/jobs/date',
        //         type: 'post',
        //         headers: {
        //             'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
        //         },
        //         data: { final: date, brand },
        //         success: function(data) {
        //             if(data >= 2){
        //                 Swal.fire({
        //                     icon: 'info',
        //                     title: 'Atenção!',
        //                     text: `Já existem ${data} jobs programados para esta data de entrega.`,
        //                 });
        //             }

        //             return null;
        //         }
        //     });
        // }

    </script>
@endsection
