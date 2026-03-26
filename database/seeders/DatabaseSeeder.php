<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Report;
use App\Models\Setting;
use App\Models\Category;
use App\Models\LapakProfile;
use App\Models\TokenPurchase;
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

        Setting::updateOrCreate(
            ['key' => 'weekly_minimum_push_tokens'],
            ['value' => '3']
        );

        Setting::updateOrCreate(
            ['key' => 'initial_push_tokens'],
            ['value' => '10']
        );

        Setting::updateOrCreate(
            ['key' => 'token_price'],
            ['value' => '2000']
        );

        Setting::updateOrCreate(
            ['key' => 'min_tokens_for_normal_price'],
            ['value' => '5']
        );

        Setting::updateOrCreate(
            ['key' => 'token_purchase_whatsapp'],
            ['value' => '62812345678']
        );

        $this->command->info('Membuat user non admin...');
        User::factory(10)->create();

        $this->command->info('Membuat lapak...');
        LapakProfile::factory()->count(10)->create();

        $this->command->info('Membuat produk...');
        $this->call([ProductSeeder::class]);

        $this->command->info('Membuat laporan...');
        Report::factory()->count(20)->create();

        $this->command->info('Membuat riwayat pembelian token...');
        TokenPurchase::factory()->count(30)->create();

        $this->command->info('Membuat pembelian token yang dikonfirmasi...');
        $this->createConfirmedTokenPurchases();
    }

    /**
     * Create confirmed token purchases and add tokens to users
     */
    private function createConfirmedTokenPurchases(): void
    {
        User::all()->each(function (User $user) {
            TokenPurchase::factory()
                ->count(2)
                ->confirmed()
                ->create(['user_id' => $user->id])
                ->each(function (TokenPurchase $purchase) {
                    $purchase->user->addTokens($purchase->quantity);
                });
        });
    }
}
