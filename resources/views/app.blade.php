@extends('layouts.app')

@section('content')
    @guest
        <primary-content></primary-content>
    @else
        <admin-content csrf-token="{{ csrf_token() }}" selected-database="{{ ($selectedDatabase ?: '') }}" :loaded-tables="{{ (isset($tables) && $tables ? $tables : '{}') }}"></admin-content>
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
