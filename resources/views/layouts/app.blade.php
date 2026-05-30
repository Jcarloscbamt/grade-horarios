<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name', 'Grade Horários') }} — UniSENAI MT</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
        @livewireStyles
        <script>
            // Restaura classe do body ANTES de renderizar (evita salto visual)
            if (localStorage.getItem('sidebar_collapsed') === 'true') {
                document.documentElement.classList.add('sidebar-collapsed-preload');
            }
        </script>
        <style>
            /* Aplica margem imediatamente antes do JS carregar */
            html.sidebar-collapsed-preload .main-wrapper { margin-left: 60px; }
        </style>
    </head>
    <body class="font-sans antialiased" style="background:#f4f4f4">

        @include('layouts.navigation')

        <!-- Conteúdo principal com offset da sidebar -->
        <main class="main-wrapper">
            {{ $slot }}
        </main>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
        @livewireScripts
    </body>
</html>
