<?php

namespace App\Jobs;

use App\Models\Product;
use App\Models\ProductView;
use App\Models\Setting;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class RecordProductViewJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $productId,
        public string $ipAddress,
    ) {}

    public function handle(): void
    {
        $guardHours = Setting::getIntValue('product_view_guard_hours', 6, 1);

        $exists = ProductView::where('product_id', $this->productId)
            ->where('ip_address', $this->ipAddress)
            ->where('expires_at', '>', now())
            ->exists();

        if ($exists) {
            return;
        }

        DB::transaction(function () use ($guardHours) {
            ProductView::create([
                'product_id' => $this->productId,
                'ip_address' => $this->ipAddress,
                'expires_at' => now()->addHours($guardHours),
            ]);

            Product::where('id', $this->productId)->increment('views_count');
        });
    }
}