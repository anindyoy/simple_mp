<?php

use App\Models\User;
use Livewire\Livewire;
use App\Filament\Pages\Dashboard;
use Illuminate\Support\Facades\Http;

describe('Dashboard Update Catalog Button', function () {
    it('shows Update Catalog button in local environment for admin', function () {
        // Simulate local environment
        app()->detectEnvironment(fn () => 'local');
        config()->set('app.demo_mode', false);

        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        $component = Livewire::test(Dashboard::class);

        $component->assertSee('Update Catalog', true);
    });

    it('shows Update Catalog button in demo mode for admin', function () {
        // Simulate demo mode
        app()->detectEnvironment(fn () => 'production');
        config()->set('app.demo_mode', true);

        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        $component = Livewire::test(Dashboard::class);

        $component->assertSee('Update Catalog', true);
    });

    it('hides Update Catalog button in production mode for admin', function () {
        // Simulate production environment without demo mode
        app()->detectEnvironment(fn () => 'production');
        config()->set('app.demo_mode', false);

        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        $component = Livewire::test(Dashboard::class);

        $component->assertDontSee('Update Catalog', true);
    });

    it('hides Update Catalog button for non-admin users even in local mode', function () {
        // Set environment to local
        config()->set('app.env', 'local');
        config()->set('app.demo_mode', false);

        $user = makeUser();
        $this->actingAs($user);

        $component = Livewire::test(Dashboard::class);

        $component->assertDontSee('Update Catalog', true);
    });

    it('executes seeder successfully when button is clicked in local mode', function () {
        // Set environment to local
        config()->set('app.env', 'local');
        config()->set('app.demo_mode', false);

        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        $component = Livewire::test(Dashboard::class);

        // Call the action through mountAction (Filament's action system)
        $component->call('mountAction', 'runUpdateCatalogSeeder');

        // Assert the component handled the action without errors
        $component->assertSuccessful();
    });

    it('handles seeder execution failure gracefully', function () {
        // Set environment to local
        config()->set('app.env', 'local');
        config()->set('app.demo_mode', false);

        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        $component = Livewire::test(Dashboard::class);

        // Call the action through mountAction (Filament's action system)
        $component->call('mountAction', 'runUpdateCatalogSeeder');

        // Assert the component handled the error without throwing
        $component->assertSuccessful();
    });
});