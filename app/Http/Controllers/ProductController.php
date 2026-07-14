<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use App\Services\ProductScheduleService;

class ProductController extends Controller
{
    public function index()
    {
        return view('main', [
            'meta' => [
                'title'       => Setting::getValue('site_title', 'Lapak Warga'),
                'description' => Setting::getValue('site_description', '...'),
                'keywords'    => Setting::getValue('site_keywords', '...'),
            ],
        ]);
    }

    public function show(Request $request, Product $product)
    {
        $product->load([
            'media',
            'lapak',
            'category',
        ]);

        if (! $product->is_active || ! $product->lapak?->is_active) {
            abort(404);
        }

        $viewGuardKey = "product_view:{$product->id}:{$request->ip()}";
        $viewGuardHours = Setting::getIntValue('product_view_guard_hours', 6, 1);

        if (Cache::add($viewGuardKey, true, now()->addHours($viewGuardHours))) {
            $product->increment('views_count');
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
