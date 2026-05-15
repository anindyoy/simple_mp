<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ProductScheduleService;
use Spatie\ResponseCache\Facades\ResponseCache;


class ProductObserver
{
    public function saved(Product $product): void
    {
        ProductScheduleService::forget($product->lapak_id);
        ResponseCache::clear();
    }

    public function deleted(Product $product): void
    {
        ProductScheduleService::forget($product->lapak_id);
    }
}
