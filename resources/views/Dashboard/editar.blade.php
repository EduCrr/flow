@php
    $layout = $loggedUser->tipo == 'colaborador' ? 'layouts.colaborador' : 'layouts.admin';
@endphp

@extends($layout)
@section('title', 'Editar job '. $demanda->id)

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
                                                        <div class="backJob">
                                                            <h5 class="card-title">Editar</h5>
                                                            <div class="btnCreate">
                                                                <a href="{{route('Job' , ['id' => $demanda->id])}}" class="btn ">Voltar</a>
                                                            </div>
                                                        </div>
                                                        <form id="formEdut" style="margin-top: 15px" method="POST" action="{{route('Job.editar_action', ['id' => $demanda->id])}}" enctype="multipart/form-data" class="needs-validation responseAjax" novalidate>
                                                            @csrf
                                                            @component('components.FormularioComponentEdyCopyColaborador', ['demanda' => $demanda])@endcomponent
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            {{-- <div class="col-md-12 col-xl-12">
                                                <div class="card">
                                                    <div class="card-body">
                                                         <form method="post" id="formEdut" class="responseAjax" action="{{route('Imagem.upload', ['id' => $demanda->id])}}" enctype="multipart/form-data">
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
                                            {{-- @if(count($demanda['imagens']) > 0)
                                            <div class="col-xl-12">
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
                                            </div>
                                            @endif --}}
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
            //marcas pré-selecionado
            let ids = @json($marcasIds);
            let idsUsers = @json($usersIds);
            let idsColaboradores = @json($colaboradoresIds);
            $('#select2Multiple').val(ids).trigger('change');
            $('#select2MultipleUsers').val(idsUsers).trigger('change');
            $('#select2MultipleColaboradores').val(idsColaboradores).trigger('change');
        });
    </script>
@endsection
