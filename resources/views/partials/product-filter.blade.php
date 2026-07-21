{{-- Trigger button --}}
<div class="mb-2 flex items-center gap-2">
    <button
        type="button"
        @click="open = true"
        :class="open
            ?
            'bg-indigo-50 border-indigo-400 text-indigo-700 hover:bg-indigo-100' :
            'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'"
        class="flex flex-1 items-center gap-2 px-4 py-2.5 rounded-lg border text-sm transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
        </svg>
        <span>Filter Produk</span>
        @if ($search || $categoryId || $condition || $deliverable)
            <span class="ml-1 flex flex-wrap gap-1">
                @if ($search)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                        Nama: {{ $search }}
                    </span>
                @endif
                @if ($categoryId)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                        Kategori: {{ $categories->firstWhere('id', $categoryId)?->category_name }}
                    </span>
                @endif
                @if ($condition)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                        Kondisi: {{ $condition === 'baru' ? 'Baru' : 'Bekas' }}
                    </span>
                @endif
                @if ($deliverable)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                        Bisa Diantar
                    </span>
                @endif
            </span>
        @endif
    </button>

    @if ($search || $categoryId || $condition || $deliverable)
        <button
            type="button"
            wire:click="resetFilters"
            class="shrink-0 px-4 py-2.5 rounded-lg border border-red-300 bg-red-50 text-red-600 hover:bg-red-100 text-sm transition">
            Reset Filter
        </button>
    @endif
</div>

{{-- Modal --}}
<div
    x-show="open"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
    @click.self="open = false">

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-lg bg-white rounded-xl shadow-xl flex flex-col">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <div class="flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
                </svg>
                <h2 class="text-base font-semibold text-gray-800">Filter Produk</h2>
            </div>
            <button type="button" @click="open = false" class="text-gray-400 hover:text-gray-600 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Body --}}
        <form wire:submit="applyFilters" class="flex flex-col flex-1">
            <div class="px-5 py-4 flex flex-col gap-4">

                {{-- Nama Produk --}}
                <div class="flex flex-col gap-1">
                    <label for="search" class="text-sm font-medium text-gray-700">Nama Produk</label>
                    <input
                        id="search"
                        type="text"
                        wire:model="search"
                        placeholder="Cari produk..."
                        class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                </div>

                {{-- Kategori --}}
                <div class="flex flex-col gap-1">
                    <label for="category" class="text-sm font-medium text-gray-700">Kategori</label>
                    <select
                        id="category"
                        wire:model="categoryId"
                        class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->category_name }} ({{ $categoryProductCounts->get($category->id, 0) }})</option>
                        @endforeach
                    </select>
                </div>

                {{-- Kondisi --}}
                <div class="flex flex-col gap-1"
                    x-data="{ supportsCondition: {{ !$categoryId || $categories->firstWhere('id', $categoryId)?->supportsCondition() ? 'true' : 'false' }} }"
                    x-init="$watch('$wire.categoryId', value => {
                        const noCategory = !value || value === '';
                        const supported = {{ $categories->map(fn($c) => ['id' => (string) $c->id, 'supports' => $c->supportsCondition()])->keyBy('id')->toJson() }};
                        supportsCondition = noCategory || (supported[value]?.supports ?? false);
                        if (!supportsCondition) $wire.condition = '';
                    })">
                    <label for="condition" class="text-sm font-medium text-gray-700">Kondisi</label>
                    <select
                        id="condition"
                        wire:model="condition"
                        :disabled="!supportsCondition"
                        :class="!supportsCondition ? 'bg-gray-100 text-gray-400 cursor-not-allowed' : 'bg-white'"
                        class="rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Semua Kondisi</option>
                        <option value="baru">Baru</option>
                        <option value="seken">Bekas</option>
                    </select>
                </div>

                {{-- Pengiriman --}}
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium text-gray-700">Pengiriman</label>
                    <label class="inline-flex items-center gap-2 cursor-pointer h-[42px] px-3 rounded-lg border border-gray-300 bg-white select-none w-fit">
                        <input
                            type="checkbox"
                            wire:model="deliverable"
                            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700">Bisa Diantar</span>
                    </label>
                </div>
            </div>

            {{-- Footer --}}
            <div class="flex gap-2 px-5 py-4 border-t border-gray-100">
                <button
                    type="submit"
                    @click="open = false"
                    class="flex-1 px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition text-sm font-medium cursor-pointer">
                    Cari
                </button>
                @if ($search || $categoryId || $condition || $deliverable)
                    <button
                        type="button"
                        wire:click="resetFilters"
                        @click="open = false"
                        class="flex-1 px-5 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 transition text-sm font-medium cursor-pointer">
                        Reset
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>
