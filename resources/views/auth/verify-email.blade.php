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

                <div class="border-t border-gray-100 pt-4 dark:border-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Masih kesulitan memverifikasi akun?
                    </p>
                    <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">
                        @if (filled($whatsapp))
                            <a href="https://wa.me/{{ preg_replace('/\D/', '', $whatsapp) }}?text={{ urlencode('Halo admin, saya kesulitan memverifikasi akun saya dengan email: ' . $email) }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="inline-flex items-center gap-1.5 font-medium text-green-600 hover:underline dark:text-green-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/>
                                </svg>
                                Hubungi Admin via WhatsApp
                            </a>
                        @else
                            Silakan hubungi admin untuk mendapatkan bantuan.
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection