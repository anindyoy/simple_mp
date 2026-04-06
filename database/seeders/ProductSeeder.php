<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\LapakProfile;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    protected ?int $count;
    protected ?string $mode;

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

        Product::factory(50)->make()->each(function ($product) use ($categories, $existingLapaks) {

            $category = $categories->random();
            $product->category_id = $category->id;

            $product->condition = $category->supportsCondition()
                ? fake()->randomElement(['baru', 'seken'])
                : null;

            $product->lapak_id = $existingLapaks->isNotEmpty()
                ? $existingLapaks->random()->id
                : LapakProfile::factory()->create()->id;

            $time = now()->subHours(rand(1, 24));

            $product->created_at = $time;

            $product->save();

            ProductImage::factory(rand(1, 3))->create([
                'product_id' => $product->id,
            ]);
        });
    }

    /**
     * MODE 2 — Generate sedikit produk untuk testing
     */
    protected function runTestingSeeder(int $total): void
    {
        $categories = Category::all();

        if ($categories->isEmpty()) return;

        $lapak = LapakProfile::first() ?? LapakProfile::factory()->create();

        Product::factory($total)->make()->each(function ($product) use ($categories, $lapak) {

            $category = $categories->random();

            $product->category_id = $category->id;
            $product->lapak_id = $lapak->id;

            $product->condition = $category->supportsCondition()
                ? fake()->randomElement(['baru', 'seken'])
                : null;

            $time = now()->subHours(rand(1, 24));

            $product->created_at = $time;
            $product->pushed_at = $time;

            $product->save();

            ProductImage::factory()->create([
                'product_id' => $product->id,
            ]);
        });
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
