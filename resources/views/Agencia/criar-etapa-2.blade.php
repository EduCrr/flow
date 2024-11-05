@php
    $layout = $isAdminAg > 0 ? 'layouts.agencia' : 'layouts.colaborador';
@endphp

@extends($layout)
@section('title', 'Criar etapa 2')

@section('css')
@endsection

@section('content')

    <section>
          <div class="main-content">
            <div class="page-content">
                <div class="container-fluid">
                   <div class="row">
                        <div class="">
                            <div class="custom-tab tab-profile">
                                <div class="tab-content pt-4">
                                    <div class="tab-pane active" id="job" role="tabpanel" >
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Etapa 2</h5>
                                                        <br/>
                                                        <p style="margin-top: 0px; margin-bottom: 0px">Numeração do JOB: {{$demanda->id}}</p>
                                                        <p class="warningText">OBS: Após a criação desse job, o ID será gerado automaticamente junto com o título do novo job criado.</p>
                                                    </div>
                                                </div>
                                            </div>
                                            <form id="formEdut" style="margin-top: 15px" method="POST" action="{{route('Agencia.criar_action_stage_2', ['id' => $demanda->id])}}" enctype="multipart/form-data" class="needs-validation responseAjax" novalidate>
                                                @csrf
                                                @component('components.FormularioComponentCriar2', ['demanda' => $demanda, 'userInfos' => $userInfos, 'colaboradorCriador' => $colaboradorCriador])@endcomponent
                                            </form>
                                            {{-- <div class="col-xl-12">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Arquivo(s) anexado(s)</h5>
                                                        @foreach ( $demanda['imagens'] as $item )
                                                            <div class="dropdown">
                                                                <button style="padding:0px" type="button" class="btn header-item waves-effect" data-bs-toggle="dropdown"
                                                                    aria-haspopup="true" aria-expanded="false">
                                                                    <span class="d-none d-xl-inline-block ms-1">{{ $item->imagem }}</span>
                                                                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                                                                </button>
                                                                <div class="dropdown-menu">
                                                                    <a href="{{ route('download.image', $item->id) }}" class="dropdown-item">Download</a>
                                                                    <form class="responseAjax" action="{{ route('Imagem.delete', $item->id) }}" method="post">
                                                                        @csrf
                                                                        <button type="submit" class="dropdown-item deleteArq" >Excluir</button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div> --}}
                                        </div>
                                    </div>
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
                url: '/flow/usuarios/busca',
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
