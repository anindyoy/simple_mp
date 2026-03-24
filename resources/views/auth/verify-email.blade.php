@extends('layouts.app')

@section('title', 'Verifikasi Email')

@section('content')
    <section class="container mx-auto max-w-3xl px-4 py-12">
        <div class="overflow-hidden rounded-2xl border border-blue-100 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="bg-linear-to-r from-blue-600 to-cyan-500 px-6 py-8 text-white">
                <p class="text-sm font-medium uppercase tracking-[0.2em] text-blue-100">Verifikasi Akun</p>
                <h1 class="mt-2 text-2xl font-semibold">Cek email Anda sebelum login</h1>
                <p class="mt-3 max-w-2xl text-sm text-blue-50">
                    Kami sudah mengirim link verifikasi ke <span class="font-semibold">{{ $email }}</span>.
                    Buka email tersebut lalu klik link verifikasi agar akun bisa digunakan untuk login.
                </p>
            </div>

            <div class="space-y-6 px-6 py-8">
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
                    Jika email belum masuk, periksa folder spam. Anda juga bisa kirim ulang email verifikasi di bawah ini.
                </div>

                <form method="POST" action="{{ route('verification.send') }}" class="space-y-4">
                    @csrf

                    <x-input
                        label="Email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        :value="$email"
                        pattern="^[^@\s]+@[^@\s]+\.[^@\s]+$"
                        title="Gunakan format email yang benar, contoh: nama@email.com" />

                    <button class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                        Kirim Ulang Email Verifikasi
                    </button>
                </form>
            </div>
        </div>
    </section>
@endsection