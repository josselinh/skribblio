<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Skribbl</title>

    <!-- Scripts -->
    <script src="{{ asset('js/app.js') }}" defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/skribbl.css') }}" rel="stylesheet">
</head>
<body>
<div id="app">
    <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="{{ url('/') }}">
                Skribbl.io <sup>tools</sup>
            </a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent"
                    aria-controls="navbarSupportedContent" aria-expanded="false"
                    aria-label="{{ __('menu.toggle_navigation') }}">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <!-- Left Side Of Navbar -->
                @auth
                    <ul class="navbar-nav mr-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="menuDropdownSentences" role="button"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('menu.sentences.root') }}
                            </a>
                            <div class="dropdown-menu" aria-labelledby="menuDropdownSentences">
                                <a class="dropdown-item"
                                   href="{{ route('sentence.index') }}">{{ __('menu.sentences.index') }}</a>
                                <a class="dropdown-item" href="{{ route('sentence.add') }}">{{ __('menu.sentences.add') }}</a>
                                @can('sentence-import')
                                    <a class="dropdown-item"
                                       href="{{ route('sentence.import') }}">{{ __('menu.sentences.import') }}</a>
                                @endcan
                                <a class="dropdown-item"
                                   href="{{ route('sentence.export') }}">{{ __('menu.sentences.export') }}</a>
                            </div>
                        </li>

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="menuDropdownGroups" role="button"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('menu.groups.root') }}
                            </a>
                            <div class="dropdown-menu" aria-labelledby="menuDropdownGroups">
                                <a class="dropdown-item"
                                   href="{{ route('group.index') }}">{{ __('menu.groups.index') }}</a>
                                <a class="dropdown-item" href="{{ route('group.add') }}">{{ __('menu.groups.add') }}</a>
                            </div>
                        </li>
                    </ul>
            @endauth

            <!-- Right Side Of Navbar -->
                <ul class="navbar-nav ml-auto">
                    <!-- Authentication Links -->
                    @guest
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('login') }}">{{ __('menu.login') }}</a>
                        </li>
                        @if (Route::has('register'))
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">{{ __('menu.register') }}</a>
                            </li>
                        @endif
                    @else
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button"
                               data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                {{ Auth::user()->name }} <span class="caret"></span>
                            </a>

                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('logout') }}"
                                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    {{ __('menu.logout') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST"
                                      style="display: none;">
                                    @csrf
                                </form>
                            </div>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>

    <main class="py-4">
        <div class="container">
            @if(session('alert-primary'))
                <div class="alert alert-primary" role="alert">
                    {{ session('alert-primary') }}
                </div>
            @endif

            @if(session('alert-secondary'))
                <div class="alert alert-secondary" role="alert">
                    {{ session('alert-secondary') }}
                </div>
            @endif

            @if(session('alert-success'))
                <div class="alert alert-success" role="alert">
                    {{ session('alert-success') }}
                </div>
            @endif

            @if(session('alert-danger'))
                <div class="alert alert-danger" role="alert">
                    {{ session('alert-danger') }}
                </div>
            @endif

            @if(session('alert-warning'))
                <div class="alert alert-warning" role="alert">
                    {{ session('alert-warning') }}
                </div>
            @endif

            @if(session('alert-info'))
                <div class="alert alert-info" role="alert">
                    {{ session('alert-info') }}
                </div>
            @endif

            @if(session('alert-light'))
                <div class="alert alert-light" role="alert">
                    {{ session('alert-light') }}
                </div>
            @endif

            @if(session('alert-dark'))
                <div class="alert alert-dark" role="alert">
                    {{ session('alert-dark') }}
                </div>
            @endif
        </div>

        @yield('content')
    </main>
</div>
</body>
</html>
