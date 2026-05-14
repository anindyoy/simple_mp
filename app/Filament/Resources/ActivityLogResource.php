<?php

namespace App\Filament\Resources;

use UnitEnum;
use BackedEnum;
use App\Models\User;
use Filament\Tables;
use App\Models\Report;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Category;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Spatie\Activitylog\Models\Activity;
use App\Filament\Resources\ActivityLogResource\Pages\ListActivityLogs;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?string $recordTitleAttribute = 'description';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->sortable()
                    ->label('Action'),
                Tables\Columns\TextColumn::make('log_name')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'user' => 'blue',
                        'product' => 'green',
                        'lapak' => 'indigo',
                        'category' => 'purple',
                        'product_image' => 'amber',
                        'product_moderation' => 'red',
                        'report' => 'orange',
                        'setting' => 'gray',
                        'token_purchase' => 'cyan',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('causer.name')
                    ->label('User')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Model')
                    ->formatStateUsing(fn(string $state): string => class_basename($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->options([
                        'user' => 'User',
                        'product' => 'Product',
                        'lapak' => 'Lapak Profile',
                        'category' => 'Category',
                        'product_image' => 'Product Image',
                        'product_moderation' => 'Product Moderation',
                        'report' => 'Report',
                        'setting' => 'Setting',
                        'token_purchase' => 'Token Purchase',
                    ]),
                Tables\Filters\SelectFilter::make('subject_type')
                    ->options([
                        \App\Models\User::class => 'User',
                        \App\Models\Product::class => 'Product',
                        \App\Models\LapakProfile::class => 'Lapak Profile',
                        \App\Models\Category::class => 'Category',
                        \App\Models\ProductModeration::class => 'Product Moderation',
                        \App\Models\Report::class => 'Report',
                        \App\Models\Setting::class => 'Setting',
                        \App\Models\TokenPurchase::class => 'Token Purchase',
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->is_admin === true;
    }

    public static function canView($record): bool
    {
        return auth()->user()?->is_admin === true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivityLogs::route('/'),
        ];
    }
}
