<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('img/favicon-32x32.png') }}">

    <title>@php
        $siteTitle = App\Models\Setting::getValue('site_title', 'Lapak Warga');
        $defaultRegion = App\Models\Setting::getValue('site_region', 'Cimanglid');
    @endphp@yield('title', $siteTitle . ' ' . $defaultRegion)</title>

    @include('partials.meta')
    {{-- @include('whatsapp-widget::whatsapp-widget-head') --}}

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-gray-50 dark:bg-gray-900">

    @include('partials.navbar', ['region' => $defaultRegion, 'siteTitle' => $siteTitle])
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

    @include('partials.footer', ['region' => $defaultRegion, 'siteTitle' => $siteTitle])

    {{-- @include('whatsapp-widget::whatsapp-widget-body') --}}

    @stack('scripts')

    @livewireScripts

    @if (! app()->environment('local') && filled(config('services.turnstile.site_key')))
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" async defer></script>

        <script>
            let turnstileWidgetId = null;
            const turnstileSiteKey = '{{ config('services.turnstile.site_key') }}';

            function logTurnstileDebug(event, payload = {}) {
                console.info('[Turnstile]', event, {
                    host: window.location.hostname,
                    origin: window.location.origin,
                    siteKeyPreview: turnstileSiteKey ? `${turnstileSiteKey.slice(0, 6)}...` : null,
                    ...payload,
                });
            }

            function renderTurnstile() {
                if (turnstileWidgetId !== null || typeof turnstile === 'undefined') return;

                const container = document.getElementById('turnstile-register');
                if (!container) return;

                logTurnstileDebug('render:start');

                turnstileWidgetId = turnstile.render('#turnstile-register', {
                    sitekey: turnstileSiteKey,
                    callback: function(token) {
                        logTurnstileDebug('token:received', {
                            tokenLength: token ? token.length : 0,
                        });
                    },
                    'error-callback': function(code) {
                        console.error('[Turnstile] widget error', {
                            code,
                            host: window.location.hostname,
                            origin: window.location.origin,
                        });

                        fetch('{{ route('turnstile.client-error') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({
                                code,
                                origin: window.location.origin,
                                hostname: window.location.hostname,
                                href: window.location.href,
                                sitekey_preview: turnstileSiteKey ? `${turnstileSiteKey.slice(0, 6)}...` : null,
                            }),
                            credentials: 'same-origin',
                            keepalive: true,
                        }).catch(() => {});
                    },
                });

                logTurnstileDebug('render:done', {
                    widgetId: turnstileWidgetId,
                });
            }

            document.addEventListener('click', function(e) {
                if (e.target.closest('[data-modal-target="registerModal"]')) {
                    setTimeout(() => {
                        renderTurnstile();
                    }, 300);
                }
            });
        </script>
    @endif
</body>

</html>
