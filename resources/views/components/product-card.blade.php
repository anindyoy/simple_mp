<div
    class="relative group max-w-sm bg-white border border-gray-200 rounded-2xl shadow-sm dark:bg-gray-800 dark:border-gray-700 flex flex-col hover:border-blue-400 transition-all duration-300 cursor-pointer">

    {{-- Overlay link untuk seluruh card --}}
    <a href="{{ route('product.show', $product->slug) }}"
        class="absolute inset-0 z-10"></a>

    <div class="relative overflow-hidden rounded-t-2xl z-0">
        @php
            $imageUrl = $$product->thumbnail_url;
        @endphp

        <img
            class="h-48 w-full object-cover group-hover:scale-110 transition-transform duration-500"
            src="{{ $imageUrl }}"
            alt="{{ $product->title }}" />

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
                BARU DIANGKAT
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

        @if ($product->hasCondition())
            <span class="inline-block mb-1 text-[10px] font-bold px-2 py-0.5 rounded-full
                {{ $product->condition === 'baru' ? 'text-green-700' : 'text-yellow-700' }}">
                Kondisi {{ $product->conditionLabel() }}
            </span>
        @endif

        @if ($showLapakName ?? false)
            <div class="flex items-center gap-1 text-sm text-gray-500 font-semibold">
                <x-heroicon-o-building-storefront class="w-3 h-3 text-gray-400" />

                {{-- Link lapak harus lebih tinggi dari overlay --}}
                <a href="{{ route('lapak.show', $product->lapak) }}"
                    class="relative z-20 hover:underline text-blue-600">
                    {{ $product->lapak->name }}
                </a>
            </div>
        @endif

        <div class="mt-auto pt-3 border-t border-gray-50 dark:border-gray-700 flex items-center justify-between text-[10px] text-gray-400">
            <div>
                @if ($product->pushed_at?->equalTo($product->created_at))
                    <span class="text-gray-500">Dibuat</span>
                @else
                    <span class="text-emerald-600 font-medium">Diangkat</span>
                @endif

                {{ $product->pushed_at?->diffForHumans() }}
            </div>

            <div class="font-semibold text-gray-600">
                {{ \Illuminate\Support\Str::limit($product->lapak->address_raw, 12) }}
            </div>
        </div>
    </div>
</div>
