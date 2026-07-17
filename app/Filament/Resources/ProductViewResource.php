<?php

namespace App\Filament\Resources;

use App\Models\ProductView;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use App\Filament\Resources\ProductViewResource\Pages\ListProductViews;

class ProductViewResource extends Resource
{
    protected static ?string $model = ProductView::class;

    protected static ?string $modelLabel = 'View Produk';

    protected static ?string $pluralModelLabel = 'View Produk';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEye;

    protected static UnitEnum|string|null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 1;

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
        return false;
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
                TextColumn::make('product.title')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('expires_at')
                    ->label('Kadaluwarsa')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Dilihat Pada')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
                IconColumn::make('is_expired')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-s-clock')
                    ->falseIcon('heroicon-s-check-circle')
                    ->trueColor('danger')
                    ->falseColor('success')
                    ->getStateUsing(fn (ProductView $record): bool => $record->expires_at <= now()),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->poll('30s');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('product:id,title');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProductViews::route('/'),
        ];
    }
}