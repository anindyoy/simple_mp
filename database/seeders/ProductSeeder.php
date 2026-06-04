<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\LapakProfile;
use Illuminate\Database\Seeder;
use App\Services\ProductScheduleService;
use Spatie\ResponseCache\Facades\ResponseCache;

class ProductSeeder extends Seeder
{
    protected ?int $count;
    protected ?string $mode;

    /** @var array<string, array<string>> */
    private array $productNames = [];

    /** @var array<string, array<string>> */
    private array $productSifat = [];

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

                [$title, $slug] = $this->generateTitleAndSlug($categoryName);
                $product->title = $title;
                $product->slug = $slug;

                $product->condition = $category->supportsCondition()
                    ? fake()->randomElement(['baru', 'seken'])
                    : null;
                $product->lapak_id = $existingLapaks->isNotEmpty()
                    ? $existingLapaks->random()->id
                    : LapakProfile::factory()->create()->id;

                $product->created_at = now()->subHours(rand(1, 24));
                $product->save();

                $categoryImages = $this->getImagesForCategory($categoryName);
                foreach (range(1, rand(1, 3)) as $_) {
                    $this->addRandomImage($product, $categoryImages);
                }
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
                $product->lapak_id = $lapak->id;

                [$title, $slug] = $this->generateTitleAndSlug($categoryName);
                $product->title = $title;
                $product->slug = $slug;

                $product->condition = $category->supportsCondition()
                    ? fake()->randomElement(['baru', 'seken'])
                    : null;

                $time = now()->subHours(rand(1, 24));
                $product->created_at = $time;
                $product->pushed_at = $time;

                $product->save();

                $categoryImages = $this->getImagesForCategory($categoryName);
                foreach (range(1, rand(1, 3)) as $_) {
                    $this->addRandomImage($product, $categoryImages);
                }
            });
        });
    }

    private function loadJsonData(): void
    {
        $seedDir = storage_path('app/seed-samples');

        $namesPath = $seedDir . '/item_nama_produk.json';
        $sifatPath = $seedDir . '/item_sifat_produk.json';

        if (file_exists($namesPath)) {
            $data = json_decode(file_get_contents($namesPath), true) ?? [];
            foreach ($data as $kategori => $names) {
                $this->productNames[mb_strtolower($kategori)] = $names;
            }
        }

        if (file_exists($sifatPath)) {
            $data = json_decode(file_get_contents($sifatPath), true) ?? [];
            foreach ($data as $kategori => $sifat) {
                $this->productSifat[mb_strtolower($kategori)] = $sifat;
            }
        }
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

    private function generateTitleAndSlug(string $categoryName): array
    {
        $key = mb_strtolower($categoryName);

        $names = $this->productNames[$key] ?? [];
        $sifat = $this->productSifat[$key] ?? [];

        if (!empty($names) && !empty($sifat)) {
            $nama = $names[array_rand($names)];
            $sif = $sifat[array_rand($sifat)];
            $title = "{$nama} {$sif}";
        } elseif (!empty($names)) {
            $title = $names[array_rand($names)];
        } else {
            $title = fake()->words(3, true);
        }

        $slug = Str::slug($title) . '-' . rand(100, 999);

        return [$title, $slug];
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

    protected function addRandomImage(Product $product, array $files = []): void
    {
        if (empty($files)) {
            $files = $this->getFallbackFiles();
        }

        if (empty($files)) {
            logger()->error('NO FILES FOUND');
            return;
        }

        $fullPath = $files[array_rand($files)];
        $tempPath = null;

        try {
            $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
            $randomName = 'product-seed-' . bin2hex(random_bytes(8));
            $tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $randomName;

            if ($extension !== '') {
                $tempPath .= '.' . $extension;
            }

            if (file_exists($tempPath)) {
                throw new \RuntimeException('Tidak dapat membuat file sementara untuk gambar produk.');
            }

            if (! copy($fullPath, $tempPath)) {
                throw new \RuntimeException('Tidak dapat menyalin gambar seed untuk produk.');
            }

            $media = $product
                ->addMedia($tempPath)
                ->toMediaCollection('products');

            if (blank($media->uuid)) {
                $media->forceFill([
                    'uuid' => (string) Str::uuid(),
                ])->save();
            }
        } catch (\Throwable $e) {
            logger()->error('MEDIA FAILED', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            if ($tempPath && is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
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
