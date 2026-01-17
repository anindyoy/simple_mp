<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Support\Str;
use App\Models\LapakProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Ambil atau buat kategori
        $category = Category::inRandomOrder()->first()
            ?? Category::factory()->create();

        return [
            'category_id' => $category->id,

            'title' => $title = $this->faker->words(3, true),
            'slug' => Str::slug($title) . '-' . rand(100, 999),

            'description' => $this->faker->paragraph(3),
            'price' => $this->faker->numberBetween(10_000, 2_000_000),

            // 👉 logic kondisi produk
            'condition' => $category->supportsCondition()
                ? $this->faker->randomElement(['baru', 'seken'])
                : null,

            'is_active' => true,
            'pushed_at' => $this->faker->dateTimeBetween('-24 hours', 'now'),
        ];
    }
}
