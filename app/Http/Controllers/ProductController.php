<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\Category;
use App\Models\LapakProfile;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use App\Services\ProductScheduleService;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search     = $request->query('search');
        $categoryId = $request->query('category');
        $condition  = $request->query('condition');

        // ✅ Dapat dari cache, hanya rebuild tiap 60 detik
        $eligibleProductIds = \App\Services\ProductScheduleService::getEligibleProductIds();

        $products = Product::with([
            'media',
            'lapak:id,name,slug',
        ])

            ->whereIn('id', $eligibleProductIds)
            ->whereHas('lapak', fn($q) => $q->where('is_active', true))
            ->where('is_active', true)
            ->when($search,     fn($q) => $q->where('title', 'like', "%$search%"))
            ->when($categoryId, fn($q) => $q->where('category_id', $categoryId))
            ->when($condition,  fn($q) => $q->where('condition', $condition))
            ->orderBy('pushed_at', 'desc')
            ->paginate(16);

        $categories = Cache::remember(
            'categories_list',
            3600,
            fn() => Category::orderBy('category_name')->get()
        );

        return view('main', [
            'products'          => $products,
            'categories'        => $categories,
            'search'            => $search,
            'selectedCategory'  => $categoryId,
            'selectedCondition' => $condition,
            'meta' => [
                'title'       => Setting::getValue('site_title', 'Lapak Warga'),
                'description' => Setting::getValue('site_description', '...'),
                'keywords'    => Setting::getValue('site_keywords', '...'),
            ],
        ]);
    }

    public function show(Product $product)
    {
        $product->load([
            'media',
            'lapak',
            'category',
        ]);

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
            'media',
            'lapak',
            'category',
        ])
            ->where('lapak_id', $product->lapak_id)
            ->where('is_active', true)
            ->where('id', '!=', $product->id)
            ->orderBy('pushed_at', 'desc')
            ->limit(8)
            ->get();

        $siteTitle = Setting::getValue(
            'site_title',
            'Lapak Warga'
        );
        $region = Setting::getValue('site_region', 'Cimanglid');

        return view('product-detail', [
            'product' => $product,
            'hasReported' => $hasReported,
            'otherProductsInLapak' => $otherProductsInLapak,
            'region' => $region,
            'meta' => [
                'title' => $product->title . ' - ' . $siteTitle . ' ' . $region,
                'description' => str()->limit(strip_tags($product->description), 155),
                'keywords' => implode(', ', [
                    $product->title,
                    $product->category?->category_name,
                    $product->lapak?->nama_lapak,
                    'jual beli ' . strtolower($region)
                ]),
                'image' => $product->getFirstMediaUrl('products'),
            ],
        ]);
    }
}
