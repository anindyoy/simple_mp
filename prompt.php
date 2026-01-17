bantu buat attribute kondisi untuk product, nilainya baru, seken, dan null
buat agar kondisi produk hanya tampil pada beberapa kategori produk
buatkan filternya juga di halaman main.

<?
-- simple_mp.products definition

CREATE TABLE `products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `lapak_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `price` decimal(12,2) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `pushed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `products_slug_unique` (`slug`),
  KEY `products_lapak_id_foreign` (`lapak_id`),
  KEY `products_category_id_foreign` (`category_id`),
  KEY `products_pushed_at_index` (`pushed_at`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  CONSTRAINT `products_lapak_id_foreign` FOREIGN KEY (`lapak_id`) REFERENCES `lapak_profiles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=51 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- simple_mp.categories definition

CREATE TABLE `categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `category_name` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

main.blade:
@extends('layouts.app')
@section('title', $meta['title'])
@section('meta_description', $meta['description'])
@section('meta_keywords', $meta['keywords'])

@section('og_title', $meta['title'])
@section('og_description', $meta['description'])

@section('content')
    <div class="container mx-auto px-4 py-8">

        {{-- Search & Filter --}}
        <form method="GET" action="{{ route('products.index') }}" class="mb-6">
            <div class="flex flex-col sm:flex-row gap-3">

                {{-- Search --}}
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="Cari produk..."
                    class="w-full sm:w-1/2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">

                {{-- Category Filter --}}
                <select
                    name="category"
                    class="w-full sm:w-1/4 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Semua Kategori</option>
                    @foreach ($categories as $category)
                        <option
                            value="{{ $category->id }}"
                            @selected($selectedCategory == $category->id)>
                            {{ $category->category_name }}
                        </option>
                    @endforeach
                </select>

                {{-- Submit --}}
                <button
                    type="submit"
                    class="sm:w-auto px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">
                    Cari
                </button>
            </div>
        </form>

        {{-- Produk --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @forelse ($products as $product)
                @include('components.product-card', [
                    'product' => $product,
                    'showLapakName' => true,
                ])
            @empty
                <div class="col-span-full text-center text-gray-500 py-10">
                    Produk tidak ditemukan
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        <div class="mt-10">
            {{ $products->links() }}
        </div>
    </div>
@endsection

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $categoryId = $request->query('category');

        $products = Product::with([
            'lapak',
            'images' => function ($query) {
                $query->where('is_primary', true);
            }
        ])
            ->where('is_active', true)
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('pushed_at', 'desc')
            ->paginate(16)
            ->withQueryString(); // penting agar filter tetap saat pagination

        $categories = Category::orderBy('category_name')->get();

        return view('main', [
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'selectedCategory' => $categoryId,

            'meta' => [
                'title' => 'Jual Beli Cimanglid - Marketplace Warga',
                'description' => 'Marketplace lokal warga Cimanglid. Temukan makanan, jasa, elektronik, dan kebutuhan harian.',
                'keywords' => 'jual beli cimanglid, marketplace desa, iklan warga cimanglid',
            ],
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['lapak', 'category', 'images']);
        return view('product-detail', [
            'product' => $product,
            'meta' => [
                'title' => $product->title . ' | Jual Beli Cimanglid',
                'description' => str()->limit(strip_tags($product->description), 155),
                'keywords' => implode(', ', [
                    $product->title,
                    $product->category?->category_name,
                    $product->lapak?->nama_lapak,
                    'jual beli cimanglid'
                ]),
                'image' => optional($product->images->first())->image_url,
            ],
        ]);
    }
}

product-detail.blade:
@extends('layouts.app')
@section('title', $meta['title'])
@section('meta_description', $meta['description'])
@section('meta_keywords', $meta['keywords'])

@section('og_title', $meta['title'])
@section('og_description', $meta['description'])
@section('og_type', 'product')
@section('og_image', $meta['image'] ?? asset('images/og-default.jpg'))
1
@section('title', $product->title . ' - Jual Beli Cimanglid')

@section('content')
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <div class="space-y-4">
                @if ($product->images->count() === 1)
                    {{-- Single Image (tanpa carousel) --}}
                    @php
                        $image = $product->images->first();
                        $imgUrl = Str::startsWith($image->image_url, ['http://', 'https://']) ? $image->image_url : asset('storage/' . $image->image_url);
                    @endphp

                    <div class="w-full h-56 md:h-96 bg-white border rounded-base shadow-sm flex items-center justify-center">
                        <img
                            src="{{ $imgUrl }}"
                            class="max-w-full max-h-full object-contain"
                            alt="Gambar Produk {{ $product->title }}">
                    </div>
                @elseif ($product->images->count() > 1)
                    {{-- Carousel (jika > 1 foto) --}}
                    <div id="productImagesCarousel" class="relative w-full" data-carousel="slide">
                        <div class="relative h-56 md:h-96 overflow-hidden rounded-base bg-white shadow-sm border">
                            @foreach ($product->images as $index => $image)
                                @php
                                    $imgUrl = Str::startsWith($image->image_url, ['http://', 'https://']) ? $image->image_url : asset('storage/' . $image->image_url);
                                @endphp

                                <div
                                    data-carousel-item="{{ $index === 0 ? 'active' : '' }}"
                                    class="{{ $index === 0 ? '' : 'hidden' }} duration-700 ease-in-out">
                                    <img
                                        src="{{ $imgUrl }}"
                                        class="block w-full h-full object-contain mx-auto"
                                        alt="Gambar Produk {{ $product->title }}">
                                </div>
                            @endforeach
                        </div>

                        {{-- Indicators --}}
                        <div class="absolute z-30 flex -translate-x-1/2 bottom-5 left-1/2 space-x-3">
                            @foreach ($product->images as $i => $img)
                                <button
                                    type="button"
                                    class="w-3 h-3 rounded-base bg-white/50"
                                    data-carousel-slide-to="{{ $i }}"
                                    aria-label="Slide {{ $i + 1 }}"></button>
                            @endforeach
                        </div>

                        {{-- Controls --}}
                        <button type="button" class="absolute top-0 start-0 z-30 flex items-center justify-center h-full px-4" data-carousel-prev>
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-base bg-white/30">
                                ‹
                            </span>
                        </button>
                        <button type="button" class="absolute top-0 end-0 z-30 flex items-center justify-center h-full px-4" data-carousel-next>
                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-base bg-white/30">
                                ›
                            </span>
                        </button>
                    </div>
                @endif
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
