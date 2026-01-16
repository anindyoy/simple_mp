buatkan fitur pencarian barang serta filter category pada halaman ini

<?
main.blade:
@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            @foreach ($products as $product)
                {{-- Cara memanggil component --}}
                @include('components.product-card', [
                    'product' => $product,
                    'showLapakName' => true,
                ])
            @endforeach
        </div>

        <div class="mt-10">
            {{ $products->links() }}
        </div>
    </div>
@endsection

<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['lapak', 'images' => function ($query) {
            $query->where('is_primary', true);
        }])
            ->where('is_active', true)
            ->orderBy('pushed_at', 'desc')
            ->paginate(16);

        return view('main', compact('products'));
    }

    public function show(Product $product)
    {
        $product->load(['lapak', 'category', 'images']);
        return view('product-detail', compact('product'));
    }
}
