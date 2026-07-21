<?php

namespace App\Filament\Resources;

use UnitEnum;
use BackedEnum;
use App\Models\JobBatch;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Carbon;
use App\Filament\Resources\JobBatchResource\Pages\ListJobBatches;

class JobBatchResource extends Resource
{
    protected static ?string $model = JobBatch::class;

    protected static ?string $modelLabel = 'Batch Job';

    protected static ?string $pluralModelLabel = 'Batch Job';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static UnitEnum|string|null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 4;

    public static function canViewAny(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_jobs')
                    ->label('Total Job')
                    ->sortable(),
                TextColumn::make('pending_jobs')
                    ->label('Sisa')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'warning' : 'success'),
                TextColumn::make('failed_jobs')
                    ->label('Gagal')
                    ->sortable()
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'success'),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->formatStateUsing(fn (int $state): string => Carbon::createFromTimestamp($state, config('app.timezone'))->format('d M Y H:i:s'))
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label('Selesai Pada')
                    ->formatStateUsing(fn (?int $state): string => $state ? Carbon::createFromTimestamp($state, config('app.timezone'))->format('d M Y H:i:s') : '-')
                    ->sortable(),
                TextColumn::make('cancelled_at')
                    ->label('Dibatalkan Pada')
                    ->formatStateUsing(fn (?int $state): string => $state ? Carbon::createFromTimestamp($state, config('app.timezone'))->format('d M Y H:i:s') : '-')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('30s')
            ->actions([
                DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->label('Hapus Terpilih'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJobBatches::route('/'),
        ];
    }
}
