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
        return [
            'lapak_id' => LapakProfile::factory(),
            'category_id' => Category::factory(),
            'title' => $title = $this->faker->words(3, true),
            'slug' => Str::slug($title) . '-' . rand(100, 999),
            'description' => $this->faker->paragraph(3), // Narasi produk
            'price' => $this->faker->numberBetween(10000, 2000000),
            'is_active' => true,
            'pushed_at' => $this->faker->dateTimeBetween('-24 hours', 'now'), // Simulasi sundul
        ];
    }
}
