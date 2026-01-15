tampilkan tombol wa / telegram di detail lapak, seperti pada product detail

resources\views\product-detail.blade.php
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

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Tombol WhatsApp: Muncul jika whatsapp_number tidak kosong --}}
                        @if ($product->lapak->whatsapp_number)
                            @php
                                // Bersihkan nomor WA (hapus spasi, plus, atau dash)
                                $waNumber = preg_replace('/[^0-9]/', '', $product->lapak->whatsapp_number);
                                // Pastikan format diawali 62
                                if (str_starts_with($waNumber, '08')) {
                                    $waNumber = '628' . substr($waNumber, 2);
                                }
                                $waMessage = 'Halo, saya tertarik dengan produk *' . $product->title . '* yang saya lihat di Jual Beli Cimanglid. Apakah masih ada?';
                            @endphp
                            <a href="https://wa.me/{{ $waNumber }}?text={{ urlencode($waMessage) }}"
                                target="_blank"
                                class="flex justify-center items-center gap-2 px-6 py-3 text-white bg-green-500 hover:bg-green-600 font-bold rounded-xl shadow-lg transition-all active:scale-95">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.662c1.72.937 3.672 1.433 5.661 1.433h.05c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z" />
                                </svg>
                                WhatsApp
                            </a>
                        @endif

                        {{-- Tombol Telegram: Muncul jika telegram_username tidak kosong --}}
                        @if ($product->lapak->telegram_username)
                            @php
                                // Bersihkan @ jika user menginputnya
                                $tgUser = ltrim($product->lapak->telegram_username, '@');
                            @endphp
                            <a href="https://t.me/{{ $tgUser }}"
                                target="_blank"
                                class="flex justify-center items-center gap-2 px-6 py-3 text-white bg-sky-500 hover:bg-sky-600 font-bold rounded-xl shadow-lg transition-all active:scale-95">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M11.944 0C5.346 0 0 5.346 0 11.944s5.346 11.944 11.944 11.944 11.944-5.346 11.944-11.944S18.542 0 11.944 0zm5.206 8.19c-.15 1.585-.805 5.456-1.134 7.215-.14.743-.41 1.04-.675 1.065-.575.054-1.01-.38-1.565-.745-.875-.575-1.37-.93-2.215-1.485-.98-.64-.345-1 .215-1.575.145-.15 2.68-2.455 2.73-2.665.005-.025.01-.12-.055-.18-.065-.055-.16-.035-.23-.02-.1.025-1.69 1.075-4.77 3.155-.45.31-.855.46-1.22.45-.4-.01-1.17-.225-1.745-.41-.705-.225-1.265-.345-1.215-.73.025-.2.295-.405.815-.61 3.19-1.385 5.315-2.3 6.375-2.73 3.04-1.24 3.67-1.455 4.08-1.46.09 0 .29.02.42.125.11.09.14.21.15.3.01.07.02.15 0 .22z" />
                                </svg>
                                Telegram
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
