<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Grade Horários') }} — UniSENAI MT</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>

        <!-- Bootstrap CSS + Icons (apenas uma vez) -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

        <!-- Livewire Styles -->
        @livewireStyles
    </head>
    <body class="font-sans antialiased" style="background:#f4f4f4">

        <div class="min-vh-100">
            @include('layouts.navigation')

            <!-- Page Content -->
            <main class="py-3">
                <div class="container-fluid px-4">
                    {{ $slot }}
                </div>
            </main>
        </div>

        <!-- Bootstrap JS (apenas uma vez, no final) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

        <!-- Livewire Scripts -->
        @livewireScripts
    </body>
</html>
