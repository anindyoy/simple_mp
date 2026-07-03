<?php

namespace App\Filament\Widgets;

use App\Models\TokenPurchase;
use Filament\Widgets\ChartWidget;

class TokenPurchaseTrendChartWidget extends ChartWidget
{
    protected static ?int $sort = 4;

    protected ?string $heading = 'Tren Token Terjual (6 Bulan Terakhir)';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $months = collect(range(5, 0))->map(fn(int $i) => now()->subMonths($i)->startOfMonth());

        $confirmedByMonth = TokenPurchase::query()
            ->where('status', 'confirmed')
            ->where('confirmed_at', '>=', $months->first())
            ->get()
            ->groupBy(fn(TokenPurchase $purchase) => $purchase->confirmed_at->format('Y-m'));

        return [
            'datasets' => [
                [
                    'label' => 'Token Terjual',
                    'data' => $months
                        ->map(fn($month) => $confirmedByMonth->get($month->format('Y-m'), collect())->sum('quantity'))
                        ->all(),
                    'backgroundColor' => '#3F9AAE',
                ],
            ],
            'labels' => $months->map(fn($month) => $month->translatedFormat('M Y'))->all(),
        ];
    }
}
