@extends('layouts.app')

@section('content')
    @guest
        <primary-content></primary-content>
    @else
        <admin-content selected-database="{{ $selectedDatabase }}" :loaded-tables="{{ (isset($tables) ? $tables : '') }}"></admin-content>
    @endguest
@endsection

@section('nav-right')
    @guest
    @else
        <form class="navbar-form navbar-right">
            <div class="form-group">
                <database-switcher :databases="{{ (isset($databases) ? $databases : '') }}" selected-database="{{ $selectedDatabase }}"></database-switcher>
            </div>
        </form>
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

