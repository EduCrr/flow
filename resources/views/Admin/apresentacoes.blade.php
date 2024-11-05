@extends('layouts.admin')
@section('title', 'Apresentações')

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
                                            <h5 class="card-title">Marcas</h5>
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
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($marcas as $marca )
                                                            <tr style="cursor: pointer;" class="trLink" style="cursor: pointer;" data-href="{{route('Admin.apresentacoes-marca', ['id' => $marca->id])}}">
                                                                <td><span style="padding: 5px; margin-right:5px; border-radius:4px; color:white; background: {{ $marca->cor }}">{{ $marca->nome }}</span></td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    @endif
                                                </table>
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
        
    </section>
@endsection

@section('plugins')
@endsection

@section('scripts')
    <script src="{{ asset('assets/js/select2.js') }}" ></script>
@endsection


