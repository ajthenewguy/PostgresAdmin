<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

    <link href="data:image/x-icon;base64,AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAABILAAASCwAAAAAAAAAAAAAAAAAAAAAAAisrK0ShoaGHz8/Ptd7e3tjj4+Pu3t7e+djY2PfQ0NDqxcXF0q+vr7CBgYGCAAAAOQAAAAEAAAAAAAAAAL+/v6z///////////////////////////////////////////////////////////39/f+AgICGAAAAAAAAAAD////v///////////////////////////////////////////////////////////8/P3/r6+wzQAAAAAAAAAA////7////////////////////////////////////////////////////////////////8LCw80AAAAAAAAAAP///+z/////+fn5/8HBwf+Ojo7/hISE/4aGhv+Ghob/hYWF/5KSkv+7u7v/2NjY//Hx8v/ExMXAAAAAAAAAAABcXFyDurq6/+vr6///////////////////////////////////////5+fn/8PDw/+cnJz+JCQkVQAAAAAAAAAA9/f32f///////////////////////////////////////////////////////////////62trbIAAAAAAAAAAP///+////////////////////////////////////////////////////////////39//+ysrTQAAAAAAAAAAD////v////////////////////////////////////////////////////////////////zs7PyQAAAAAAAAAA+Pj43f////+cnJz/k5OT/6Wlpf+xsbH/tLS0/7Kysv+urq7/oqKi/4yMjP+Xl5f/2NjY/56en64AAAAAAAAAAGRkZIH19fX//////////////////////////////////////////////////////7m5uv8zMzNXAAAAAAAAAAD////s///////////////////////////////////////////////////////////5+fr/tLS1xgAAAAAAAAAA////8f///////////////////////////////////////////////////////////////6mpqtAAAAAAAAAAAP////H////////////////////////////////////////////////////////////////AwMHHAAAAAAAAAAC2traz///////////////////////////////////////////////////////////////+d3d4ggAAAAAAAAAAAAAAAxMTE0mLi4uOsLCwv6mpqd+oqKjxpaWl/KOjo/udnZ7wlZWV3IWFhrpzc3ODKCgoNQEBAQAAAAAAgAEAAIABAACAAQAAgAEAAIABAACAAQAAgAEAAIABAACAAQAAgAEAAIABAACAAQAAgAEAAIABAACAAQAAgAEAAA==" rel="icon" type="image/x-icon">
</head>
<body>
    <div id="axiosConfig"
         baseUrl="{{url('/')}}"
         accessToken="{{Session::get('access_token')}}"
    ></div>
    <div id="app">
        <nav class="navbar navbar-inverse navbar-fixed-top">
            <div class="container-fluid">
                <div class="navbar-header">

                    <!-- Collapsed Hamburger -->
                    <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#app-navbar-collapse">
                        <span class="sr-only">Toggle Navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>

                    <!-- Branding Image -->
                    <a class="navbar-brand" href="{{ url('/') }}">
                        {{ config('app.name', 'Laravel') }}
                    </a>
                </div>

                <div class="collapse navbar-collapse" id="app-navbar-collapse">
                    <!-- Left Side Of Navbar -->
                    <ul class="nav navbar-nav">
                        &nbsp;
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="nav navbar-nav navbar-right">
                        <!-- Authentication Links -->
                        @guest
                            <li><a href="{{ route('login') }}">Login</a></li>
                            <li><a href="{{ route('register') }}">Register</a></li>
                        @else

                            @yield('nav-right')

                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-expanded="false">
                                    {{ Auth::user()->name }} <span class="caret"></span>
                                </a>

                                <ul class="dropdown-menu" role="menu">
                                    <li>
                                        <a href="{{ route('logout') }}"
                                            onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                            Logout
                                        </a>

                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                            {{ csrf_field() }}
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endguest
                    </ul>

                </div>
            </div>
        </nav>

        @yield('content')
    </div>

    <!-- Scripts -->
    @yield('js')
</body>
</html>
