<?php

namespace App\Filament\Resources\Reports\Tables;

use Carbon\Carbon;
use App\Models\Product;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Illuminate\Support\HtmlString;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Reports\ReportResource;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reportable_type')
                    ->label('Tipe')
                    ->formatStateUsing(
                        fn($state) =>
                        str_contains($state, 'Product') ? 'Produk' : 'Lapak'
                    ),

                TextColumn::make('target')
                    ->label('Target')
                    ->state(function ($record) {

                        if ($record->reportable_type === \App\Models\Product::class) {
                            return $record->product_title;
                        }

                        return $record->lapak_name;
                    })
                    ->description(function ($record) {

                        $isActive = $record->reportable_type === \App\Models\Product::class
                            ? $record->product_status
                            : $record->lapak_status;

                        if (is_null($isActive)) {
                            return null;
                        }

                        $label = $isActive ? 'Aktif' : 'Nonaktif';

                        $style = $isActive
                            ? "background:#dcfce7;color:#166534;"
                            : "background:#fee2e2;color:#991b1b;";

                        return new HtmlString(
                            "<span style='
                                display:inline-block;
                                padding:2px 8px;
                                font-size:12px;
                                font-weight:500;
                                border-radius:6px;
                                {$style}
                            '>{$label}</span>"
                        );
                    }),

                TextColumn::make('pemilik')
                    ->label('Pemilik')
                    ->state(function ($record) {

                        if ($record->reportable_type === \App\Models\Product::class) {
                            return $record->product_owner_name;
                        }

                        return $record->direct_owner_name;
                    })
                    ->weight(FontWeight::SemiBold)

                    ->description(function ($record) {

                        // ❌ Jika target adalah Lapak, jangan tampilkan nama toko
                        if ($record->reportable_type === \App\Models\LapakProfile::class) {
                            return null;
                        }

                        // ✔ Jika target Produk, tampilkan nama toko
                        $namaToko = $record->product_lapak_name;

                        if (! $namaToko) {
                            return null;
                        }

                        return new HtmlString(
                            "<span style='font-size:12px;color:#6b7280;'>
                                Toko: {$namaToko}
                            </span>"
                        );
                    }),

                TextColumn::make('total_reports')
                    ->label('Total Report')
                    ->badge()
                    ->color('danger'),

                TextColumn::make('last_reported_at')
                    ->label('Terakhir Dilaporkan')
                    ->formatStateUsing(
                        fn($state) =>
                        $state
                            ? Carbon::parse($state)->diffForHumans()
                            : '-'
                    )
                    ->color('gray'),
            ])
            ->filters([

                // 1️⃣ Filter Tipe (Produk / Lapak)
                SelectFilter::make('reportable_type')
                    ->label('Tipe')
                    ->options([
                        \App\Models\Product::class => 'Produk',
                        \App\Models\LapakProfile::class => 'Lapak',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['value'] ?? null) {
                            $query->where('reportable_type', $data['value']);
                        }
                    }),

                // 2️⃣ Filter Minimum Total Report
                Filter::make('minimum_reports')
                    ->label('Minimal Total Report')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('min')
                            ->numeric()
                            ->label('Minimal')
                    ])
                    ->query(function (Builder $query, array $data) {
                        if ($data['min'] ?? null) {
                            $query->havingRaw('COUNT(*) >= ?', [$data['min']]);
                        }
                    }),

                // 3️⃣ Filter Berdasarkan Waktu Terakhir Dilaporkan
                Filter::make('last_reported')
                    ->label('Terakhir Dilaporkan')
                    ->form([
                        \Filament\Forms\Components\Select::make('range')
                            ->label('Rentang Waktu')
                            ->options([
                                'today' => 'Hari Ini',
                                '7_days' => '7 Hari Terakhir',
                                '30_days' => '30 Hari Terakhir',
                            ])
                    ])
                    ->query(function (Builder $query, array $data) {

                        if (! isset($data['range'])) {
                            return;
                        }

                        match ($data['range']) {
                            'today' => $query->havingRaw('MAX(created_at) >= ?', [Carbon::today()]),
                            '7_days' => $query->havingRaw('MAX(created_at) >= ?', [Carbon::now()->subDays(7)]),
                            '30_days' => $query->havingRaw('MAX(created_at) >= ?', [Carbon::now()->subDays(30)]),
                        };
                    }),

            ])
            ->actions([
                Action::make('detail')
                    ->label('Lihat Detail')
                    ->url(
                        fn($record) =>
                        ReportResource::getUrl('details', [
                            'type' => base64_encode($record->reportable_type),
                            'id' => $record->reportable_id,
                        ])
                    )
                    ->icon('heroicon-o-eye'),
            ])
            ->recordUrl(null);
    }
}
