mengapa saat dijalankan perintah artisan migrate:fresh --seed
jumlah lapak profile 2x lipat jumlah produk?

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\LapakProfile;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Database\Seeders\ProductSeeder;
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
            Category::create(['category_name' => $cat]);
        }

        LapakProfile::factory()->count(10);

        $this->call([ProductSeeder::class]);
    }
}

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
            $product->category_id = $categories->random()->id;

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
