<?php

namespace App\Filament\Widgets;

use App\Models\Report;
use App\Models\LapakProfile;
use App\Models\TokenPurchase;
use App\Models\ProductModeration;
use App\Services\VisitorStatsService;
use App\Services\ProductScheduleService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Filament\Resources\Reports\ReportResource;
use App\Filament\Resources\TokenPurchases\TokenPurchaseResource;

class AdminStatsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    public static function canView(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Lapak Aktif', LapakProfile::where('is_active', true)->count())
                ->icon('heroicon-o-building-storefront')
                ->color('success'),

            Stat::make('Produk Tayang Sekarang', ProductScheduleService::getEligibleProductIds()->count())
                ->icon('heroicon-o-eye')
                ->color('info'),

            Stat::make('Token Purchase Pending', TokenPurchase::where('status', 'pending')->count())
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->url(TokenPurchaseResource::getUrl('index')),

            Stat::make('Laporan Pending', Report::where('status', 'pending')->count())
                ->icon('heroicon-o-flag')
                ->color('danger')
                ->url(ReportResource::getUrl('index')),

            Stat::make('Moderasi Produk Pending', ProductModeration::where('status', 'pending')->count())
                ->icon('heroicon-o-shield-exclamation')
                ->color('warning'),

            Stat::make('Pengunjung Unik 24 Jam Terakhir', VisitorStatsService::getCached())
                ->description('Berdasarkan IP unik di ProductView, dihitung ulang tiap 6 jam')
                ->icon('heroicon-o-users')
                ->color('primary'),
        ];
    }
}
