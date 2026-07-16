<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\LapakProfile;
use App\Traits\AttachesProductImages;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    use AttachesProductImages;

    /** @var array<string, array{min: int, max: int}>|null */
    protected static ?array $priceRanges = null;

    public function definition(): array
    {
        $title = $this->faker->words(3, true);

        $category = Category::query()->inRandomOrder()->first()
            ?? Category::factory()->create();

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . rand(100, 999),
            'category_id' => $category->id,

            'lapak_id' => LapakProfile::query()->inRandomOrder()->value('id')
                ?? LapakProfile::factory(),

            'description' => $this->faker->paragraph(3),
            'price' => $this->priceForCategory($category->category_name),
            'is_active' => true,
            'pushed_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
            'can_be_delivered' => $this->faker->boolean(50), // 50% kemungkinan bisa diantar
            'views_count' => rand(0, 100),
        ];
    }

    /**
     * Paksa produk memakai kategori tertentu, sekaligus menyesuaikan
     * harga agar tetap konsisten dengan kategori tersebut.
     */
    public function forCategory(Category $category): static
    {
        return $this->state(fn() => [
            'category_id' => $category->id,
            'price' => $this->priceForCategory($category->category_name),
        ]);
    }

    protected function priceForCategory(?string $categoryName): int
    {
        $range = self::loadPriceRanges()[mb_strtolower($categoryName ?? '')]
            ?? ['min' => 10_000, 'max' => 2_000_000];

        return (int) round($this->faker->numberBetween($range['min'], $range['max']), -3);
    }

    /** @return array<string, array{min: int, max: int}> */
    protected static function loadPriceRanges(): array
    {
        if (self::$priceRanges !== null) {
            return self::$priceRanges;
        }

        $path = storage_path('app/seed-samples/item_produk.json');
        $data = file_exists($path) ? (json_decode(file_get_contents($path), true) ?? []) : [];

        self::$priceRanges = collect($data)
            ->mapWithKeys(fn($attributes, $kategori) => [
                mb_strtolower($kategori) => $attributes['harga'] ?? ['min' => 10_000, 'max' => 2_000_000],
            ])
            ->all();

        return self::$priceRanges;
    }

    public function configure(): static
    {
        return $this->afterMaking(function ($product) {
            $this->syncCondition($product);
        })->afterCreating(function ($product) {
            $this->syncCondition($product);
            $product->saveQuietly();

            // Generate gambar random 1-3
            $this->attachRandomImages($product);
        });
    }

    public function withoutImages(): static
    {
        return $this->afterCreating(fn() => null); // no-op, skip image generation
    }

    protected function syncCondition($product): void
    {
        $category = $product->category;

        $product->condition = $category?->supportsCondition()
            ? fake()->randomElement(['baru', 'seken'])
            : null;
    }
}
