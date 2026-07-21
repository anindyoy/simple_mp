<?php

use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Pages\SiteSettingsPage;
use Illuminate\Support\Facades\Cache;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

// ==================== Setup ====================

beforeEach(function () {
    Cache::flush();

    $this->admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $this->user = User::factory()->create([
        'is_admin' => false,
    ]);
});

// ==================== Access Control ====================

it('allows admin to access page', function () {
    $this->actingAs($this->admin);

    expect(SiteSettingsPage::canAccess())->toBeTrue();
    expect(SiteSettingsPage::shouldRegisterNavigation())->toBeTrue();
});

it('blocks non admin from accessing page', function () {
    $this->actingAs($this->user);

    expect(SiteSettingsPage::canAccess())->toBeFalse();
    expect(SiteSettingsPage::shouldRegisterNavigation())->toBeFalse();
});

// ==================== Save Logic ====================

it('saves settings correctly', function () {
    $this->actingAs($this->admin);

    $page = new SiteSettingsPage();

    $page->data = [
        'site_title' => 'My Site',
        'site_description' => 'Desc',
        'site_keywords' => 'a,b,c',
        'site_region' => 'Bandung',

        'daily_minimum_push_tokens' => 5,
        'weekly_minimum_push_tokens' => 7,
        'initial_push_tokens' => 20,

        'token_price' => 3000,
        'min_tokens_for_normal_price' => 10,
        'token_purchase_whatsapp' => '+628123',

        'rules_content' => 'Rules here',

        'token_bank_accounts' => [
            [
                'bank_name' => 'BCA',
                'account_number' => '123',
                'account_holder' => 'A',
            ],
            [
                // duplicate account_number → should be removed
                'bank_name' => 'BCA',
                'account_number' => '123',
                'account_holder' => 'A',
            ],
        ],

        'external_link_labels' => "Shopee\nTokopedia\nShopee",
    ];

    // mock form state
    $page->form = new class($page) {
        public function __construct(private $page) {}
        public function getState() {
            return $this->page->data;
        }
    };

    $page->save();

    expect(Setting::getValue('site_title'))->toBe('My Site');
    expect(Setting::getValue('site_region'))->toBe('Bandung');

    // numeric cast
    expect(Setting::getValue('daily_minimum_push_tokens'))->toBe('5');
    expect(Setting::getValue('min_tokens_for_normal_price'))->toBe('10');

    // bank accounts unique
    $banks = json_decode(Setting::getValue('token_bank_accounts'), true);
    expect($banks)->toHaveCount(1);

    // labels unique
    $labels = json_decode(Setting::getValue('lapak_external_link_labels'), true);
    expect($labels)->toBe(['Shopee', 'Tokopedia']);
});

// ==================== External Link Labels ====================

it('returns default labels if empty', function () {
    $this->actingAs($this->admin);

    $page = new SiteSettingsPage();

    $method = new ReflectionMethod($page, 'getExternalLinkLabels');
    $method->setAccessible(true);

    $labels = $method->invoke($page);

    expect($labels)->toContain('Shopee');
    expect($labels)->toContain('Tokopedia');
});

it('parses stored labels correctly', function () {
    $this->actingAs($this->admin);

    Setting::setValue(
        'lapak_external_link_labels',
        json_encode([' Shopee ', 'Tokopedia', 'Shopee'])
    );

    $page = new SiteSettingsPage();

    $method = new ReflectionMethod($page, 'getExternalLinkLabels');
    $method->setAccessible(true);

    $labels = $method->invoke($page);

    expect($labels)->toBe(['Shopee', 'Tokopedia']);
});

// ==================== Bank Accounts ====================

it('returns empty bank accounts if none stored', function () {
    $this->actingAs($this->admin);

    $page = new SiteSettingsPage();

    $method = new ReflectionMethod($page, 'getBankAccounts');
    $method->setAccessible(true);

    $accounts = $method->invoke($page);

    expect($accounts)->toBe([]);
});

it('filters invalid bank accounts', function () {
    $this->actingAs($this->admin);

    Setting::setValue(
        'token_bank_accounts',
        json_encode([
            [
                'bank_name' => 'BCA',
                'account_number' => '123',
                'account_holder' => 'A',
            ],
            [
                'bank_name' => 'BCA',
                // invalid (missing account_number)
            ],
        ])
    );

    $page = new SiteSettingsPage();

    $method = new ReflectionMethod($page, 'getBankAccounts');
    $method->setAccessible(true);

    $accounts = $method->invoke($page);

    expect($accounts)->toHaveCount(1);
});

// ==================== Security ====================

it('throws 403 when non admin mounts page', function () {
    $this->actingAs($this->user);

    $page = new SiteSettingsPage();

    expect(fn () => $page->mount())
        ->toThrow(\Symfony\Component\HttpKernel\Exception\HttpException::class);
});

// ==================== Mount ====================

describe('mount', function () {
    it('fills form with default values when no settings are stored', function () {
        $this->actingAs($this->admin);

        livewire(SiteSettingsPage::class)
            ->assertSet('data.site_title', 'Lapak Warga')
            ->assertSet('data.site_region', 'Cimanglid')
            ->assertSet('data.site_description', 'Marketplace online untuk warga. Jual beli produk dan jasa lokal dengan mudah.')
            ->assertSet('data.site_keywords', 'marketplace, jual beli online, produk lokal, warga, toko online')
            ->assertSet('data.product_schedule_delay_hours', 4)
            ->assertSet('data.show_visitor_count', true)
            ->assertSet('data.daily_minimum_push_tokens', 2)
            ->assertSet('data.weekly_minimum_push_tokens', 3)
            ->assertSet('data.initial_push_tokens', 10)
            ->assertSet('data.token_price', 2000)
            ->assertSet('data.min_tokens_for_normal_price', 5)
            ->assertSet('data.token_purchase_whatsapp', '')
            ->assertSet('data.token_bank_accounts', [])
            // RichEditor uses a TipTap StateCast: state is stored as a JSON doc array, not raw HTML.
            ->assertSet('data.rules_content', fn($state) => is_array($state) && ($state['type'] ?? '') === 'doc');
    });

    it('fills form with stored setting values', function () {
        Setting::setValue('site_title', 'Toko Desa');
        Setting::setValue('site_region', 'Desa Maju');
        Setting::setValue('product_schedule_delay_hours', '6');
        Setting::setValue('initial_push_tokens', '15');
        Setting::setValue('token_purchase_whatsapp', '+628123456789');

        $this->actingAs($this->admin);

        livewire(SiteSettingsPage::class)
            ->assertSet('data.site_title', 'Toko Desa')
            ->assertSet('data.site_region', 'Desa Maju')
            ->assertSet('data.product_schedule_delay_hours', 6)
            ->assertSet('data.initial_push_tokens', 15)
            ->assertSet('data.token_purchase_whatsapp', '+628123456789');
    });

    it('fills form with show_visitor_count false when stored as disabled', function () {
        Setting::setValue('show_visitor_count', '0');

        $this->actingAs($this->admin);

        livewire(SiteSettingsPage::class)
            ->assertSet('data.show_visitor_count', false);
    });

    it('loads token_bank_accounts from stored JSON', function () {
        Setting::setValue('token_bank_accounts', json_encode([
            ['bank_name' => 'BCA', 'account_number' => '123456', 'account_holder' => 'John Doe'],
        ]));

        $this->actingAs($this->admin);

        // Filament Repeater uses UUID string keys, so we use a callable to inspect values.
        livewire(SiteSettingsPage::class)
            ->assertSet('data.token_bank_accounts', function ($accounts) {
                $first = array_values($accounts)[0] ?? [];
                return count($accounts) === 1
                    && ($first['bank_name'] ?? null) === 'BCA'
                    && ($first['account_number'] ?? null) === '123456'
                    && ($first['account_holder'] ?? null) === 'John Doe';
            });
    });

    it('formats external_link_labels as a newline-separated string from stored array', function () {
        Setting::setValue('lapak_external_link_labels', json_encode(['Shopee', 'Tokopedia', 'Custom']));

        $this->actingAs($this->admin);

        livewire(SiteSettingsPage::class)
            ->assertSet('data.external_link_labels', "Shopee\nTokopedia\nCustom");
    });

    it('uses default external link labels when none are stored', function () {
        $this->actingAs($this->admin);

        livewire(SiteSettingsPage::class)
            ->assertSet('data.external_link_labels', "Website\nShopee\nTokopedia\nTiktok\nInstagram\nFacebook");
    });

    it('maps rules_content from the user_rules_content setting key', function () {
        Setting::setValue('user_rules_content', '<p>Peraturan 1</p>');

        $this->actingAs($this->admin);

        // The RichEditor StateCast converts HTML to a TipTap doc array in Livewire state.
        // We verify the text "Peraturan 1" is present in the serialized document.
        livewire(SiteSettingsPage::class)
            ->assertSet('data.rules_content', fn($state) =>
                is_array($state) && str_contains(json_encode($state), 'Peraturan 1')
            );
    });
});

// ==================== Form ====================

describe('form', function () {
    it('renders without error', function () {
        $this->actingAs($this->admin);

        livewire(SiteSettingsPage::class)
            ->assertSuccessful();
    });

    it('validates required text fields on save', function () {
        $this->actingAs($this->admin);

        livewire(SiteSettingsPage::class)
            ->fillForm([
                'site_title'              => '',
                'site_region'             => '',
                'site_description'        => '',
                'site_keywords'           => '',
                'token_purchase_whatsapp' => '',
                'rules_content'           => '',
            ])
            ->call('save')
            ->assertHasFormErrors([
                'site_title',
                'site_region',
                'site_description',
                'site_keywords',
                'token_purchase_whatsapp',
                'rules_content',
            ]);
    });

    it('validates that numeric fields must be numeric', function () {
        $this->actingAs($this->admin);

        livewire(SiteSettingsPage::class)
            ->fillForm([
                'product_schedule_delay_hours' => 'bukan-angka',
                'daily_minimum_push_tokens'    => 'bukan-angka',
                'token_price'                  => 'bukan-angka',
            ])
            ->call('save')
            ->assertHasFormErrors([
                'product_schedule_delay_hours',
                'daily_minimum_push_tokens',
                'token_price',
            ]);
    });

    it('validates that product_schedule_delay_hours must be at least 1', function () {
        $this->actingAs($this->admin);

        livewire(SiteSettingsPage::class)
            ->fillForm(['product_schedule_delay_hours' => 0])
            ->call('save')
            ->assertHasFormErrors(['product_schedule_delay_hours']);
    });

    it('validates that min_tokens_for_normal_price must be at least 1', function () {
        $this->actingAs($this->admin);

        livewire(SiteSettingsPage::class)
            ->fillForm(['min_tokens_for_normal_price' => 0])
            ->call('save')
            ->assertHasFormErrors(['min_tokens_for_normal_price']);
    });

    it('saves successfully with valid data', function () {
        $this->actingAs($this->admin);

        livewire(SiteSettingsPage::class)
            ->fillForm([
                'site_title'                   => 'Judul Valid',
                'site_region'                  => 'Wilayah Valid',
                'site_description'             => 'Deskripsi valid untuk site ini.',
                'site_keywords'                => 'keyword, valid',
                'product_schedule_delay_hours' => 3,
                'daily_minimum_push_tokens'    => 1,
                'weekly_minimum_push_tokens'   => 2,
                'initial_push_tokens'          => 5,
                'token_price'                  => 1000,
                'min_tokens_for_normal_price'  => 3,
                'token_purchase_whatsapp'      => '+628123456789',
                'rules_content'                => '<p>Peraturan</p>',
                'external_link_labels'         => "Shopee\nTokopedia",
                'token_bank_accounts'          => [
                    ['bank_name' => 'BCA', 'account_number' => '123', 'account_holder' => 'A'],
                ],
                'whatsapp_agents'              => [
                    [
                        'name' => 'Admin 1',
                        'gender' => 'pria',
                        'phone' => '62812345678',
                        'active' => true,
                    ],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect(Setting::getValue('site_title'))->toBe('Judul Valid');
        expect(Setting::getValue('site_region'))->toBe('Wilayah Valid');
    });

    it('menyimpan show_visitor_count sebagai "0" saat dimatikan', function () {
        $this->actingAs($this->admin);

        livewire(SiteSettingsPage::class)
            ->fillForm([
                'site_title'              => 'Judul Valid',
                'site_region'             => 'Wilayah Valid',
                'site_description'        => 'Deskripsi valid untuk site ini.',
                'site_keywords'           => 'keyword, valid',
                'token_purchase_whatsapp' => '+628123456789',
                'rules_content'           => '<p>Peraturan</p>',
                'show_visitor_count'      => false,
                'token_bank_accounts'     => [
                    ['bank_name' => 'BCA', 'account_number' => '123', 'account_holder' => 'A'],
                ],
                'whatsapp_agents'         => [
                    ['name' => 'Admin 1', 'gender' => 'pria', 'phone' => '62812345678', 'active' => true],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect(Setting::getValue('show_visitor_count'))->toBe('0');
    });

    it('menyimpan show_visitor_count sebagai "1" secara default', function () {
        $this->actingAs($this->admin);

        livewire(SiteSettingsPage::class)
            ->fillForm([
                'site_title'              => 'Judul Valid',
                'site_region'             => 'Wilayah Valid',
                'site_description'        => 'Deskripsi valid untuk site ini.',
                'site_keywords'           => 'keyword, valid',
                'token_purchase_whatsapp' => '+628123456789',
                'rules_content'           => '<p>Peraturan</p>',
                'token_bank_accounts'     => [
                    ['bank_name' => 'BCA', 'account_number' => '123', 'account_holder' => 'A'],
                ],
                'whatsapp_agents'         => [
                    ['name' => 'Admin 1', 'gender' => 'pria', 'phone' => '62812345678', 'active' => true],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect(Setting::getValue('show_visitor_count'))->toBe('1');
    });
});