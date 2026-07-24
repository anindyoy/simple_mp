<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\RandomProductHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestPushedAtUpdateSeeder extends Seeder
{
    /**
     * Create 10 random product history records where pushed_at < created_at
     * to test the UpdatePushedAtFromRandomHistorySeeder
     */
    public function run(): void
    {
        $this->command->info('Creating test data for pushed_at update...');

        // Get active products
        $products = Product::where('is_active', true)
            ->with('lapak')
            ->inRandomOrder()
            ->limit(10)
            ->get();

        if ($products->isEmpty()) {
            $this->command->warn('Tidak ada produk aktif.');
            return;
        }

        $users = User::pluck('id')->toArray();

        if (empty($users)) {
            $this->command->warn('Tidak ada user.');
            return;
        }

        $createdCount = 0;

        foreach ($products as $product) {
            // Set pushed_at to 10 days ago (old date)
            $oldPushedAt = now()->subDays(10);

            $product->update([
                'pushed_at' => $oldPushedAt,
                'pushed_by' => 'manual',
            ]);

            // Create random product history with created_at 5 days ago (newer than pushed_at)
            $historyCreatedAt = now()->subDays(5);

            RandomProductHistory::create([
                'product_id' => $product->id,
                'lapak_id' => $product->lapak_id,
                'user_id' => $users[array_rand($users)],
                'created_at' => $historyCreatedAt,
                'updated_at' => $historyCreatedAt,
            ]);

            $this->command->line("Created: Product #{$product->id} - pushed_at: {$oldPushedAt}, history created_at: {$historyCreatedAt}");
            $createdCount++;
        }

        $this->command->info("Selesai membuat {$createdCount} test records.");
        $this->command->info("Sekarang jalankan: php artisan db:seed --class=UpdatePushedAtFromRandomHistorySeeder");
    }
}