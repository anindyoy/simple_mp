<?php

use App\Filament\Widgets\AdminPendingTokenPurchasesWidget;
use App\Models\TokenPurchase;
use Livewire\Livewire;

describe('canView', function () {
    it('is visible for admin only', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);
        expect(AdminPendingTokenPurchasesWidget::canView())->toBeTrue();

        $seller = makeUser();
        $this->actingAs($seller);
        expect(AdminPendingTokenPurchasesWidget::canView())->toBeFalse();
    });
});

describe('table', function () {
    it('lists only pending purchases', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();

        $pending = TokenPurchase::factory()->pending()->create(['user_id' => $seller->id]);
        $confirmed = TokenPurchase::factory()->confirmed()->create(['user_id' => $seller->id]);
        $cancelled = TokenPurchase::factory()->cancelled()->create(['user_id' => $seller->id]);

        $this->actingAs($admin);

        Livewire::test(AdminPendingTokenPurchasesWidget::class)
            ->loadTable()
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$confirmed, $cancelled]);
    });
});
