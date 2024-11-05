@extends('layouts.admin8')
@section('title', 'Adicionar marca')

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
                                    <div class="init">
                                         <h5 class="card-title">Adicione uma marca</h5>
                                        <a href="{{ route('Admin.marcas') }}" class="btnBack btn btn-primary">Voltar</a>
                                    </div>
                                    <form style="margin-top: 15px" method="POST" action="{{route('Admin.marca_adicionar')}}" enctype="multipart/form-data" class="needs-validation responseAjax" novalidate>
                                        @csrf
                                        <div class="mb-3 row">
                                            <div class="col-lg-12  mo-b-15">
                                               <label for="inputT" class="form-label pt-0">Nome da marca</label>
                                               <input name="nome" required value="{{ old('nome') }}" class="form-control" type="text"  id="inputT">
                                                <div class="invalid-feedback">
                                                    Preencha o campo nome
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3 row">
                                            <div class="col-lg-12  mo-b-15">
                                               <label for="inputF" class="form-label pt-0">Logo</label>
                                                <input
                                                    id="inputF"
                                                    type="file"
                                                    class="form-control"
                                                    name="logo"
                                                    accept="image/png, image/jpeg, image/jpg"
                                                />
                                            </div>
                                        </div>
                                         <div class="mb-3 row">
                                            <div class="col-lg-12  mo-b-15">
                                              <label for="Color" class="form-label">Cor da marca</label>
                                                <input type="color" required class="form-control form-control-color" id="Color" name="cor" value="#222">
                                                <div class="invalid-feedback">
                                                    Preencha o campo cor
                                                </div>
                                            </div>
                                        </div>
                                        <br/>
                                        <button type="submit" class="btn btn-primary w-lg leftAuto">Criar marca</button>
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
@endsection
