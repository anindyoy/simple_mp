<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Setting;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use App\Services\ProductScheduleService;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search     = $request->query('search');
        $categoryId = $request->query('category');
        $condition  = $request->query('condition');

        $eligibleProductIds = ProductScheduleService::getEligibleProductIds();

        if ($eligibleProductIds->count() > 900) {
            rescue(fn() => $this->notifyEligibleThreshold($eligibleProductIds->count()), report: false);
        }

        $products = Product::with([
            'media',
            'lapak:id,name,slug,address_raw',
            'category:id,category_name',
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

    private function notifyEligibleThreshold(int $count): void
    {
        // Kirim notif Telegram max sekali per hari agar tidak spam
        if (! Cache::add('products.eligible_threshold_notified', true, now()->addDay())) {
            return;
        }

        $token  = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');

        if (blank($token) || blank($chatId)) {
            return;
        }

        Http::post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id'    => $chatId,
            'parse_mode' => 'HTML',
            'text'       => implode("\n", [
                '⚠️ <b>Peringatan Kapasitas eligible_ids</b>',
                '',
                "<code>eligibleProductIds</code> telah mencapai <b>{$count}</b> produk (batas rekomendasi: 900).",
                'Pertimbangkan refactor <code>resolveEligibleProductIds()</code> dan <code>whereIn</code> di ProductController.',
                '',
                'Domain: ' . config('app.url'),
            ]),
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
