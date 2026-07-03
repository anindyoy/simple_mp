<?php

use App\Filament\Widgets\SellerStatsOverviewWidget;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

describe('canView', function () {
    it('is visible for a seller with a lapak', function () {
        $seller = makeUser();
        $this->actingAs($seller);

        expect(SellerStatsOverviewWidget::canView())->toBeTrue();
    });

    it('is hidden for admin', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        expect(SellerStatsOverviewWidget::canView())->toBeFalse();
    });

    it('is hidden for guests', function () {
        expect(SellerStatsOverviewWidget::canView())->toBeFalse();
    });
});

describe('getStats', function () {
    it('splits own products into live-now and queued, ignoring other lapaks', function () {
        $seller = makeUser();
        $otherSeller = makeUser();

        // first product publishes immediately, later ones queue behind the schedule delay
        makeProduct($seller->lapak);
        makeProduct($seller->lapak);
        makeProduct($seller->lapak);
        makeProduct($otherSeller->lapak);

        $this->actingAs($seller);

        $stats = getWidgetStats(new SellerStatsOverviewWidget());

        expect(findStat($stats, 'Produk Tayang Sekarang')->getValue())->toBe(1)
            ->and(findStat($stats, 'Produk Menunggu Jadwal')->getValue())->toBe(2)
            ->and(findStat($stats, 'Produk Menunggu Jadwal')->getDescription())->toContain('Tayang berikutnya');
    });

    it('shows no queue description when nothing is scheduled', function () {
        $seller = makeUser();

        $this->actingAs($seller);

        $stats = getWidgetStats(new SellerStatsOverviewWidget());

        expect(findStat($stats, 'Produk Menunggu Jadwal')->getValue())->toBe(0)
            ->and(findStat($stats, 'Produk Menunggu Jadwal')->getDescription())->toBe('Tidak ada antrean');
    });
});
