@php
    // $layout = $loggedUser->tipo == 'agencia' ? 'layouts.agencia' : ($loggedUser->tipo == 'admin' || $loggedUser->tipo == 'admin_8' ? 'layouts.admin' : 'layouts.colaborador');
    $layout = ($loggedUser->tipo == 'agencia') ? 'layouts.agencia' :
    (($loggedUser->tipo == 'admin') ? 'layouts.admin' :
    (($loggedUser->tipo == 'admin_8') ? 'layouts.admin8' : 'layouts.colaborador'));

@endphp

@extends($layout)
@section('title', 'Atualizações')

@section('css')
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
                                    <div class="changeDem">
                                      <h5 class="card-title">Últimas atualizações</h5>
                                        @if($loggedUser->id == 39)
                                        <div class="btnCreate">
                                            <a href="{{route('Atualizacoes.criar')}}" class="btn ">Criar</a>
                                        </div>
                                        @endif
                                    </div>
                                   <div class="accordion accordion-plus-icon mt-2 mb-2" id="accordionExample">
                                        @if(count($atualizations) == 0)
                                            <p>Nenhuma atualização foi encontrada!</p>
                                        @endif
                                        @foreach ($atualizations as $key => $item)
                                            <div class="card mb-0 pt-2 shadow-none">
                                                <div class="accordion-header" id="heading{{ $key + 1 }}">
                                                    <h5 class="my-0">
                                                        <button
                                                        class="accordion-button{{ $key === 0 ? '' : ' collapsed' }}"
                                                        type="button"
                                                        data-bs-toggle="collapse"
                                                        data-bs-target="#collapse{{ $key + 1 }}"
                                                        aria-expanded="{{ $key === 0 ? 'true' : 'false' }}"
                                                        aria-controls="collapse{{ $key + 1 }}"
                                                        >
                                                        {!! $item->titulo !!} ({!! Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $item->criado)->format('d/m/Y') !!})
                                                        </button>
                                                    </h5>
                                                    @if($loggedUser->id == 39)
                                                    <div class="editAccordion">
                                                        <a href="{{route('Atualizacoes.excluir', ['id' => $item->id])}}" class="btn btn-outline-secondary btn-sm edit deleteBt" style="background-color: #a1a1a1" title="Excluir">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                        <a href="{{route('Atualizacoes.editar', ['id' => $item->id])}}" class="btn btn-outline-secondary btn-sm edit" style="background-color: #a1a1a1" title="Editar">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                    </div>
                                                    @endif
                                                </div>

                                                <div id="collapse{{ $key + 1 }}" class="collapse{{ $key === 0 ? ' show' : '' }}" aria-labelledby="heading{{ $key + 1 }}" data-bs-parent="#accordionExample">
                                                    <div class="card-body font-size-13">
                                                        {!! $item->descricao !!}
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
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
@endsection
