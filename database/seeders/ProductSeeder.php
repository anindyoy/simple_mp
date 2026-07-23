<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\LapakProfile;
use Illuminate\Database\Seeder;
use App\Traits\AttachesProductImages;
use App\Traits\UsesProductJsonData;
use App\Services\ProductScheduleService;
use Spatie\ResponseCache\Facades\ResponseCache;

class ProductSeeder extends Seeder
{
    use AttachesProductImages;
    use UsesProductJsonData;

    protected ?int $count;
    protected ?string $mode;

    /** @var array<string, array<string>> */
    private array $categoryImages = [];

    /**
     * @param int|null $count
     * @param string|null $mode
     *
     * Mode:
     * - null → default seeder (50 produk)
     * - testing → generate sedikit produk
     * - updatePushed → update pushed_at beberapa produk
     */
    public function __construct(?int $count = null, ?string $mode = null)
    {
        $this->count = $count;
        $this->mode = $mode;
    }

    public function run(): void
    {
        if ($this->mode === 'updatePushed') {
            $this->updatePushedAt();
            return;
        }

        $this->loadJsonData();
        $this->loadCategoryImages();

        if ($this->count !== null) {
            $this->runTestingSeeder($this->count);
            return;
        }

        $this->runDefaultSeeder();
    }

    /**
     * MODE 1 — Seeder original (50 produk)
     */
    protected function runDefaultSeeder(): void
    {
        $categories = Category::all();
        $existingLapaks = LapakProfile::all();

        Product::withoutEvents(function () use ($categories, $existingLapaks) {
            Product::factory(50)->make()->each(function ($product) use ($categories, $existingLapaks) {
                $category = $categories->random();
                $categoryName = $category->category_name;

                $product->category_id = $category->id;
                $product->price = $this->generatePrice($categoryName);

                [$title, $slug] = $this->makeProductTitle($categoryName);
                $product->title = $title;
                $product->slug = $slug;

                $product->condition = $category->supportsCondition()
                    ? fake()->randomElement(['baru', 'seken'])
                    : null;
                $product->lapak_id = $existingLapaks->isNotEmpty()
                    ? $existingLapaks->random()->id
                    : LapakProfile::factory()->create()->id;

                $product->timestamps = false;
                $product->created_at = fake()->dateTimeBetween('-10 days', 'now');
                $product->save();

                $product->unsetRelation('category');
                $this->attachRandomImages($product);
            });
        });

        ResponseCache::clear();
        ProductScheduleService::forgetEligible();
    }

    /**
     * MODE 2 — Generate sedikit produk untuk testing
     */
    protected function runTestingSeeder(int $total): void
    {
        $categories = Category::all();

        if ($categories->isEmpty()) return;

        $lapak = LapakProfile::first() ?? LapakProfile::factory()->create();

        Product::withoutEvents(function () use ($total, $categories, $lapak) {
            Product::factory($total)->make()->each(function ($product) use ($categories, $lapak) {
                $category = $categories->random();
                $categoryName = $category->category_name;

                $product->category_id = $category->id;
                $product->price = $this->generatePrice($categoryName);
                $product->lapak_id = $lapak->id;

                [$title, $slug] = $this->makeProductTitle($categoryName);
                $product->title = $title;
                $product->slug = $slug;

                $product->condition = $category->supportsCondition()
                    ? fake()->randomElement(['baru', 'seken'])
                    : null;

                $product->timestamps = false;
                $time = fake()->dateTimeBetween('-10 days', 'now');
                $product->created_at = $time;
                $product->pushed_at = $time;

                $product->save();
                $product->unsetRelation('category');
                $this->attachRandomImages($product);
            });
        });
    }

    private function loadCategoryImages(): void
    {
        $seedDir = storage_path('app/seed-samples');

        $subfolders = collect(scandir($seedDir))
            ->reject(fn($f) => in_array($f, ['.', '..']) || !is_dir($seedDir . DIRECTORY_SEPARATOR . $f));

        foreach ($subfolders as $folder) {
            $folderPath = $seedDir . DIRECTORY_SEPARATOR . $folder;
            $images = collect(scandir($folderPath))
                ->reject(fn($f) => in_array($f, ['.', '..']) || is_dir($folderPath . DIRECTORY_SEPARATOR . $f))
                ->map(fn($f) => $folderPath . DIRECTORY_SEPARATOR . $f)
                ->values()
                ->toArray();

            if (!empty($images)) {
                $this->categoryImages[mb_strtolower($folder)] = $images;
            }
        }
    }

    private function getImagesForCategory(string $categoryName): array
    {
        $key = mb_strtolower($categoryName);

        return $this->categoryImages[$key]
            ?? $this->getFallbackFiles();
    }

    private function getFallbackFiles(): array
    {
        $seedDir = storage_path('app/seed-samples');

        return collect(scandir($seedDir))
            ->reject(fn($f) => in_array($f, ['.', '..']) || is_dir($seedDir . DIRECTORY_SEPARATOR . $f))
            ->filter(fn($f) => !str_ends_with($f, '.json'))
            ->map(fn($f) => $seedDir . DIRECTORY_SEPARATOR . $f)
            ->values()
            ->toArray();
    }

    /**
     * MODE 3 — Update pushed_at beberapa produk agar < 6 jam
     */
    protected function updatePushedAt(): void
    {
        $count = $this->count ?? 5;

        $products = Product::inRandomOrder()
            ->limit($count)
            ->get();

        foreach ($products as $product) {
            $product->update([
                'pushed_at' => now()->subHours(rand(1, 5)),
            ]);
        }
    }
}