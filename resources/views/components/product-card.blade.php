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