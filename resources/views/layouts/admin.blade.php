<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Juru Giling')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>


<body>

    @include('partials.navbar')

    <div class="container-fluid">
        <div class="row">

            <div class="col-md-2">
                @include('partials.sidebar')
            </div>

            <div class="col-md-10 p-4">
                
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"
                        aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"
                        aria-label="Close"></button>
            </div>
        @endif

            @yield('content')
            </div>

        </div>
    </div>

    @include('partials.footer')
    @stack('scripts')

</body>
</html>