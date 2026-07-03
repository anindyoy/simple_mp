<?php

use App\Filament\Widgets\AdminPendingReportsWidget;
use App\Models\Report;
use Livewire\Livewire;

describe('canView', function () {
    it('is visible for admin only', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);
        expect(AdminPendingReportsWidget::canView())->toBeTrue();

        $seller = makeUser();
        $this->actingAs($seller);
        expect(AdminPendingReportsWidget::canView())->toBeFalse();
    });
});

describe('table', function () {
    it('lists only pending reports', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();

        $pending = Report::factory()->forLapak($seller->lapak)->create(['status' => 'pending']);
        $reviewed = Report::factory()->forLapak($seller->lapak)->create(['status' => 'reviewed']);
        $rejected = Report::factory()->forLapak($seller->lapak)->create(['status' => 'rejected']);

        $this->actingAs($admin);

        Livewire::test(AdminPendingReportsWidget::class)
            ->loadTable()
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$reviewed, $rejected]);
    });
});
