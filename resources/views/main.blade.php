@extends('layouts.app')
@section('title', $meta['title'])
@section('meta_description', $meta['description'])
@section('meta_keywords', $meta['keywords'])
@section('og_title', $meta['title'])
@section('og_description', $meta['description'])
@section('content')
    <div class="container mx-auto px-4 py-8">

        {{-- Search & Filter --}}
        @include('partials.product-filter')

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