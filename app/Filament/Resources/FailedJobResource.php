<?php

namespace App\Filament\Resources;

use App\Models\FailedJob;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use App\Filament\Resources\FailedJobResource\Pages\ListFailedJobs;

class FailedJobResource extends Resource
{
    protected static ?string $model = FailedJob::class;

    protected static ?string $modelLabel = 'Job Gagal';

    protected static ?string $pluralModelLabel = 'Job Gagal';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static UnitEnum|string|null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 2;

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
                    ->sortable()
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                TextColumn::make('connection')
                    ->label('Koneksi')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('queue')
                    ->label('Antrian')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('failed_at')
                    ->label('Gagal Pada')
                    ->dateTime('d M Y H:i:s')
                    ->sortable(),
                TextColumn::make('exception')
                    ->label('Exception')
                    ->limit(80)
                    ->searchable()
                    ->copyable()
                    ->tooltip(fn (FailedJob $record): string => $record->exception),
            ])
            ->defaultSort('failed_at', 'desc')
            ->striped()
            ->poll('30s')
            ->actions([
                DeleteAction::make()
                    ->label('Hapus'),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus Terpilih'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFailedJobs::route('/'),
        ];
    }
}