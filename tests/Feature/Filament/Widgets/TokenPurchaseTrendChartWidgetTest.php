<?php

use App\Filament\Widgets\TokenPurchaseTrendChartWidget;
use App\Models\TokenPurchase;

describe('canView', function () {
    it('is visible for admin only', function () {
        $admin = makeUser(isAdmin: true);
        $this->actingAs($admin);
        expect(TokenPurchaseTrendChartWidget::canView())->toBeTrue();

        $seller = makeUser();
        $this->actingAs($seller);
        expect(TokenPurchaseTrendChartWidget::canView())->toBeFalse();
    });
});

describe('getData', function () {
    it('aggregates confirmed token quantity into the current month bucket', function () {
        $admin = makeUser(isAdmin: true);
        $seller = makeUser();

        TokenPurchase::factory()->create([
            'user_id' => $seller->id,
            'quantity' => 7,
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        TokenPurchase::factory()->create([
            'user_id' => $seller->id,
            'quantity' => 3,
            'status' => 'pending',
            'confirmed_at' => null,
        ]);

        $this->actingAs($admin);

        $data = getWidgetChartData(new TokenPurchaseTrendChartWidget());

        expect($data['labels'])->toHaveCount(6)
            ->and(last($data['datasets'][0]['data']))->toBe(7);
    });
});
