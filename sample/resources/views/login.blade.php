<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles / Scripts -->
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="font-sans antialiased dark:bg-black dark:text-white/50">
        <p>Logged user: {{ auth()->user()?->name ?? 'Guest' }}</p>
        <p>Is using octane? {{ ($_SERVER['LARAVEL_OCTANE'] ?? null) ? 'Yes' : 'No'  }}</p>
        <p>
            Current provider URL: {{ config('oidc.provider_url') }} <br>
            <br>
            <a href="{{ url()->current() . "?switch_provider" }}">Change</a>
        </p>

        <a href="{{ str_replace(
            ["oidc:3000", "oidc2:3000"],
            ["http://oidc.localhost:3000", "http://oidc2.localhost:3002"],
            auth()->guard()->getAuthorizationUrl()
        ) }}">LOG IN</a>
    </body>
</html>
