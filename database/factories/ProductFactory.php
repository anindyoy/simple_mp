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

    public function definition(): array
    {
        $title = $this->faker->words(3, true);

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . rand(100, 999),

            'category_id' => Category::query()->inRandomOrder()->value('id')
                ?? Category::factory(),

            'lapak_id' => LapakProfile::query()->inRandomOrder()->value('id')
                ?? LapakProfile::factory(),

            'description' => $this->faker->paragraph(3),
            'price' => $this->faker->numberBetween(10_000, 2_000_000),

            'is_active' => true,
            'pushed_at' => $this->faker->dateTimeBetween('-3 days', 'now'),
            'can_be_delivered' => $this->faker->boolean(50), // 50% kemungkinan bisa diantar
        ];
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
