<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\RandomProductHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

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

        $total = $this->count ?? 20;

        RandomProductHistory::factory()
            ->count($total)
            ->make()
            ->each(function (RandomProductHistory $history) use ($products, $users) {
                /** @var Product $product */
                $product = $products->random();
                $history->product_id = $product->id;
                $history->lapak_id = $product->lapak_id;
                $history->user_id = $users->random();
                $history->save();
            });
    }
}