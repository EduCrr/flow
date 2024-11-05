@extends('layouts.agencia')
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
                                                        <h5 class="card-title">Copiar job</h5>
                                                        <p class="warningText">OBS: Após a criação desse job, o ID será gerado automaticamente.</p>
                                                        <form id="formEdut" style="margin-top: 15px" method="POST" action="{{route('Agencia.copiar_action')}}" enctype="multipart/form-data" class="needs-validation responseAjax" novalidate>
                                                            @csrf
                                                            @component('components.FormularioComponentEdyCopyAgencia', ['demanda' => $demanda, 'users' => $users, 'marcas' => $marcas, 'colaboradores' => $colaboradores])@endcomponent
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

        $(document).ready(function() {
            //marcas pré-selecionado

            let ids = @json($marcasIds);
            $('#select2Multiple').val(ids).trigger('change');
            let idsUser = @json($usersIds);
            $('#select2MultipleUser').val(idsUser).trigger('change');

            $('#inputC').on('change', function() {
                let id = $(this).val();
                if(id != ''){
                    $('#loading').show();
                    $('#select2Multiple').empty();
                    $.ajax({
                        url: "/flow/getBrandsColaborador/" + id,
                        type: "get",
                        dataType: "json",
                        headers: {
                            'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                        },
                        success: function(response) {
                            let len = 0;
                            if (response != null) {
                                len = response.length;
                            }

                            if (len > 0) {
                                for (let i = 0; i < len; i++) {
                                    let id = response[i].id;
                                    let name = response[i].nome;
                                    let cor = response[i].cor;

                                    // Cria um novo objeto Option
                                    let option = new Option(name, id);

                                    // Define a cor da opção
                                    $(option).attr('data-cor', cor);

                                    // Adiciona o novo objeto ao select2
                                    $('#select2Multiple').append(option);
                                }

                                // Atualiza o select2
                                $('#select2Multiple').trigger('change');

                                $('#select2Multiple').select2({
                                    placeholder: "Selecione sua(s) marca(s)",
                                    allowClear: true,
                                    templateSelection: function (data, container) {
                                        var cor = $(data.element).data('cor');
                                        if (cor) {
                                            $(container).css("background-color", cor);
                                            $(container).css("color", "white");
                                        } else {
                                            $(container).css("background-color", "white");
                                            $(container).css("color", "black");
                                        }
                                        return data.text;
                                    }
                                });
                            }
                        },
                        complete: function() {
                            $('#loading').hide();
                        }
                    });
                }else{
                    $('#select2Multiple').empty();
                }
            });

        });
    </script>
@endsection
