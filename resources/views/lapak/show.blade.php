@extends('layouts.app')
@section('title', $meta['title'])
@section('meta_description', $meta['description'])
@section('meta_keywords', $meta['keywords'])

@section('og_title', $meta['title'])
@section('og_description', $meta['description'])
@section('og_type', 'profile')
@section('og_image', $meta['image'] ?? asset('images/og-default.jpg'))

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-6">
        <a href="{{ route('products.index') }}"
           class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-blue-600 transition-colors mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke Beranda
        </a>

        <div class="bg-indigo-50 p-6 rounded-2xl border border-indigo-100 shadow-sm mb-8">
            <div class="flex items-center gap-4 mb-6">
                <img
                    src="{{ $lapak->profile_image_url }}"
                    class="w-14 h-14 rounded-xl object-cover shadow"
                    alt="{{ $lapak->name }}" />

                <div>
                    <h4 class="font-bold text-gray-900 text-lg flex items-center gap-1">
                        <x-heroicon-o-building-storefront class="w-4 h-4 text-indigo-500" />
                        {{ $lapak->name }}
                    </h4>

                    <p class="text-sm text-gray-500 flex items-center gap-1">
                        <x-heroicon-o-map-pin class="w-4 h-4 text-red-500" />
                        {{ $lapak->address_raw }}
                    </p>

                    <p class="text-xs text-gray-400">
                        Bergabung sejak {{ $lapak->joined_at_label }}
                    </p>

                    @if ($lapak->can_be_delivered)
                        <p class="text-xs font-semibold text-blue-600 flex items-center gap-1 mt-1">
                            <x-heroicon-o-truck class="w-3 h-3" />
                            Melayani Pengiriman
                        </p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <button onclick="copyLapakLink()"
                        class="flex justify-center items-center gap-2 px-6 py-3 text-white bg-blue-600 hover:bg-blue-700 font-bold rounded-xl shadow-lg transition-all active:scale-95">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                    </svg>
                    Bagikan
                </button>

                {{-- WhatsApp --}}
                @if ($lapak->whatsapp_url)
                    <a href="{{ $lapak->whatsapp_url }}"
                        target="_blank"
                        class="flex justify-center items-center gap-2 px-6 py-3
                      text-white bg-green-500 hover:bg-green-600
                      font-bold rounded-xl shadow-lg transition-all active:scale-95">
                        <x-fab-whatsapp class="w-5 h-5" />
                        WhatsApp
                    </a>
                @endif

                {{-- Telegram --}}
                @if ($lapak->telegram_url)
                    <a href="{{ $lapak->telegram_url }}"
                        target="_blank"
                        class="flex justify-center items-center gap-2 px-6 py-3
                      text-white bg-sky-500 hover:bg-sky-600
                      font-bold rounded-xl shadow-lg transition-all active:scale-95">
                        <x-fab-telegram class="w-5 h-5" />
                        Telegram
                    </a>
                @endif

                @if (! empty($externalLinks))
                    @foreach ($externalLinks as $externalLink)
                        <a href="{{ $externalLink['link'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="flex justify-center items-center gap-2 px-6 py-3
                          text-indigo-700 bg-white hover:bg-indigo-50 border border-indigo-200
                          font-bold rounded-xl shadow-lg transition-all active:scale-95">
                            {{ $externalLink['label'] }}
                        </a>
                    @endforeach
                @endif
            </div>

            <div class="mt-4">
                @if ($hasReported)
                    <span class="text-sm text-gray-400 cursor-not-allowed">
                        Anda sudah melaporkan ini
                    </span>
                @else
                    <button
                        onclick="document.getElementById('reportLapakModal').classList.remove('hidden')"
                        class="text-sm text-red-500 hover:underline">
                        Laporkan Toko
                    </button>
                @endif
            </div>

            @include('partials.report-modal', [
                'type' => 'lapak',
                'id' => $lapak->id,
                'title' => $lapak->name,
            ])
        </div>

        <div id="shareLapakToast" class="fixed bottom-4 right-4 bg-gray-800 text-white text-sm px-4 py-2 rounded-lg shadow-lg opacity-0 transition-opacity duration-300 pointer-events-none z-50">
            Tautan lapak berhasil disalin!
        </div>

        @push('scripts')
        <script>
            function copyLapakLink() {
                const url = window.location.href;
                navigator.clipboard.writeText(url).then(() => {
                    const toast = document.getElementById('shareLapakToast');
                    toast.classList.remove('opacity-0');
                    toast.classList.add('opacity-100');
                    setTimeout(() => {
                        toast.classList.remove('opacity-100');
                        toast.classList.add('opacity-0');
                    }, 2500);
                }).catch(() => {
                    alert('Gagal menyalin tautan. Silakan salin URL secara manual.');
                });
            }
        </script>
        @endpush

        <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">
            Produk dari Lapak ini
        </h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach ($lapak->products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>

    </div>

@endsection
