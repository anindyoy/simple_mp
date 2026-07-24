<?php

use App\Models\User;
use App\Filament\Pages\Dashboard;
use Filament\Notifications\Notification;
use Livewire\Livewire;

describe('EnsureLapakProfileExists Middleware', function () {

    // ==================== DASHBOARD ACCESS ====================

    it('allows user without lapak to access dashboard', function () {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->actingAs($user);

        $this->get(route('filament.admin.pages.dashboard'))
            ->assertOk();
    });

    it('allows user with lapak to access dashboard', function () {
        $user = makeUser();

        $this->actingAs($user);

        $this->get(route('filament.admin.pages.dashboard'))
            ->assertOk();
    });

    it('allows admin to access dashboard', function () {
        $admin = makeUser(isAdmin: true);

        $this->actingAs($admin);

        $this->get(route('filament.admin.pages.dashboard'))
            ->assertOk();
    });

    // ==================== PRODUCT LIST REDIRECT ====================

    it('redirects user without lapak to lapak-profile when accessing product list', function () {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->actingAs($user);

        $this->get(route('filament.admin.resources.products.index'))
            ->assertRedirect(route('filament.admin.pages.lapak-profile'));

        Notification::assertNotified('Lapak belum dibuat');
    });

    it('allows user with lapak to access product list', function () {
        $user = makeUser();

        $this->actingAs($user);

        $this->get(route('filament.admin.resources.products.index'))
            ->assertOk();
    });

    it('allows admin to access product list', function () {
        $admin = makeUser(isAdmin: true);

        $this->actingAs($admin);

        $this->get(route('filament.admin.resources.products.index'))
            ->assertOk();
    });

    // ==================== DASHBOARD NOTIFICATION ====================

    it('shows notification on dashboard when user has no lapak', function () {
        $user = User::factory()->create([
            'is_admin' => false,
        ]);

        $this->actingAs($user);

        Livewire::test(Dashboard::class);

        Notification::assertNotified('Lapak belum dibuat');
    });

    it('does not show notification on dashboard when user has lapak', function () {
        $user = makeUser();

        $this->actingAs($user);

        Livewire::test(Dashboard::class);

        Notification::assertNotNotified('Lapak belum dibuat');
    });
});
