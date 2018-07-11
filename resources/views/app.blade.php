@extends('layouts.app')

@section('content')
    @guest
        <div class="container">
            <div class="row">
                <div class="col-md-8 col-md-offset-2">
                    <div class="panel panel-default">
                        <div class="panel-heading">Welcome</div>

                        <div class="panel-body">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <page-content csrf-token="{{ csrf_token() }}" selected-database="{{ ($selectedDatabase ?: '') }}" :loaded-tables="{{ (isset($tables) && $tables ? $tables : '{}') }}"></page-content>
    @endguest
@endsection

@section('nav-right')
    @guest
    @else
        <database-connections default-connection="{{ $selectedDatabase }}"></database-connections>
    @endguest
@endsection

@section('js')
    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
    </script>
    <script src="{{ mix('js/app.js') }} "></script>
@stop
