<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\LapakProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use App\Services\ProductScheduleService;
use Spatie\ResponseCache\Facades\ResponseCache;

class UpdateCatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lapaks      = LapakProfile::all();
        $categories  = Category::all();
        $products    = Product::all();

        $changed          = false;
        $createdLapakCount = 0;
        $createdCount     = 0;
        $updatedCount     = 0;

        $totalRuns = 3;

        for ($i = 0; $i < $totalRuns; $i++) {
            $isLast    = $i === $totalRuns - 1;
            $mustUpdate = $isLast && $updatedCount === 0 && $products->isNotEmpty();

            // Kondisi buat lapak baru
            if (! $mustUpdate && random_int(1, 3) === 1) {
                // Cek apakah hari ini sudah ada lapak yang baru dibuat
                $newLapakToday = LapakProfile::query()
                    ->whereDate('created_at', today())
                    ->exists();

                if (! $newLapakToday) {
                    // Belum ada lapak baru hari ini — skip, lanjut ke kode update/create product
                    $lapak = $this->createRandomLapak();
                    $lapaks->push($lapak);
                    $createdLapakCount++;
                    $changed = true;
                    continue;
                }
            }

            $action = ($mustUpdate || ($products->isNotEmpty() && random_int(0, 1) === 1))
                ? 'update'
                : 'create';

            if ($action === 'create') {
                $product = $this->createRandomProduct($lapaks, $categories);
                if ($product) {
                    $products->push($product);
                    $createdCount++;
                    $changed = true;
                }
                continue;
            }

            $updated = $this->updateRandomProductPushTime($products);
            $updatedCount += $updated ? 1 : 0;
            $changed = $updated || $changed;
        }

        if ($changed) {
            ResponseCache::clear();
            ProductScheduleService::forgetEligible();
        }

        $summary = [
            'created_lapak' => $createdLapakCount,
            'created'       => $createdCount,
            'updated'       => $updatedCount,
            'total_runs'    => $totalRuns,
        ];

        $this->info(sprintf(
            'UpdateCatalogSeeder summary: created_lapak=%d, created=%d, updated=%d, total_runs=%d',
            $createdLapakCount,
            $createdCount,
            $updatedCount,
            $totalRuns,
        ));
        Log::info('UpdateCatalogSeeder selesai', $summary);
    }

    protected function info(string $message): void
    {
        fwrite(STDOUT, $message . PHP_EOL);
    }

    private function createRandomLapak(): LapakProfile
    {
        return LapakProfile::factory()->create();
    }

    private function createRandomProduct(Collection $lapaks, Collection $categories): ?Product
    {
        $lapak    = $lapaks->random();
        $category = $categories->random();

        if (! $lapak || ! $category) {
            return null;
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

        return $product;
    }

    private function updateRandomProductPushTime(Collection $products): bool
    {
        if ($products->isEmpty()) {
            return false;
        }

        $products->random()->updateQuietly([
            'pushed_at' => now()->subHours(random_int(1, 5)),
        ]);

        return true;
    }

    private function makeProductTitle(string $categoryName): string
    {
        return $categoryName . ' ' . Str::headline(fake()->words(2, true));
    }
}