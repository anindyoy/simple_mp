<div
    class="relative group max-w-sm bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 flex flex-col hover:border-blue-400 transition-all duration-300 cursor-pointer">

    {{-- Overlay link untuk seluruh card --}}
    <a href="{{ route('product.show', $product->slug) }}"
        class="absolute inset-0 z-10"></a>

    <div class="relative overflow-hidden rounded-t-2xl z-0">
        @php
            $imageUrl = $product->thumbnail_url;
        @endphp

        <img
            class="h-48 w-full object-cover group-hover:scale-110 transition-transform duration-500"
            src="{{ $imageUrl }}"
            alt="{{ $product->title }}"
            onerror="this.onerror=null;this.src='{{ asset('img/no-product-image.png') }}';" />

        @php
            $isNew = $product->created_at->diffInHours(now()) < 24;
            $isLifted = $product->pushed_at->diffInHours(now()) < 8;
        @endphp

        @if ($isNew)
            <span class="absolute top-3 left-3 bg-green-500 text-white text-[10px] font-black px-2 py-1 rounded-lg shadow-lg">
                BARU
            </span>
        @elseif ($isLifted)
            <span class="absolute top-3 left-3 bg-orange-500 text-white text-[10px] font-black px-2 py-1 rounded-lg shadow-lg">
                DIANGKAT
            </span>
        @endif

        <span class="absolute bottom-2 right-2 bg-white/90 backdrop-blur px-2 py-1 rounded-md text-[9px] font-bold text-gray-600 shadow-sm">
            {{ $product->category->category_name }}
        </span>
    </div>

    <div class="p-4 flex-grow flex flex-col relative z-0">
        <h5 class="text-sm font-bold tracking-tight text-gray-900 dark:text-white line-clamp-2 mb-1 group-hover:text-blue-600 transition-colors">
            {{ $product->title }}
        </h5>

        <p class="text-lg font-black text-blue-700 dark:text-blue-400 mb-1">
            Rp {{ number_format($product->price) }}
        </p>

        <div class="mb-1 flex flex-wrap gap-1">
            @if ($product->hasCondition())
                <span
                    class="inline-flex items-center px-3 py-1 text-xs font-semibold rounded-full
                    {{ $product->condition === 'baru' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                    Kondisi {{ $product->conditionLabel() }}
                </span>
            @endif

            @if ($product->can_be_delivered)
                <span class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-700">
                    <x-heroicon-o-truck class="w-3 h-3" />
                    Bisa Diantar
                </span>
            @endif
        </div>

        @if ($showLapakName ?? false)
            <div class="flex items-center gap-1 text-sm text-gray-500 font-semibold">
                <x-heroicon-o-building-storefront class="w-3 h-3 text-gray-400" />

                {{-- Link lapak harus lebih tinggi dari overlay --}}
                <a href="{{ route('lapak.show', $product->lapak) }}"
                    class="relative z-20 hover:underline text-blue-600">
                    {{ $product->lapak->name }}
                </a>
            </div>

            {{-- Waktu --}}
            <div class="flex items-center gap-1 mt-0.5 text-[10px] text-gray-400">
                <x-heroicon-s-clock class="w-3 h-3" />
                @if ($product->pushed_at?->greaterThan($product->created_at))
                    <span class="text-emerald-600 dark:text-emerald-400 font-medium">Diangkat</span>
                    {{ $product->pushed_at->diffForHumans() }}
                @else
                    <span class="text-gray-500">Dibuat</span>
                    {{ $product->created_at->diffForHumans() }}
                @endif
            </div>
        @endif

        <div class="mt-auto pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between text-[10px]">
                {{-- Kiri: Views badge --}}
                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 font-medium leading-none">
                    <x-heroicon-o-eye class="w-3 h-3" />
                    <span class="leading-none">{{ number_format($product->views_count) }}</span>
                </span>

                {{-- Kanan: Lokasi --}}
                <div class="flex items-center gap-1 text-gray-400 font-medium">
                    <x-heroicon-o-map-pin class="w-3 h-3 text-gray-400" />
                    {{ $product->lapak->address_short }}
                </div>
            </div>
        </div>
    </div>
</div>
