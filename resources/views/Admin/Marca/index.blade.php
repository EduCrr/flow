    @extends('layouts.admin')
    @section('title', 'Marcas')

    @section('css')
    @endsection

    @section('content')

        <section>
            <div class="main-content">
                <div class="page-content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="changeDem">
                                            <div>
                                                <h5 class="card-title">Escolha a marcas</h5>
                                            </div>
                                            <div class="general-label">
                                                <form class="row row-cols-lg-auto g-3 align-items-center" method="GET">
                                                    <div class="col-12">
                                                        <div class="mb-0">
                                                            <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Pesquisar">
                                                        </div>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="mb-0">
                                                            <a href="{{route('Admin.marcas')}}" class="btn btn-danger ">Limpar</a>
                                                            <button type="submit" class="btn btn-primary ">Pesquisar</button>
                                                        </div>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-12">
                                                <div class="table-responsive" >
                                                    <table class="table table-hover table-centered table-nowrap mb-0">
                                                        @if(count($marcas) === 0)
                                                        <p>Nenhuma marca foi encontrado!</p>
                                                        @else
                                                        <thead>
                                                            <tr>
                                                                <th>Nome</th>
                                                                <th style="display: flex;justify-content: flex-end;">Ações</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($marcas as $marca )
                                                                <tr class="trLink"  data-href="{{route('Admin.marca_editar', ['id' => $marca->id])}}" style="cursor: pointer;"  @if($loggedUser->tipo != 'ghost')  @endif>
                                                                    <td><span style="padding: 5px; margin-right:5px; border-radius:4px; color:white; background: {{ $marca->cor }}">{{ $marca->nome }}</span></td>
                                                                    @if($loggedUser->tipo != 'ghost')
                                                                    <td style="display: flex;justify-content: flex-end;">
                                                                        <a href="{{route('Admin.marca_editar', ['id' => $marca->id])}}" class="btn btn-outline-secondary btn-sm"  style="background-color: #a1a1a1" title="Info">
                                                                            <i class="fas fa-search"></i>
                                                                        </a>
                                                                        <a href="{{route('Admin.marca_delete_action', ['id' => $marca->id])}}" class="btn btn-outline-secondary btn-sm deleteBt btnDanger"  style="background-color: #a1a1a1; margin-left: 5px" title="Deletar">
                                                                            <i class="fas fa-trash"></i>
                                                                        </a>
                                                                    </td>
                                                                    @endif
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                        @endif
                                                    </table>
                                                </div>
                                                <!--end table-responsive-->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-primary">
                                        <div>{{$marcas->links("pagination::bootstrap-4")}}</div>
                                    </div>
                                </div>
                            </div>
                            <!-- end col -->
                        </div>
                    </div>
                </div>
            </div>

        </section>
    @endsection


    @section('plugins')
    @endsection

    @section('scripts')
        <script src="{{ asset('assets/js/select2.js') }}" ></script>
    @endsection


