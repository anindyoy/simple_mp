@extends('layouts.app')

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
                class="w-full sm:w-1/2 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
            >

            {{-- Category Filter --}}
            <select
                name="category"
                class="w-full sm:w-1/4 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500"
            >
                <option value="">Semua Kategori</option>
                @foreach ($categories as $category)
                    <option
                        value="{{ $category->id }}"
                        @selected($selectedCategory == $category->id)
                    >
                        {{ $category->category_name }}
                    </option>
                @endforeach
            </select>

            {{-- Submit --}}
            <button
                type="submit"
                class="sm:w-auto px-5 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition"
            >
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
