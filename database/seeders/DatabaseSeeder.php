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

        LapakProfile::factory()->count(10)->create();

        $this->call([ProductSeeder::class]);
    }
}
