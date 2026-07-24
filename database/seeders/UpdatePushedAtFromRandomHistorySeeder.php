<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\RandomProductHistory;

class UpdatePushedAtFromRandomHistorySeeder extends Seeder
{
    /**
     * Update pushed_at produk berdasarkan random product history.
     * Jika pushed_at produk < created_at random product history,
     * maka samakan pushed_at dengan created_at random product history
     * dan set pushed_by = 'system'.
     */
    public function run(): void
    {
        $this->command->info('Memulai update pushed_at dari random product history...');

        // Ambil semua random product history dengan relasi product
        $histories = RandomProductHistory::with('product')->get();

        $updatedCount = 0;
        $skippedCount = 0;

        foreach ($histories as $history) {
            $product = $history->product;

            // Skip jika produk tidak ada
            if (!$product) {
                $skippedCount++;
                continue;
            }

            $pushedAt = $product->pushed_at;
            $historyCreatedAt = $history->created_at;

            // Jika pushed_at produk kurang dari created_at random product history
            if ($pushedAt && $pushedAt->lt($historyCreatedAt)) {
                $product->update([
                    'pushed_at' => $historyCreatedAt,
                    'pushed_by' => 'system',
                ]);

                $updatedCount++;
                $this->command->line("Updated: Product #{$product->id} - pushed_at diubah dari {$pushedAt} ke {$historyCreatedAt}");
            } else {
                $skippedCount++;
            }
        }

        $this->command->info("Selesai!");
        $this->command->info("Total updated: {$updatedCount}");
        $this->command->info("Total skipped: {$skippedCount}");
    }
}