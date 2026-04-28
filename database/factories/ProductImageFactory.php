<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductImage>
 */
class ProductImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Download a placeholder image and store it in the public disk so
        // Filament's ImageColumn with ->disk('public') can resolve it.
        $files = Storage::disk('public')->files('seed-samples');
        $path = !empty($files) ? $files[array_rand($files)] : 'products/placeholder.jpg';

        return [
            'product_id' => Product::factory(),
            'image_url' => $path,
            'is_primary' => $this->faker->boolean(80),
        ];
    }
}
