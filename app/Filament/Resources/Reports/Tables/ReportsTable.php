<?php

namespace App\Filament\Resources\Reports\Tables;

use App\Filament\Resources\Reports\ReportResource;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;

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

                TextColumn::make('reportable_id')
                    ->label('Target')
                    ->formatStateUsing(function ($record) {
                        $model = $record->reportable_type;
                        $target = $model::find($record->reportable_id);

                        return $target?->title ?? $target?->name ?? '-';
                    }),

                TextColumn::make('total_reports')
                    ->label('Total Report')
                    ->badge()
                    ->color('danger'),

                TextColumn::make('total_pending')
                    ->label('Pending')
                    ->badge()
                    ->color('warning'),

                TextColumn::make('total_reviewed')
                    ->label('Reviewed')
                    ->badge()
                    ->color('success'),
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
