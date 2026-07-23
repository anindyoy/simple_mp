<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use Illuminate\Database\Seeder;
use App\Models\RandomProductHistory;
use Illuminate\Database\Eloquent\Collection;

class RandomProductHistorySeeder extends Seeder
{
    protected ?int $count;

    /**
     * @param int|null $count Jumlah RandomProductHistory yang digenerate (default 20)
     */
    public function __construct(?int $count = null)
    {
        $this->count = $count;
    }

    public function run(): void
    {
        $products = Product::query()
            ->where('is_active', true)
            ->with('lapak')
            ->get();

        if ($products->isEmpty()) {
            $this->command->warn('Tidak ada produk aktif, lewati seeding RandomProductHistory.');
            return;
        }

        $users = User::query()->pluck('id');

        if ($users->isEmpty()) {
            $this->command->warn('Tidak ada user, lewati seeding RandomProductHistory.');
            return;
        }

        $total = $this->count ?? 10;

        RandomProductHistory::factory()
            ->count($total)
            ->make()
            ->each(function (RandomProductHistory $history) use ($products, $users) {
                /** @var Product $product */
                $product = $products->random();
                $history->product_id = $product->id;
                $history->lapak_id = $product->lapak_id;
                $history->user_id = $users->random();

                // Disable timestamps agar Eloquent tidak overwrite created_at/updated_at saat save
                $history->timestamps = false;

                // Set created_at dalam rentang 5 hari terakhir, dan pastikan < created_at produk
                $fiveDaysAgo = now()->subDays(5);
                $productCreated = $product->created_at;

                // Hitung selisih detik antara batas bawah (5 hari lalu) dan batas atas (created_at produk atau sekarang)
                if ($productCreated->lte($fiveDaysAgo)) {
                    // Produk dibuat >5 hari lalu: rentang penuh 5 hari
                    $rangeSeconds = 5 * 24 * 60 * 60; // 432000
                } else {
                    // Produk dibuat <5 hari lalu: dari 5 hari lalu sampai sebelum produk dibuat
                    $rangeSeconds = (int) $fiveDaysAgo->diffInSeconds($productCreated);
                }

                $offset = mt_rand(0, max(0, $rangeSeconds - 1));
                $history->created_at = $fiveDaysAgo->copy()->addSeconds($offset);
                $history->updated_at = $history->created_at;

                $history->save();
                $history->timestamps = true;

                // Set pushed_at & pushed_by untuk sebagian besar produk (≈70%)
                if (fake()->boolean(70)) {
                    $product->update([
                        'pushed_at' => now()->subHours(rand(1, 7)),
                        'pushed_by' => 'system',
                    ]);
                }
            });
    }
}