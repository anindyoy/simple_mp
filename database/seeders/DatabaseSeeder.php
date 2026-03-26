<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Report;
use App\Models\Setting;
use App\Models\Category;
use App\Models\LapakProfile;
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

        if (!User::whereIsAdmin(true)->exists()) {
            $this->command->info('Membuat user admin default...');
            User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@lapak.com',
                'password' => bcrypt('password'),
                'is_admin' => true
            ]);
        }

        if (!Category::exists()) {
            $this->command->info('Membuat kategori produk...');
            $categories = ['Makanan', 'Fashion', 'Elektronik', 'Otomotif', 'Jasa'];
            foreach ($categories as $cat) {
                Category::create(['category_name' => $cat]);
            }
        }

        $this->command->info('Membuat setting default...');
        Setting::updateOrCreate(
            ['key' => 'lapak_external_link_labels'],
            Setting::factory()->externalLinkLabels()->make()->toArray()
        );

        Setting::updateOrCreate(
            ['key' => 'user_rules_content'],
            Setting::factory()->userRulesContent()->make()->toArray()
        );

        $this->command->info('Membuat user non admin...');
        User::factory(10)->create();

        $this->command->info('Membuat lapak...');
        LapakProfile::factory()->count(10)->create();

        $this->command->info('Membuat produk...');
        $this->call([ProductSeeder::class]);

        $this->command->info('Membuat laporan...');
        Report::factory()->count(20)->create();
    }
}
