<?php

use App\Filament\Resources\Products\Pages\ListProducts;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

require_once __DIR__ . '/helpers.php';

describe('Product Table Actions', function () {

    // ── Push ──────────────────────────────────────────────────────────────

    it('shows push action for regular user', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak);

        $this->actingAs($user);

        livewire(ListProducts::class)
            ->assertTableActionVisible('push', $product);
    });

    it('hides push action for admin', function () {
        $admin   = makeUser(isAdmin: true);
        $user    = makeUser();
        $product = makeProduct($user->lapak);

        $this->actingAs($admin);

        livewire(ListProducts::class)
            ->assertTableActionHidden('push', $product);
    });

    // ── Request Reactivation ───────────────────────────────────────────────

    it('shows requestReactivation action for inactive product', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak, ['is_active' => false]);

        $this->actingAs($user);

        livewire(ListProducts::class)
            ->assertTableActionVisible('requestReactivation', $product);
    });

    it('hides requestReactivation action for active product', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak, ['is_active' => true]);

        $this->actingAs($user);

        livewire(ListProducts::class)
            ->assertTableActionHidden('requestReactivation', $product);
    });

    it('disables requestReactivation when pending moderation exists', function () {
        $user    = makeUser();
        $product = makeProduct($user->lapak, ['is_active' => false]);

        makePendingModeration($product);

        $this->actingAs($user);

        livewire(ListProducts::class)
            ->assertTableActionDisabled('requestReactivation', $product);
    });

    // ── Admin: Approve / Reject ────────────────────────────────────────────

    it('shows approveReactivation action for admin on pending product', function () {
        $admin   = makeUser(isAdmin: true);
        $user    = makeUser();
        $product = makeProduct($user->lapak, ['is_active' => false]);

        makePendingModeration($product);

        $this->actingAs($admin);

        livewire(ListProducts::class)
            ->assertTableActionVisible('approveReactivation', $product);
    });

    it('shows rejectReactivation action for admin on pending product', function () {
        $admin   = makeUser(isAdmin: true);
        $user    = makeUser();
        $product = makeProduct($user->lapak, ['is_active' => false]);

        makePendingModeration($product);

        $this->actingAs($admin);

        livewire(ListProducts::class)
            ->assertTableActionVisible('rejectReactivation', $product);
    });

    it('hides approveReactivation when no pending request', function () {
        $admin   = makeUser(isAdmin: true);
        $user    = makeUser();
        $product = makeProduct($user->lapak, ['is_active' => false]);

        $this->actingAs($admin);

        livewire(ListProducts::class)
            ->assertTableActionHidden('approveReactivation', $product);
    });
});