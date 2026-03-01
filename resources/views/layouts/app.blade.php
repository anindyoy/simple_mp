<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Jual Beli Cimanglid')</title>

    @include('partials.meta')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.css">
</head>

<body class="bg-gray-50 dark:bg-gray-900">

    @include('partials.navbar')
    @if (session('success'))
        <div class="max-w-6xl mx-auto mt-4 p-3 bg-green-100 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    @guest
        <x-auth-modal type="login" />
        <x-auth-modal type="register" />
    @endguest

    @include('partials.footer')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.2.1/flowbite.min.js"></script>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>

    <script>
        let turnstileWidgetId = null;

        function renderTurnstile() {
            if (turnstileWidgetId !== null) return;

            turnstileWidgetId = turnstile.render('#turnstile-register', {
                sitekey: '{{ config('services.turnstile.site_key') }}',
            });
        }

        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-modal-target="registerModal"]')) {
                setTimeout(() => {
                    renderTurnstile();
                }, 300);
            }
        });
    </script>
</body>

</html>
