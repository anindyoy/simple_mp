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
        $seedDir = public_path('img/seed-samples');
        $files = array_filter(scandir($seedDir), fn($f) => !in_array($f, ['.', '..']));

        if (!empty($files)) {
            $source = $files[array_rand($files)];
            $sourcePath = $seedDir . '/' . $source;

            // ambil extension file
            $ext = pathinfo($source, PATHINFO_EXTENSION);

            // generate nama random
            $randomName = 'products/' . Str::uuid() . '.' . $ext;

            // copy file ke storage disk
            $disk = Storage::disk('public');
            $disk->put($randomName, file_get_contents($sourcePath));

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
