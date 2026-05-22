<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\ProductScheduleService;
use Spatie\ResponseCache\Facades\ResponseCache;


class ProductObserver
{
    public function created(Product $product): void
    {
        $lapakId = (int) $product->getAttribute('lapak_id');

        ProductScheduleService::forget($lapakId);
        ResponseCache::clear();
    }

    public function updated(Product $product): void
    {
        $changedAttributes = array_keys($product->getChanges());
        $meaningfulChanges = array_diff($changedAttributes, ['updated_at']);

        if ($meaningfulChanges === []) {
            return;
        }

        $lapakId = (int) $product->getAttribute('lapak_id');
        $originalLapakId = (int) $product->getOriginal('lapak_id');

        if ($originalLapakId && $originalLapakId !== $lapakId) {
            ProductScheduleService::forget($originalLapakId);
        }

        ProductScheduleService::forget($lapakId);
        ResponseCache::clear();
    }

    public function deleted(Product $product): void
    {
        ProductScheduleService::forget((int) $product->getAttribute('lapak_id'));
    }
}
