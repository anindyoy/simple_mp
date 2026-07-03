<?php

use App\Filament\Widgets\AdminStatsOverviewWidget;
use App\Models\LapakProfile;
use App\Models\ProductModeration;
use App\Models\Report;
use App\Models\TokenPurchase;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

describe('canView', function () {
    it('is visible for admin', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        expect(AdminStatsOverviewWidget::canView())->toBeTrue();
    });

    it('is hidden for non-admin', function () {
        $seller = makeUser();
        $this->actingAs($seller);

        expect(AdminStatsOverviewWidget::canView())->toBeFalse();
    });

    it('is hidden for guests', function () {
        expect(AdminStatsOverviewWidget::canView())->toBeFalse();
    });
});

describe('getStats', function () {
    it('counts only active lapaks', function () {
        $admin = makeUser(isAdmin: true);
        $inactiveSeller = makeUser();
        $inactiveSeller->lapak->update(['is_active' => false]);

        $this->actingAs($admin);

        $stats = getWidgetStats(new AdminStatsOverviewWidget());

        expect(findStat($stats, 'Lapak Aktif')->getValue())
            ->toBe(LapakProfile::where('is_active', true)->count());
    });

    it('counts pending token purchases and links to the resource', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();

        TokenPurchase::factory()->pending()->create(['user_id' => $seller->id]);
        TokenPurchase::factory()->confirmed()->create(['user_id' => $seller->id]);

        $this->actingAs($admin);

        $stat = findStat(getWidgetStats(new AdminStatsOverviewWidget()), 'Token Purchase Pending');

        expect($stat->getValue())->toBe(1)
            ->and($stat->getUrl())->not->toBeNull();
    });

    it('counts pending reports and links to the resource', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();

        Report::factory()->forLapak($seller->lapak)->create(['status' => 'pending']);
        Report::factory()->forLapak($seller->lapak)->create(['status' => 'reviewed']);

        $this->actingAs($admin);

        $stat = findStat(getWidgetStats(new AdminStatsOverviewWidget()), 'Laporan Pending');

        expect($stat->getValue())->toBe(1)
            ->and($stat->getUrl())->not->toBeNull();
    });

    it('counts pending product moderations', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        $product = makeProduct($seller->lapak);

        ProductModeration::factory()->pending()->create(['product_id' => $product->id]);
        ProductModeration::factory()->approved()->create(['product_id' => $product->id]);

        $this->actingAs($admin);

        expect(findStat(getWidgetStats(new AdminStatsOverviewWidget()), 'Moderasi Produk Pending')->getValue())
            ->toBe(1);
    });

    it('counts products currently eligible to be shown publicly', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();
        makeProduct($seller->lapak);

        $this->actingAs($admin);

        expect(findStat(getWidgetStats(new AdminStatsOverviewWidget()), 'Produk Tayang Sekarang')->getValue())
            ->toBeGreaterThanOrEqual(1);
    });
});
