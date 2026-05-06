<?php

use App\Models\User;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Filament\Pages\SiteSettingsPage;

uses(RefreshDatabase::class);

// ==================== Setup ====================

beforeEach(function () {
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