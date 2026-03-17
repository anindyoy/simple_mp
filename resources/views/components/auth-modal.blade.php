@props(['type'])

@php
    $isLogin = $type === 'login';
    $shouldOpenLoginModal = $isLogin && session('open_auth_modal') === 'login';
    $unverifiedEmail = $isLogin ? session('unverified_email') : null;
@endphp

<div id="{{ $type }}Modal"
    data-modal-target="{{ $type }}Modal"
    tabindex="-1"
    aria-hidden="true"
    class="modal-backdrop hidden fixed inset-0 z-50 bg-black/50">

    <div class="flex min-h-full items-center justify-center p-4">

        <div class="modal-content bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full p-6"
            style="width: min(88vw, {{ $isLogin ? '34rem' : '42rem' }});">
            <div class="flex justify-between items-center border-b pb-3 mb-4">
                <h3 class="text-lg font-semibold">
                    {{ $isLogin ? 'Login' : 'Daftar Akun' }}
                </h3>
                <button data-modal-hide="{{ $type }}Modal">✕</button>
            </div>

            <form method="POST"
                action="{{ $isLogin ? route('login.public') : route('register.public') }}"
                class="space-y-4">
                @csrf

                @if ($isLogin && $unverifiedEmail)
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                        <p>Akun ini belum terverifikasi. Silakan cek email Anda terlebih dahulu sebelum login.</p>
                        <form method="POST" action="{{ route('verification.send') }}">
                            @csrf
                            <input type="hidden" name="email" value="{{ $unverifiedEmail }}">

                            <button type="submit" class="underline text-amber-800">
                                Kirim ulang email verifikasi
                            </button>
                        </form>
                    </div>
                @endif

                @unless ($isLogin)
                    <x-input label="Nama Lengkap" name="name" autocomplete="name" />
                @endunless

                @if ($isLogin)
                    <x-input label="Email" name="email" type="email" autocomplete="email" />
                    <x-input
                        label="Password"
                        name="password"
                        type="password"
                        autocomplete="current-password" />
                @else
                    <x-input
                        label="Email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$"
                        title="Gunakan format email yang benar, contoh: nama@email.com" />

                    <div>
                        <x-input
                            label="Password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            minlength="8" />
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            Minimal 8 karakter
                        </p>
                    </div>
                @endif

                @unless ($isLogin)
                    <x-input
                        label="Konfirmasi Password"
                        name="password_confirmation"
                        type="password"
                        autocomplete="new-password"
                        minlength="8" />
                    <div class="mt-4 flex justify-center">
                        <div id="turnstile-register"></div>
                    </div>
                    @error('cf-turnstile-response')
                        <div class="text-sm text-red-500 mt-2">
                            {{ $message }}
                        </div>
                    @enderror
                @endunless

                <button class="w-full bg-blue-600 text-white py-2 rounded-lg">
                    {{ $isLogin ? 'Login' : 'Daftar' }}
                </button>
            </form>

            @unless ($isLogin)
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const registerModal = document.getElementById('registerModal');
                        if (!registerModal) return;

                        const form = registerModal.querySelector('form');
                        const passwordInput = form?.querySelector('input[name="password"]');
                        const confirmationInput = form?.querySelector('input[name="password_confirmation"]');

                        if (!form || !passwordInput || !confirmationInput) return;

                        const validatePasswordMatch = () => {
                            if (confirmationInput.value && confirmationInput.value !== passwordInput.value) {
                                confirmationInput.setCustomValidity('Konfirmasi password harus sama dengan password.');
                            } else {
                                confirmationInput.setCustomValidity('');
                            }
                        };

                        passwordInput.addEventListener('input', validatePasswordMatch);
                        confirmationInput.addEventListener('input', validatePasswordMatch);
                        form.addEventListener('submit', validatePasswordMatch);
                    });
                </script>
            @endunless

            @if ($shouldOpenLoginModal)
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const loginModal = document.getElementById('loginModal');

                        if (!loginModal) return;

                        loginModal.classList.remove('hidden');
                        loginModal.setAttribute('aria-hidden', 'false');
                    });
                </script>
            @endif
        </div>
    </div>
</div>
