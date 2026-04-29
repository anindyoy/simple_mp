<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
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
        $disk = Storage::disk('public');

        $files = $disk->files('seed-samples');

        if (!empty($files)) {
            $source = $files[array_rand($files)];

            // ambil extension file
            $ext = pathinfo($source, PATHINFO_EXTENSION);

            // generate nama random
            $randomName = 'products/' . Str::uuid() . '.' . $ext;

            // copy file ke nama baru
            $disk->copy($source, $randomName);

            $path = $randomName;
        } else {
            $path = 'products/' . Str::uuid() . '.jpg';
        }

        return [
            'product_id' => Product::factory(),
            'image_url' => $path,
            'is_primary' => $this->faker->boolean(80),
        ];
    }
}
