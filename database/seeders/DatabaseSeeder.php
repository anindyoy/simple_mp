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
use Illuminate\Support\Facades\Storage;
use Database\Seeders\WhatsappAgentSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * @var array<int, string>|null
     */
    private ?array $tokenBankAccountNumbers = null;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Membersihkan file media lama...');
        foreach (Storage::disk('public')->directories() as $dir) {
            Storage::disk('public')->deleteDirectory($dir);
        }

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
            Category::insert([
                ['category_name' => 'Makanan',        'supports_condition' => false],
                ['category_name' => 'Minuman',        'supports_condition' => false],
                ['category_name' => 'Pakaian',        'supports_condition' => true],
                ['category_name' => 'Elektronik',     'supports_condition' => true],
                ['category_name' => 'Otomotif',       'supports_condition' => true],
                ['category_name' => 'Jasa',           'supports_condition' => false],
                ['category_name' => 'Properti',       'supports_condition' => true],
                ['category_name' => 'Buah & Sayuran', 'supports_condition' => false],
                ['category_name' => 'Gadget',         'supports_condition' => false],
                ['category_name' => 'Suplemen',       'supports_condition' => true],
                ['category_name' => 'Lainnya',        'supports_condition' => false],
            ]);
        }

        $this->command->info('Membuat setting default...');
        $settings = $this->buildDefaultSettings();

        $now = now();

        Setting::query()->upsert(
            array_map(fn(array $setting) => [
                ...$setting,
                'created_at' => $now,
                'updated_at' => $now,
            ], $settings),
            ['key'],
            ['value', 'updated_at']
        );

        $this->command->info('Membuat user non admin...');
        $users = User::factory(10)->create();

        $this->command->info('Membuat lapak...');
        $users->each(function (User $user) {
            LapakProfile::factory()->create([
                'user_id' => $user->id,
            ]);
        });

        $this->command->info('Membuat produk...');
        $this->call([ProductSeeder::class]);

        $this->command->info('Membuat laporan...');
        Report::factory()->count(20)->create();

        $this->command->info('Membuat riwayat pembelian token...');
        $bankAccountNumbers = $this->getTokenBankAccountNumbers();

        TokenPurchase::factory()
            ->count(30)
            ->state(fn() => [
                'bank_account' => fake()->randomElement($bankAccountNumbers),
            ])
            ->create();

        $this->command->info('Membuat pembelian token yang dikonfirmasi...');
        $this->createConfirmedTokenPurchases();

        $this->command->info('Membuat agent WhatsApp default...');
        $this->call([WhatsappAgentSeeder::class]);
    }

    /**
     * Build default settings array used by the seeder.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildDefaultSettings(): array
    {
        $externalLinkLabels = Setting::factory()->externalLinkLabels()->make();
        $userRulesContent = Setting::factory()->userRulesContent()->make();

        return [
            ['key' => 'lapak_external_link_labels', 'value' => $externalLinkLabels->value],
            ['key' => 'user_rules_content', 'value' => $userRulesContent->value],
            ['key' => 'weekly_minimum_push_tokens', 'value' => '3'],
            ['key' => 'initial_push_tokens', 'value' => '10'],
            ['key' => 'token_price', 'value' => '2000'],
            ['key' => 'min_tokens_for_normal_price', 'value' => '5'],
            ['key' => 'token_purchase_whatsapp', 'value' => config('app.hp_admin')],
            [
                'key' => 'token_bank_accounts',
                'value' => json_encode([
                    [
                        'bank_name' => 'BCA',
                        'account_number' => '1234567890',
                        'account_holder' => 'PT SimpleMP',
                    ],
                    [
                        'bank_name' => 'Mandiri',
                        'account_number' => '9876543210',
                        'account_holder' => 'PT SimpleMP',
                    ],
                    [
                        'bank_name' => 'BNI',
                        'account_number' => '1122334455',
                        'account_holder' => 'PT SimpleMP',
                    ],
                ], JSON_UNESCAPED_UNICODE),
            ],
        ];
    }

    /**
     * Create confirmed token purchases and add tokens to users
     */
    private function createConfirmedTokenPurchases(): void
    {
        $bankAccountNumbers = $this->getTokenBankAccountNumbers();

        User::query()
            ->select('id')
            ->chunkById(200, function ($users) use ($bankAccountNumbers) {
                foreach ($users as $user) {
                    $purchases = TokenPurchase::factory()
                        ->count(2)
                        ->confirmed()
                        ->state(fn() => [
                            'bank_account' => fake()->randomElement($bankAccountNumbers),
                        ])
                        ->create(['user_id' => $user->id]);

                    $totalConfirmedTokens = (int) $purchases->sum('quantity');

                    if ($totalConfirmedTokens > 0) {
                        User::whereKey($user->id)->increment('push_tokens', $totalConfirmedTokens);
                    }
                }
            });
    }

    /**
     * @return array<int, string>
     */
    private function getTokenBankAccountNumbers(): array
    {
        if ($this->tokenBankAccountNumbers !== null) {
            return $this->tokenBankAccountNumbers;
        }

        $stored = Setting::getValue('token_bank_accounts');
        $decoded = is_string($stored) ? json_decode($stored, true) : [];

        $numbers = collect($decoded)
            ->filter(fn($account) => is_array($account) && !empty($account['account_number']))
            ->pluck('account_number')
            ->map(fn($number) => (string) $number)
            ->unique()
            ->values()
            ->all();

        $this->tokenBankAccountNumbers = !empty($numbers)
            ? $numbers
            : ['1234567890', '9876543210', '1122334455'];

        return $this->tokenBankAccountNumbers;
    }
}
