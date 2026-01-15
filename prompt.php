bantu saya buatkan halaman detail lapak.
tampilkan juga ada list produknya.

-- simple_mp.lapak_profiles definition

CREATE TABLE `lapak_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `shop_name` varchar(100) NOT NULL,
  `whatsapp_number` varchar(20) NOT NULL,
  `telegram_username` varchar(50) DEFAULT NULL,
  `address_raw` text NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lapak_profiles_user_id_foreign` (`user_id`),
  CONSTRAINT `lapak_profiles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

resources\views\components\product-card.blade.php:
<div class="max-w-sm bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 flex flex-col group hover:border-blue-400 transition-all duration-300">
    <div class="relative overflow-hidden rounded-t-2xl">
        {{-- Logika Gambar: Cek apakah URL eksternal atau lokal --}}
        @php
            $primaryImage = $product->images->where('is_primary', true)->first() ?? $product->images->first();
            $imageUrl = $primaryImage
                ? (Str::startsWith($primaryImage->image_url, ['http://', 'https://'])
                    ? $primaryImage->image_url
                    : asset('storage/' . $primaryImage->image_url))
                : 'https://flowbite.com/docs/images/products/apple-watch.png';
        @endphp

        <a href="{{ route('product.show', $product->slug) }}">
            <img class="h-48 w-full object-cover group-hover:scale-110 transition-transform duration-500"
                 src="{{ $imageUrl }}"
                 alt="{{ $product->title }}" />
        </a>

        {{-- Badge Sundul (Aktif jika di-push dalam 6 jam terakhir) --}}
        @if($product->pushed_at->diffInHours(now()) < 6)
            <span class="absolute top-3 left-3 bg-orange-500 text-white text-[10px] font-black px-2 py-1 rounded-lg shadow-lg">
                SUNDUL
            </span>
        @endif

        <span class="absolute bottom-2 right-2 bg-white/90 backdrop-blur px-2 py-1 rounded-md text-[9px] font-bold text-gray-600 shadow-sm">
            {{ $product->category->category_name }}
        </span>
    </div>

    <div class="p-4 flex-grow flex flex-col">
        <a href="{{ route('product.show', $product->slug) }}">
            <h5 class="text-sm font-bold tracking-tight text-gray-900 dark:text-white line-clamp-2 mb-2 group-hover:text-blue-600 transition-colors">
                {{ $product->title }}
            </h5>
        </a>

        <p class="text-lg font-black text-blue-700 dark:text-blue-400 mb-4">
            Rp {{ number_format($product->price, 0, ',', '.') }}
        </p>

        <div class="mt-auto pt-3 border-t border-gray-50 dark:border-gray-700 flex items-center justify-between text-[10px] text-gray-400">
            <div class="flex items-center gap-1">
                <svg class="w-3 h-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ $product->pushed_at->diffForHumans() }}
            </div>
            <div class="flex items-center gap-1 font-semibold text-gray-600">
                <svg class="w-3 h-3 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path></svg>
                {{ Str::limit($product->lapak->address_raw, 12) }}
            </div>
        </div>
    </div>
</div>

<?php

namespace App\Http\Controllers;

class ProductController extends Controller
{
    public function index()
    {
        // Mengambil produk yang aktif, diurutkan dari yang paling baru di-sundul (push)
        // Eager Loading (with) digunakan agar query ke database lebih efisien
        $products = Product::with(['lapak', 'images' => function ($query) {
            $query->where('is_primary', true);
        }])
            ->where('is_active', true)
            ->orderBy('pushed_at', 'desc')
            ->paginate(10); // Menggunakan pagination agar tidak berat saat data banyak

        return view('welcome', compact('products'));
    }

    public function show(Product $product)
    {
        // Load relasi agar data tampil lengkap
        $product->load(['lapak', 'category', 'images']);

        return view('product-detail', compact('product'));
    }
}
