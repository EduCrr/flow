@php
    $layout = $loggedUser->tipo == 'colaborador' ? 'layouts.colaborador' : 'layouts.admin';
@endphp

@extends($layout)
@section('title', 'Copiar job')

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
                                    <div class="tab-pane active"  role="tabpanel" >
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="card">
                                                    <div class="card-body">
                                                        <h5 class="card-title">Copiar job</h5>
                                                        <p class="warningText">OBS: Após a criação desse job, o ID será gerado automaticamente.</p>
                                                        <form id="formEdut" style="margin-top: 15px" method="POST" action="{{route('Job.copiar_action')}}" enctype="multipart/form-data" class="needs-validation responseAjax" novalidate>
                                                            @csrf
                                                            @component('components.FormularioComponentEdyCopyColaborador', ['demanda' => $demanda, 'marcasC' => $marcasC])@endcomponent
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
