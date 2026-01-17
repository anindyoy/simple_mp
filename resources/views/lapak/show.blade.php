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

        <div class="bg-indigo-50 p-6 rounded-2xl border border-indigo-100 shadow-sm mb-8">
            <div class="flex items-center gap-4 mb-6">
                <img
                    src="{{ $lapak->profile_image_url }}"
                    class="w-14 h-14 rounded-xl object-cover shadow"
                    alt="{{ $lapak->shop_name }}" />

                <div>
                    <h4 class="font-bold text-gray-900 text-lg flex items-center gap-1">
                        <x-heroicon-o-building-storefront class="w-4 h-4 text-indigo-500" />
                        {{ $lapak->shop_name }}
                    </h4>

                    <p class="text-sm text-gray-500 flex items-center gap-1">
                        <x-heroicon-o-map-pin class="w-4 h-4 text-red-500" />
                        {{ $lapak->address_raw }}
                    </p>

                    <p class="text-xs text-gray-400">
                        Bergabung sejak {{ $lapak->joined_at_label }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
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
            </div>
        </div>

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
