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

            {{-- Toggle button — hanya tampil di mobile --}}
            <div class="sm:hidden mb-3">
                <button
                    type="button"
                    x-data="{ open: {{ $search || $selectedCategory || request('condition') ? 'true' : 'false' }} }"
                    @click="open = !open; $dispatch('filter-toggle', { open })"
                    class="flex items-center gap-2 w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-700 hover:bg-gray-50 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
                    </svg>
                    <span>Filter Produk</span>
                    @if ($search || $selectedCategory || request('condition'))
                        <span class="ml-auto inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                            Aktif
                        </span>
                    @endif
                    <svg xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 ml-auto text-gray-400 transition-transform duration-200"
                        :class="open ? 'rotate-180' : ''"
                        x-data="{ open: {{ $search || $selectedCategory || request('condition') ? 'true' : 'false' }} }"
                        @filter-toggle.window="open = $event.detail.open"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
            </div>

            {{-- Filter fields --}}
            <div
                x-data="{ open: {{ $search || $selectedCategory || request('condition') ? 'true' : 'false' }} }"
                @filter-toggle.window="open = $event.detail.open"
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="sm:!block"
                {{-- Selalu tampil di sm ke atas --}}
                :class="''">
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

                    @php
                        $condition = request('condition');
                    @endphp

                    @if (!$selectedCategory || $categories->firstWhere('id', $selectedCategory)?->supportsCondition())
                        <select
                            name="condition"
                            class="w-full sm:w-1/4 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Semua Kondisi</option>
                            <option value="baru" @selected($condition === 'baru')>Baru</option>
                            <option value="seken" @selected($condition === 'seken')>Bekas</option>
                        </select>
                    @endif

                    {{-- Submit --}}
                    <button
                        type="submit"
                        class="sm:w-auto px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition">
                        Cari
                    </button>
                </div>
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