<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ProductScheduleService;

class ProductObserver
{
    public function saved(Product $product): void
    {
        ProductScheduleService::forget($product->lapak_id);
    }

    public function deleted(Product $product): void
    {
        ProductScheduleService::forget($product->lapak_id);
    }
}
