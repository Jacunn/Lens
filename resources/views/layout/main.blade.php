<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Lens</title>

        {{-- Stylesheets --}}
        <link rel="stylesheet" href="/vendor/bootstrap/css/bootstrap.css">
        <link rel="stylesheet" href="/vendor/datatables/datatables.css">
        
        @stack('css')

        {{-- Scripts --}}
        <script src="/vendor/jquery/jquery.min.js"></script>
        <script src="/vendor/bootstrap/js/bootstrap.js"></script>
        <script src="/vendor/datatables/datatables.js"></script>
        
        @stack('js')
    </head>
    <body class="antialiased bg-standard">
        {{-- Header inclusion --}}

        {{-- Main content inclusion --}}
        <div class="container-xl mx-auto mt-5">
            @yield('content')
        </div>

        {{-- Footer inclusion --}}
    </body>
</html>
