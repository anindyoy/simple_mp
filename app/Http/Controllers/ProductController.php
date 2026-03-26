<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use App\Models\Setting;
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
            ->whereHas('lapak', function ($lapakQuery) {
                $lapakQuery->where('is_active', true);
            })
            ->where('is_active', true)
            ->when($search, fn($q) => $q->where('title', 'like', "%$search%"))
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->when($condition, fn($q) => $q->where('condition', $condition))
            ->orderBy('pushed_at', 'desc')
            ->paginate(16)
            ->withQueryString();

        $categories = Category::orderBy('category_name')->get();

        $siteTitle = Setting::getValue(
            'site_title',
            'Lapak Online Warga'
        );
        $siteDescription = Setting::getValue(
            'site_description',
            'Marketplace online untuk warga. Jual beli produk dan jasa lokal dengan mudah.'
        );
        $siteKeywords = Setting::getValue(
            'site_keywords',
            'marketplace, jual beli online, produk lokal, warga, toko online'
        );

        return view('main', [
            'products' => $products,
            'categories' => $categories,
            'search' => $search,
            'selectedCategory' => $categoryId,
            'selectedCondition' => $condition,

            'meta' => [
                'title' => $siteTitle,
                'description' => $siteDescription,
                'keywords' => $siteKeywords,
            ],
        ]);
    }

    public function show(Product $product)
    {
        $product->load(['lapak', 'category', 'images']);

        if (! $product->is_active || ! $product->lapak?->is_active) {
            abort(404);
        }

        $hasReported = false;

        if (auth()->check()) {
            $hasReported = $product->reports()
                ->where('user_id', auth()->id())
                ->exists();
        }

        $otherProductsInLapak = Product::with([
            'lapak',
            'category',
            'images' => fn($q) => $q->where('is_primary', true),
        ])
            ->where('lapak_id', $product->lapak_id)
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->orderBy('pushed_at', 'desc')
            ->limit(8)
            ->get();

        $siteTitle = Setting::getValue(
            'site_title',
            'Lapak Online Warga'
        );
        $region = Setting::getValue('site_region', 'Cimanglid');

        return view('product-detail', [
            'product' => $product,
            'hasReported' => $hasReported,
            'otherProductsInLapak' => $otherProductsInLapak,
            'region' => $region,
            'meta' => [
                'title' => $product->title . ' - Jual Beli ' . $region,
                'description' => str()->limit(strip_tags($product->description), 155),
                'keywords' => implode(', ', [
                    $product->title,
                    $product->category?->category_name,
                    $product->lapak?->nama_lapak,
                    'jual beli ' . strtolower($region)
                ]),
                'image' => optional($product->images->first())->image_url,
            ],
        ]);
    }
}
