@extends('layouts.app')

@section('title', $product->title . ' - Jual Beli Cimanglid')

@section('content')
    {{-- SEMUA KODE DETAIL HARUS DI SINI --}}
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                <div class="rounded-2xl overflow-hidden bg-white shadow-sm border">
                    @php $primaryImage = $product->images->where('is_primary', true)->first() ?? $product->images->first(); @endphp
                    <img src="{{ Str::startsWith($primaryImage->image_url, 'http') ? $primaryImage->image_url : asset('storage/' . $primaryImage->image_url) }}"
                        class="w-full h-96 object-contain bg-gray-50">
                </div>
            </div>

            <div>
                <h1 class="text-4xl font-extrabold text-gray-900">{{ $product->title }}</h1>
                <p class="text-2xl font-bold text-blue-600 mt-2">Rp {{ number_format($product->price, 0, ',', '.') }}</p>
                <div class="mt-6">
                    <h3 class="font-bold text-gray-500 uppercase text-xs">Deskripsi</h3>
                    <p class="text-gray-600 mt-2">{{ $product->description }}</p>
                </div>

                {{-- Tombol WA --}}
                {{-- Card Profil Penjual & Kontak --}}
                <div class="bg-indigo-50 p-6 rounded-2xl border border-indigo-100 shadow-sm mt-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 bg-indigo-600 rounded-full flex items-center justify-center text-white font-bold text-xl shadow-inner">
                            {{ strtoupper(substr($product->lapak->shop_name, 0, 1)) }}
                        </div>
                        <div>
                            <h4 class="font-bold text-gray-900 text-lg">
                                <a href="{{ route('lapak.show', $product->lapak_id) }}"
                                    class="font-semibold text-blue-500 hover:underline">
                                    {{ $product->lapak->shop_name }}
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
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                </div>
            </div>
        </div>
    </div>
@endsection
