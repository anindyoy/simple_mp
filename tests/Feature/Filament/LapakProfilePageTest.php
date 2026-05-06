<?php

use App\Models\User;
use Livewire\Livewire;
use App\Models\Setting;
use App\Filament\Pages\LapakProfile;
use App\Models\LapakProfile as ModelLapakProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // default user non-admin
    $this->user = User::factory()->create([
        'is_admin' => false,
    ]);

    $this->actingAs($this->user);
});


// ==================== ACCESS TEST ====================

it('non admin can access lapak profile page', function () {
    Livewire::test(LapakProfile::class)
        ->assertStatus(200);
});

it('admin cannot see navigation', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    $this->actingAs($admin);

    expect(LapakProfile::shouldRegisterNavigation())->toBeFalse();
});


// ==================== MOUNT TEST ====================

it('creates new lapak instance if not exists', function () {
    $component = Livewire::test(LapakProfile::class);

    expect($component->instance()->lapak)->not->toBeNull();
    expect($component->instance()->lapak->exists)->toBeFalse();
});

it('loads existing lapak data on mount', function () {
    $lapak = ModelLapakProfile::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Lapak Lama',
    ]);

    $component = Livewire::test(LapakProfile::class);

    expect($component->instance()->lapak->id)->toBe($lapak->id);
});


// ==================== FORM TEST ====================

it('form contains required fields', function () {
    $component = Livewire::test(LapakProfile::class);

    $component
        ->assertFormFieldExists('name')
        ->assertFormFieldExists('address_raw')
        ->assertFormFieldExists('whatsapp_number')
        ->assertFormFieldExists('telegram_username')
        ->assertFormFieldExists('external_links')
        ->assertFormFieldExists('profile_image');
});


// ==================== CREATE TEST ====================

it('can create lapak profile', function () {
    Livewire::test(LapakProfile::class)
        ->fillForm([
            'name' => 'Lapak Baru',
            'address_raw' => 'Bandung',
            'whatsapp_number' => '08123456789',
            'telegram_username' => 'lapakbaru',
            'external_links' => [
                [
                    'label' => 'Website',
                    'link' => 'https://example.com',
                ],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('lapak_profiles', [
        'name' => 'Lapak Baru',
        'user_id' => $this->user->id,
    ]);
});


// ==================== UPDATE TEST ====================

it('can update existing lapak profile', function () {
    $lapak = ModelLapakProfile::factory()->create([
        'user_id' => $this->user->id,
        'name' => 'Old Name',
    ]);

    Livewire::test(LapakProfile::class)
        ->fillForm([
            'name' => 'Updated Name',
            'address_raw' => 'Jakarta',
            'whatsapp_number' => '08111111111',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($lapak->fresh()->name)->toBe('Updated Name');
});


// ==================== SETTINGS / LABEL TEST ====================

it('uses default external link labels when setting empty', function () {
    Setting::query()->delete();

    $component = Livewire::test(LapakProfile::class);

    $labels = invade($component->instance())->getExternalLinkLabelOptions();

    expect($labels)->toHaveKey('Website');
    expect($labels)->toHaveKey('Shopee');
});

it('uses stored external link labels when available', function () {
    Setting::setValue('lapak_external_link_labels', json_encode(['Tokopedia', 'Custom']));

    $component = Livewire::test(LapakProfile::class);

    $labels = invade($component->instance())->getExternalLinkLabelOptions();

    expect($labels)->toHaveKey('Tokopedia');
    expect($labels)->toHaveKey('Custom');
});


// ==================== HELPER TEST ====================

it('generates lapak name suggestions', function () {
    $component = Livewire::test(LapakProfile::class);

    $suggestions = invade($component->instance())->getLapakNameSuggestions();

    expect($suggestions)->toBeArray();
    expect($suggestions[0])->toContain('Toko');
});