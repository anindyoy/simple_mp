<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\LapakProfile;
use Illuminate\Database\Seeder;
use App\Services\ProductScheduleService;
use Spatie\ResponseCache\Facades\ResponseCache;

class UpdateCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $changed = false;
        $createdCount = 0;
        $updatedCount = 0;

        for ($i = 0; $i < 5; $i++) {
            $action = Product::query()->exists() && random_int(0, 1) === 1
                ? 'update'
                : 'create';

            if ($action === 'create') {
                $created = $this->createRandomProduct();
                $createdCount += $created ? 1 : 0;
                $changed = $created || $changed;
                continue;
            }

            $updated = $this->updateRandomProductPushTime();
            $updatedCount += $updated ? 1 : 0;
            $changed = $updated || $changed;
        }

        if ($changed) {
            ResponseCache::clear();
            ProductScheduleService::forgetEligible();
        }

        $this->info(sprintf(
            'UpdateCatalogSeeder summary: created=%d, updated=%d, total_runs=%d',
            $createdCount,
            $updatedCount,
            5,
        ));
    }

    protected function info(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    private function createRandomProduct(): bool
    {
        $lapak = LapakProfile::query()->inRandomOrder()->first();
        $category = Category::query()->inRandomOrder()->first();

        if (! $lapak || ! $category) {
            return false;
        }

        $title = $this->makeProductTitle($category->category_name);

        // withoutEvents agar observer creating tidak overwrite pushed_at
        $product = Product::withoutEvents(
            fn() =>
            Product::factory()->create([
                'title'       => $title,
                'category_id' => $category->id,
                'lapak_id'    => $lapak->id,
                'pushed_at'   => now()->subHours(rand(1, 5)),
            ])
        );

        ProductScheduleService::rebuild($lapak->id);

        return true;
    }

    private function updateRandomProductPushTime(): bool
    {
        $product = Product::query()->inRandomOrder()->first();

        if (! $product) {
            return false;
        }

        $product->updateQuietly([
            'pushed_at' => now()->subHours(random_int(1, 5)),
        ]);

        return true;
    }

    private function makeProductTitle(string $categoryName): string
    {
        return $categoryName . ' ' . Str::headline(fake()->words(2, true));
    }
}
