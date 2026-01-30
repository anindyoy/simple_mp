<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $categoryId = $request->query('category');
        $condition = $request->query('condition');

        $products = Product::with([
            'lapak',
            'images' => fn($q) => $q->where('is_primary', true),
        ])
            ->where('is_active', true)
            ->when($search, fn($q) => $q->where('title', 'like', "%$search%"))
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->when($condition, fn($q) => $q->where('condition', $condition))
            ->orderBy('pushed_at', 'desc')
            ->paginate(16)
            ->withQueryString();

        $categories = Category::orderBy('category_name')->get();

        return view('main', [
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'selectedCategory' => $categoryId,
            'selectedCondition' => $condition,

            'meta' => [
                'title' => 'Jual Beli Cimanglid - Marketplace Warga',
                'description' => 'Marketplace lokal warga Cimanglid. Temukan makanan, jasa, elektronik, dan kebutuhan harian.',
                'keywords' => 'jual beli cimanglid, marketplace desa, iklan warga cimanglid',
            ],
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['lapak', 'category', 'images']);
        return view('product-detail', [
            'product' => $product,
            'meta' => [
                'title' => $product->title . ' | Jual Beli Cimanglid',
                'description' => str()->limit(strip_tags($product->description), 155),
                'keywords' => implode(', ', [
                    $product->title,
                    $product->category?->category_name,
                    $product->lapak?->nama_lapak,
                    'jual beli cimanglid'
                ]),
                'image' => optional($product->images->first())->image_url,
            ],
        ]);
    }
}
