<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\Category;
use App\Models\LapakProfile;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Jalankan seeder produk
     */
    public function run(): void
    {
        $categories = Category::all();
        $existingLapaks = LapakProfile::all();

        Product::factory(50)->make()->each(function ($product) use ($categories, $existingLapaks) {
            // Tentukan kategori random
            $category = $categories->random();
            $product->category_id = $category->id;

            $product->condition = $category->supportsCondition()
                ? fake()->randomElement(['baru', 'seken'])
                : null;

            if ($existingLapaks->isNotEmpty() && rand(0, 2) != 0) {
                $product->lapak_id = $existingLapaks->random()->id;
            } else {
                $product->lapak_id = LapakProfile::factory()->create()->id;
            }

            // Simpan produk ke DB
            $product->save();

            // Buat 1-3 gambar untuk produk ini
            ProductImage::factory(rand(1, 3))->create([
                'product_id' => $product->id,
            ]);
        });
    }
}
