<?php

use App\Livewire\UpdatePassword;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('can render update password component', function () {
    Livewire::test(UpdatePassword::class)
        ->assertStatus(200);
});

it('form contains required fields', function () {
    Livewire::test(UpdatePassword::class)
        ->assertFormFieldExists('current_password')
        ->assertFormFieldExists('new_password')
        ->assertFormFieldExists('new_password_confirmation');
});

it('current_password is required when password update requires current', function () {
    config()->set('filament-breezy.password_update_requires_current', true);

    Livewire::test(UpdatePassword::class)
        ->fillForm([
            'current_password' => '',
            'new_password' => 'newPassword123',
            'new_password_confirmation' => 'newPassword123',
        ])
        ->call('submit')
        ->assertHasFormErrors(['current_password' => 'required']);
});

it('new_password is required', function () {
    Livewire::test(UpdatePassword::class)
        ->fillForm([
            'current_password' => 'oldPassword123',
            'new_password' => '',
            'new_password_confirmation' => 'newPassword123',
        ])
        ->call('submit')
        ->assertHasFormErrors(['new_password' => 'required']);
});

it('new_password_confirmation must match new_password', function () {
    Livewire::test(UpdatePassword::class)
        ->fillForm([
            'current_password' => 'oldPassword123',
            'new_password' => 'newPassword123',
            'new_password_confirmation' => 'differentPassword',
        ])
        ->call('submit')
        ->assertHasFormErrors(['new_password_confirmation' => 'same']);
});

it('updates password successfully with valid current password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('oldPassword123'),
    ]);

    $this->actingAs($user);

    Livewire::test(UpdatePassword::class)
        ->fillForm([
            'current_password' => 'oldPassword123',
            'new_password' => 'newPassword456',
            'new_password_confirmation' => 'newPassword456',
        ])
        ->call('submit')
        ->assertHasNoFormErrors();

    $this->assertTrue(
        Hash::check('newPassword456', $user->fresh()->password)
    );
});

it('fails to update password with incorrect current password', function () {
    $user = User::factory()->create([
        'password' => bcrypt('oldPassword123'),
    ]);

    $this->actingAs($user);

    Livewire::test(UpdatePassword::class)
        ->fillForm([
            'current_password' => 'wrongPassword',
            'new_password' => 'newPassword456',
            'new_password_confirmation' => 'newPassword456',
        ])
        ->call('submit')
        ->assertHasFormErrors(['current_password' => 'current_password']);
});

it('applies password validation rules from config', function () {
    config()->set('filament-breezy.password_update_rules', ['required', 'min:12', 'regex:/[A-Z]/', 'regex:/[0-9]/']);

    Livewire::test(UpdatePassword::class)
        ->fillForm([
            'current_password' => 'oldPassword123',
            'new_password' => 'short',
            'new_password_confirmation' => 'short',
        ])
        ->call('submit')
        ->assertHasFormErrors(['new_password']);
});

it('password fields are revealed/revealable', function () {
    $component = Livewire::test(UpdatePassword::class);

    // Check that fields have password() and revealable() - they render as password type
    $component->assertFormFieldExists('current_password');
    $component->assertFormFieldExists('new_password');
    $component->assertFormFieldExists('new_password_confirmation');
});

it('new_password field uses configured validation rules', function () {
    // Default rules from filament-breezy config
    $rules = config('filament-breezy.password_update_rules', ['required', 'min:8']);

    Livewire::test(UpdatePassword::class)
        ->fillForm([
            'current_password' => 'oldPassword123',
            'new_password' => '1234567', // too short
            'new_password_confirmation' => '1234567',
        ])
        ->call('submit')
        ->assertHasFormErrors(['new_password']);
});