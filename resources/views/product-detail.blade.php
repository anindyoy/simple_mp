@extends('layouts.app')
@section('title', $meta['title'])
@section('meta_description', $meta['description'])
@section('meta_keywords', $meta['keywords'])

@section('og_title', $meta['title'])
@section('og_description', $meta['description'])
@section('og_type', 'product')
@section('og_image', $meta['image'] ?? asset('images/og-default.jpg'))

@section('content')
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <a href="{{ route('products.index') }}"
           class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-blue-600 transition-colors mb-4">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Kembali ke Beranda
        </a>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                @if ($product->getMedia('products')->count() === 1) {{-- Single Image (tanpa carousel) --}}
                    @php
                        $image = $product->getFirstMedia('products');
                        $imgUrl = $image->getUrl();
                    @endphp

                    <div class="w-full bg-gray-100 border rounded-base shadow-sm">
                        <img
                            src="{{ $imgUrl }}"
                            class="w-full h-auto"
                            alt="Gambar Produk {{ $product->title }}">
                    </div>
                @elseif ($product->getMedia('products')->count() > 1)
                    {{-- Carousel (jika > 1 foto) --}}
                    <div id="productImagesCarousel" class="relative w-full" data-carousel="slide">
                        <div class="relative aspect-square rounded-base bg-gray-100 shadow-sm border overflow-hidden">
                            @foreach ($product->getMedia('products') as $index => $image)
                                <div
                                    data-carousel-item="{{ $index === 0 ? 'active' : '' }}"
                                    class="{{ $index === 0 ? '' : 'hidden' }} duration-700 ease-in-out w-full h-full absolute inset-0">

                                    <img
                                        src="{{ $image->getUrl() }}"
                                        class="block w-full h-full object-contain p-2"
                                        alt="Gambar Produk {{ $product->title }}">
                                </div>
                            @endforeach
                        </div>

                        {{-- Indicators --}}
                        <div class="absolute z-30 flex -translate-x-1/2 bottom-3 left-1/2 space-x-3 rtl:space-x-reverse">
                            @foreach ($product->getMedia('products') as $i => $img)
                                <button
                                    type="button"
                                    class="w-3 h-3 rounded-full bg-white/50 hover:bg-white/80 transition-colors"
                                    data-carousel-slide-to="{{ $i }}"
                                    aria-label="Slide {{ $i + 1 }}"></button>
                            @endforeach
                        </div>

                        {{-- Controls --}}
                        <button type="button" class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group" data-carousel-prev>
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 group-hover:bg-white/50 transition-colors">
                                ‹
                            </span>
                        </button>
                        <button type="button" class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-4 cursor-pointer group" data-carousel-next>
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/30 group-hover:bg-white/50 transition-colors">
                                ›
                            </span>
                        </button>
                    </div>
                @endif
            </div>

            <div>
                <h1 class="text-4xl font-extrabold text-gray-900">{{ $product->title }}</h1>
                <p class="text-2xl font-bold text-blue-600 mt-2">Rp {{ number_format($product->price) }}</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    @if ($product->hasCondition())
                        <span
                            class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full
            {{ $product->condition === 'baru' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                            {{ $product->conditionLabel() }}
                        </span>
                    @endif

                    @if ($product->can_be_delivered)
                        <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
                            <x-heroicon-o-truck class="w-3 h-3" />
                            Bisa Diantar
                        </span>
                    @endif
                </div>

                <div class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                    <span class="inline-flex items-center gap-1">
                        <x-heroicon-o-eye class="w-3.5 h-3.5 text-gray-400" />
                        <span class="font-semibold text-gray-600">Dilihat:</span>
                        {{ number_format($product->views_count) }}x
                    </span>
                    <span>
                        <span class="font-semibold text-gray-600">Dibuat:</span>
                        {{ $product->created_at->diffForHumans() }}
                    </span>
                    @if ($product->pushed_at?->greaterThan($product->created_at))
                        <span>
                            <span class="font-semibold text-emerald-600">Diangkat:</span>
                            {{ $product->pushed_at->diffForHumans() }}
                        </span>
                    @endif
                </div>

                <div class="mt-5 flex items-center justify-between">
                    <button onclick="copyProductLink()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition-all active:scale-95">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                        </svg>
                        Bagikan
                    </button>

                    @if ($hasReported)
                        <span class="inline-flex items-center gap-1.5 text-sm text-gray-400 cursor-not-allowed">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            Anda sudah melaporkan ini
                        </span>
                    @else
                        <button
                            onclick="document.getElementById('reportProductModal').classList.remove('hidden')"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-red-600 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl shadow-sm transition-all active:scale-95">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4.5c-.77-.833-2.694-.833-3.464 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            Laporkan Produk
                        </button>
                    @endif
                </div>

                <div class="mt-5">
                    <h3 class="font-bold text-gray-500 uppercase text-xs">Deskripsi</h3>
                    <p class="text-gray-600 mt-2">{{ $product->description }}</p>
                </div>

                @include('partials.report-modal', [
                    'type' => 'product',
                    'id' => $product->id,
                    'title' => $product->title,
                ])

                {{-- Card Profil Penjual & Kontak --}}
                <div class="bg-indigo-50 p-6 rounded-2xl border border-indigo-100 shadow-sm mt-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-xl shadow-inner">
                            {{ strtoupper(substr($product->lapak->name, 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 text-lg">
                                <a href="{{ route('lapak.show', $product->lapak) }}"
                                    class="font-semibold text-blue-500 hover:underline">
                                    {{ $product->lapak->name }}
                                </a>
                            </h4>
                            <p class="text-sm text-gray-500 flex items-center gap-1">
                                <svg class="w-3 h-3 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $product->lapak->address_raw }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <p class="text-sm font-semibold text-gray-600 mb-2">Pesan Melalui:</p>
                        <div class="grid grid-cols-2 gap-4">
                            {{-- WhatsApp --}}
                            @if ($product->lapak->whatsapp_url)
                                <a href="{{ $product->lapak->whatsapp_url }}"
                                    target="_blank"
                                    class="flex justify-center items-center gap-2 px-6 py-3 text-white bg-green-500 hover:bg-green-600 font-bold rounded-xl shadow-lg transition-all active:scale-95">
                                    <x-fab-whatsapp class="w-5 h-5" />
                                    WhatsApp
                                </a>
                            @endif

                            {{-- Telegram --}}
                            @if ($product->lapak->telegram_url)
                                <a href="{{ $product->lapak->telegram_url }}"
                                    target="_blank"
                                    class="flex justify-center items-center gap-2 px-6 py-3 text-white bg-sky-500 hover:bg-sky-600 font-bold rounded-xl shadow-lg transition-all active:scale-95">
                                    <x-fab-telegram class="w-5 h-5" />
                                    Telegram
                                </a>
                            @endif
                        </div>
                    </div>

                    @if (filled($product->lapak->external_links) && is_array($product->lapak->external_links))
                        <div class="mt-6">
                            <p class="text-sm font-semibold text-gray-600 mb-2">Toko Online:</p>
                            <div class="grid grid-cols-2 gap-4">
                                @php
                                    $externalLinkCounter = 0;
                                @endphp
                                @foreach ($product->lapak->external_links as $externalLink)
                                    @if (!empty($externalLink['link']))
                                        @php
                                            $externalLinkCounter++;
                                        @endphp
                                        @php
                                            $linkLabel = $externalLink['custom_label']
                                                ?? $externalLink['label']
                                                ?? ('Toko Online ' . $externalLinkCounter);
                                        @endphp
                                        <a href="{{ $externalLink['link'] }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="flex justify-center items-center gap-2 px-6 py-3 text-indigo-700 bg-white hover:bg-indigo-50 border border-indigo-200 font-bold rounded-xl shadow-lg transition-all active:scale-95">
                                            {{ $linkLabel }}
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                </div>

                <div id="shareToast" class="fixed bottom-4 right-4 bg-gray-800 text-white text-sm px-4 py-2 rounded-lg shadow-lg opacity-0 transition-opacity duration-300 pointer-events-none z-50">
                    Tautan produk berhasil disalin!
                </div>

                @push('scripts')
                <script>
                    function copyProductLink() {
                        const url = window.location.href;
                        navigator.clipboard.writeText(url).then(() => {
                            const toast = document.getElementById('shareToast');
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
            </div>
        </div>

        <section class="mt-12">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-2xl font-bold text-gray-900">Produk Lainnya di Lapak Ini</h2>
                <a href="{{ route('lapak.show', $product->lapak) }}" class="text-sm font-semibold text-blue-600 hover:underline">
                    Lihat Semua
                </a>
            </div>

            @if ($otherProductsInLapak->isNotEmpty())
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                    @foreach ($otherProductsInLapak as $otherProduct)
                        @include('components.product-card', [
                            'product' => $otherProduct,
                        ])
                    @endforeach
                </div>
            @else
                <div class="rounded-2xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500">
                    Belum ada produk lain di lapak ini.
                </div>
            @endif
        </section>
    </div>
@endsection
