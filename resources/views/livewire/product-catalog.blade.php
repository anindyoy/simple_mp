<div x-data="{ open: false }">

    @include('partials.product-filter', [
        'categories' => $categories,
        'categoryProductCounts' => $categoryProductCounts,
        'search' => $search,
        'categoryId' => $categoryId,
        'condition' => $condition,
        'deliverable' => $deliverable,
        'supportsCondition' => $supportsCondition,
    ])

    @if($showVisitorCount)
        <div class="flex items-center gap-1.5 text-sm text-gray-500 mt-1 mb-4">
            <span>👥</span>
            <span>{{ number_format($visitorCount24h) }} pengunjung dalam 24 jam terakhir</span>
        </div>
    @endif

    {{-- Loading overlay --}}
    <div
        wire:loading
        wire:target="applyFilters, resetFilters"
        style="display: none"
        class="fixed inset-0 z-40 flex items-center justify-center bg-black/20">
        <div class="bg-white rounded-xl px-6 py-4 flex items-center gap-3 shadow-lg">
            <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
            </svg>
            <span class="text-sm font-medium text-gray-700">Memuat produk...</span>
        </div>
    </div>

    {{-- Grid Produk --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">

        {{-- Skeleton saat loading --}}
        <div wire:loading.grid wire:target="applyFilters, resetFilters" style="display: none" class="col-span-full grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @for ($i = 0; $i < 16; $i++)
                <div class="bg-white border border-gray-200 rounded-2xl shadow-sm flex flex-col animate-pulse">
                    <div class="h-48 w-full bg-gray-200 rounded-t-2xl"></div>
                    <div class="p-4 flex flex-col gap-2 flex-grow">
                        <div class="h-3.5 bg-gray-200 rounded w-full"></div>
                        <div class="h-3.5 bg-gray-200 rounded w-2/3"></div>
                        <div class="h-5 bg-gray-200 rounded w-1/2 mt-1"></div>
                        <div class="flex gap-1 mt-1">
                            <div class="h-5 bg-gray-200 rounded-full w-16"></div>
                            <div class="h-5 bg-gray-200 rounded-full w-20"></div>
                        </div>
                        <div class="h-3.5 bg-gray-200 rounded w-1/2 mt-1"></div>
                        <div class="mt-auto pt-3 border-t border-gray-50 flex justify-between">
                            <div class="h-3 bg-gray-200 rounded w-1/3"></div>
                            <div class="h-3 bg-gray-200 rounded w-1/4"></div>
                        </div>
                    </div>
                </div>
            @endfor
        </div>

        {{-- Produk aktual --}}
        <div wire:loading.remove wire:target="applyFilters, resetFilters" class="contents">
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

    </div>

    {{-- Pagination --}}
    <div class="mt-10">
        {{ $products->links() }}
    </div>
</div>
