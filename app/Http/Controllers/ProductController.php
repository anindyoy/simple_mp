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

        $products = Product::with([
                'lapak',
                'images' => function ($query) {
                    $query->where('is_primary', true);
                }
            ])
            ->where('is_active', true)
            ->when($search, function ($query) use ($search) {
                $query->where('title', 'like', '%' . $search . '%');
            })
            ->when($categoryId, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('pushed_at', 'desc')
            ->paginate(16)
            ->withQueryString(); // penting agar filter tetap saat pagination

        $categories = Category::orderBy('category_name')->get();

        return view('main', [
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'selectedCategory' => $categoryId,
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['lapak', 'category', 'images']);
        return view('product-detail', compact('product'));
    }
}
