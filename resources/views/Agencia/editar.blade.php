@php
    $layout = $isAdminAg > 0 ? 'layouts.agencia' : 'layouts.colaborador';
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
                                <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active pb-3 pt-0"
                                            data-bs-toggle="tab"
                                            href="#job"
                                            role="tab"
                                            ><i class="fab fa-product-hunt me-2"></i>Job
                                        </a>
                                    </li>
                                </ul>
                                <div class="tab-content pt-4">
                                    <div class="tab-pane active" id="job" role="tabpanel" >
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Edite seu job</h5>
                                                        <form id="formEdut" style="margin-top: 15px" method="POST" action="{{route('Agencia.editar_action', ['id' => $demanda->id])}}" enctype="multipart/form-data" class="needs-validation responseAjax" novalidate>
                                                            @csrf
                                                            @component('components.FormularioComponentEdyCopyAgencia', ['demanda' => $demanda, 'colaboradores' => $colaboradores])@endcomponent
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
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

    </script>

@endsection

{{-- // $(document).ready(function() {
    //     //marcas pré-selecionado
    //     let ids = @json($marcasIds);
    //     $('#select2Multiple').val(ids).trigger('change');
    //     let idsUser = @json($usersIds);
    //     $('#select2MultipleUser').val(idsUser).trigger('change');

    //     let idsColaboradores = @json($colaboradoresIds);
    //     $('#select2MultipleColaboradores').val(idsColaboradores).trigger('change');

    // }); --}}
