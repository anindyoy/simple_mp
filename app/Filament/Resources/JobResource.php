<?php

namespace App\Filament\Resources;

use UnitEnum;
use BackedEnum;
use App\Models\Job;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Carbon;
use App\Filament\Resources\JobResource\Pages\ListJobs;

class JobResource extends Resource
{
    protected static ?string $model = Job::class;

    protected static ?string $modelLabel = 'Job Antrian';

    protected static ?string $pluralModelLabel = 'Job Antrian';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static UnitEnum|string|null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 3;

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
                    ->toggleable(),
                TextColumn::make('queue')
                    ->label('Antrian')
                    ->searchable()
                    ->sortable()
                    ->badge(),
                TextColumn::make('display_name')
                    ->label('Job')
                    ->limit(60)
                    ->tooltip(fn (Job $record): ?string => $record->display_name),
                TextColumn::make('attempts')
                    ->label('Percobaan')
                    ->sortable(),
                TextColumn::make('reserved_at')
                    ->label('Sedang Diproses')
                    ->formatStateUsing(fn (?int $state): string => $state ? Carbon::createFromTimestamp($state, config('app.timezone'))->format('d M Y H:i:s') : '-')
                    ->badge()
                    ->color(fn (?int $state): string => $state ? 'warning' : 'gray'),
                TextColumn::make('available_at')
                    ->label('Tersedia Pada')
                    ->formatStateUsing(fn (int $state): string => Carbon::createFromTimestamp($state, config('app.timezone'))->format('d M Y H:i:s'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->formatStateUsing(fn (int $state): string => Carbon::createFromTimestamp($state, config('app.timezone'))->format('d M Y H:i:s'))
                    ->sortable(),
            ])
            ->defaultSort('id', 'desc')
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
            'index' => ListJobs::route('/'),
        ];
    }
}
