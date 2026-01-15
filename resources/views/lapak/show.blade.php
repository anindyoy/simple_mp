@extends('layouts.app')
@section('title', 'Lapak ' . $lapak->shop_name . ' - Jual Beli Cimanglid')
@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- HEADER LAPAK --}}
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow p-6 mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">
                    {{ $lapak->shop_name }}
                </h1>

                <p class="text-sm text-gray-500 mt-1">
                    {{ $lapak->address_raw }}
                </p>

                <div class="flex gap-3 mt-3 text-sm">
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $lapak->whatsapp_number) }}"
                       target="_blank"
                       class="px-4 py-2 bg-green-500 text-white rounded-xl font-semibold hover:bg-green-600">
                        WhatsApp
                    </a>

                    @if($lapak->telegram_username)
                        <a href="https://t.me/{{ $lapak->telegram_username }}"
                           target="_blank"
                           class="px-4 py-2 bg-blue-500 text-white rounded-xl font-semibold hover:bg-blue-600">
                            Telegram
                        </a>
                    @endif
                </div>
            </div>

            <div class="text-sm text-gray-400">
                Bergabung sejak {{ $lapak->created_at->format('d M Y') }}
            </div>
        </div>
    </div>

    {{-- LIST PRODUK --}}
    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">
        Produk dari Lapak ini
    </h2>

    @if($lapak->products->count())
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            @foreach($lapak->products as $product)
                <x-product-card :product="$product" />
            @endforeach
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 p-6 rounded-xl text-center text-gray-500">
            Lapak ini belum memiliki produk 😢
        </div>
    @endif

</div>
@endsection
