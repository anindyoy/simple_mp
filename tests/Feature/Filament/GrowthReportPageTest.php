<?php

use App\Filament\Pages\GrowthReportPage;
use App\Filament\Widgets\LapakGrowthChartWidget;
use App\Filament\Widgets\ProductGrowthChartWidget;
use App\Models\LapakProfile;
use App\Models\User;

describe('access control', function () {
    it('allows admin to access the page', function () {
        $this->actingAs(makeUser(isAdmin: true));

        expect(GrowthReportPage::canAccess())->toBeTrue();
        expect(GrowthReportPage::shouldRegisterNavigation())->toBeTrue();
    });

    it('blocks non admin from accessing the page', function () {
        $this->actingAs(makeUser());

        expect(GrowthReportPage::canAccess())->toBeFalse();
        expect(GrowthReportPage::shouldRegisterNavigation())->toBeFalse();
    });

    it('renders successfully for admin over http', function () {
        $this->actingAs(makeUser(isAdmin: true));

        $this->get('/admin/laporan/pertumbuhan')->assertSuccessful();
    });

    it('groups content into exactly a chart section and a table section', function () {
        $this->actingAs(makeUser(isAdmin: true));

        $response = $this->get('/admin/laporan/pertumbuhan');

        $response->assertSuccessful();
        $response->assertSeeText('Grafik Pertumbuhan');
        $response->assertSeeText('Tabel Pertumbuhan');
        $response->assertSeeText('Lapak Aktif');
        $response->assertSeeText('Produk Aktif');
    });

    it('renders widget data based on the requested filter range', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        LapakProfile::factory()->create(['is_active' => true, 'created_at' => now()]);
        makeProduct($admin->lapak, ['is_active' => true, 'created_at' => now()]);

        // Rentang yang mencakup data hari ini.
        $this->get('/admin/laporan/pertumbuhan?' . http_build_query([
            'filters' => [
                'period' => 'daily',
                'start_date' => now()->toDateString(),
                'end_date' => now()->toDateString(),
            ],
        ]))->assertDontSeeText('Tidak ada data untuk rentang periode ini.');

        // Rentang di masa lalu yang tidak mencakup data apa pun.
        $this->get('/admin/laporan/pertumbuhan?' . http_build_query([
            'filters' => [
                'period' => 'daily',
                'start_date' => now()->subDays(2)->toDateString(),
                'end_date' => now()->subDays(1)->toDateString(),
            ],
        ]))->assertSeeText('Tidak ada data untuk rentang periode ini.');
    });
});

describe('LapakGrowthChartWidget', function () {
    it('is visible for admin only', function () {
        $this->actingAs(makeUser(isAdmin: true));
        expect(LapakGrowthChartWidget::canView())->toBeTrue();

        $this->actingAs(makeUser());
        expect(LapakGrowthChartWidget::canView())->toBeFalse();
    });

    it('reports new and cumulative active lapak counts per monthly bucket', function () {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        LapakProfile::factory()->create(['is_active' => true, 'created_at' => now()->startOfMonth()]);
        LapakProfile::factory()->create(['is_active' => true, 'created_at' => now()->startOfMonth()]);
        LapakProfile::factory()->create(['is_active' => false, 'created_at' => now()->startOfMonth()]);

        $widget = new LapakGrowthChartWidget();
        $widget->pageFilters = [
            'period' => 'monthly',
            'start_date' => now()->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ];

        $data = getWidgetChartData($widget);

        expect(last($data['datasets'][0]['data']))->toBe(2)
            ->and(last($data['datasets'][1]['data']))->toBe(3);
    });

    it('trims leading empty months before the first record even when the filter range starts earlier', function () {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        LapakProfile::factory()->create(['is_active' => true, 'created_at' => now()->startOfMonth()]);

        $widget = new LapakGrowthChartWidget();
        $widget->pageFilters = [
            'period' => 'monthly',
            'start_date' => now()->subMonths(12)->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ];

        $data = getWidgetChartData($widget);

        expect($data['labels'])->toHaveCount(1)
            ->and(last($data['datasets'][0]['data']))->toBe(1);
    });

    it('returns empty series when there is no data at all', function () {
        $this->actingAs(User::factory()->create(['is_admin' => true]));

        $widget = new LapakGrowthChartWidget();
        $widget->pageFilters = [
            'period' => 'monthly',
            'start_date' => now()->subMonths(12)->startOfMonth()->toDateString(),
            'end_date' => now()->toDateString(),
        ];

        $data = getWidgetChartData($widget);

        expect($data['labels'])->toBeEmpty()
            ->and($data['datasets'][0]['data'])->toBeEmpty();
    });
});

describe('ProductGrowthChartWidget', function () {
    it('reports new and cumulative active product counts per daily bucket', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);

        $lapak = $admin->lapak;
        makeProduct($lapak, ['is_active' => true, 'created_at' => now()]);
        makeProduct($lapak, ['is_active' => false, 'created_at' => now()]);

        $widget = new ProductGrowthChartWidget();
        $widget->pageFilters = [
            'period' => 'daily',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
        ];

        $data = getWidgetChartData($widget);

        expect(last($data['datasets'][0]['data']))->toBe(1)
            ->and(last($data['datasets'][1]['data']))->toBe(2);
    });
});
