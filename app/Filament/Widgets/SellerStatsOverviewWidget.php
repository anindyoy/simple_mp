<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Services\ProductScheduleService;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\HtmlString;

class SellerStatsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 2;

    protected int|array|null $columns = 2;

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user !== null && ! $user->is_admin && $user->lapak !== null;
    }

    protected function getStats(): array
    {
        $lapak = auth()->user()->lapak;

        $activeProductIds = $lapak->products()->where('is_active', true)->pluck('id');
        $eligibleIds = ProductScheduleService::getEligibleProductIds();
        $schedule = ProductScheduleService::getOrRebuild($lapak->id);

        $liveNow = $activeProductIds->intersect($eligibleIds)->count();

        $queued = collect($schedule)
            ->filter(fn($publishAt, $productId) => $activeProductIds->contains($productId)
                && ! $eligibleIds->contains($productId));

        $nextPublishAt = $queued
            ->map(fn($publishAt) => Carbon::parse($publishAt))
            ->sort()
            ->first();

        return [
            Stat::make('Jumlah Produk Tayang Sekarang', $liveNow)
                ->icon('heroicon-o-eye')
                ->color('success')
                ->url(ProductResource::getUrl('index')),

            Stat::make(
                new HtmlString(view('filament.widgets.stat-info-label', [
                    'label' => 'Jumlah Produk Menunggu Antrean',
                    'heading' => 'Apa itu Produk Menunggu Antrean?',
                    'description' => 'Produk yang sudah dibuat tapi belum tayang di halaman publik. '
                        . 'Agar beranda tidak dibanjiri banyak produk sekaligus dari satu lapak, sistem '
                        . 'menayangkan produk baru secara bertahap dengan jeda beberapa jam dari produk sebelumnya.',
                ])->render()),
                $queued->count()
            )
                ->description($nextPublishAt
                    ? 'Tayang berikutnya: ' . $nextPublishAt->translatedFormat('d M, H:i')
                    : 'Tidak ada antrean')
                ->icon('heroicon-o-clock')
                ->color('warning')
                ->url(ProductResource::getUrl('index')),
        ];
    }
}
