@props(['type'])

@php
    $isLogin = $type === 'login';
@endphp

<div id="{{ $type }}Modal"
    data-modal-target="{{ $type }}Modal"
    tabindex="-1"
    aria-hidden="true"
    class="modal-backdrop hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center">

    <div class="modal-content bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-md p-4">
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

            @unless ($isLogin)
                <x-input label="Nama Lengkap" name="name" autocomplete="name" />
            @endunless

            <x-input label="Email" name="email" type="email" autocomplete="email" />
            <x-input
                label="Password"
                name="password"
                type="password"
                autocomplete="current-password" />

            @unless ($isLogin)
                <x-input label="Konfirmasi Password" name="password_confirmation" type="password" autocomplete="new-password" />
            @endunless

            <button class="w-full bg-blue-600 text-white py-2 rounded-lg">
                {{ $isLogin ? 'Login' : 'Daftar' }}
            </button>
        </form>
    </div>
</div>
