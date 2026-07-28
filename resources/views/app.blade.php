<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        {{--
            Tema y superficie se fijan aquí, antes de que Vue monte, para
            que no haya un parpadeo con el tema por defecto (docs/04
            §1 y §3). Fuentes autoalojadas en public/fonts/ vía @font-face
            en resources/css/app.css — nunca un CDN externo: registraría la
            IP de los participantes (docs/06-SEGURIDAD.md §7).
        --}}
        <script nonce="{{ $cspNonce }}">
            (function () {
                var theme = localStorage.getItem('epycus.theme')
                    || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
                var surface = localStorage.getItem('epycus.surface') || 'neumorphism';
                document.documentElement.setAttribute('data-theme', theme);
                document.documentElement.setAttribute('data-surface', surface);
            })();
        </script>

        <!-- Scripts -->
        @routes(nonce: $cspNonce)
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
