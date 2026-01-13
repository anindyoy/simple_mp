<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $categories = ['Makanan', 'Fashion', 'Elektronik', 'Otomotif', 'Jasa'];
        foreach ($categories as $cat) {
            \App\Models\Category::create(['category_name' => $cat]);
        }

        // Buat 10 Produk beserta Penjual dan Gambarnya
        \App\Models\Product::factory(10)
            ->has(\App\Models\ProductImage::factory()->count(3), 'images')
            ->create([
                'category_id' => fn() => \App\Models\Category::inRandomOrder()->first()->id
            ]);
    }
}
