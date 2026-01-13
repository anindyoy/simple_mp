<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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
