<?php

namespace App\Filament\Resources\CategoryRequests;

use UnitEnum;
use BackedEnum;
use App\Models\CategoryRequest;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\CategoryRequests\Pages\ManageCategoryRequests;
use App\Filament\Resources\CategoryRequests\Tables\CategoryRequestsTable;

class CategoryRequestResource extends Resource
{
    protected static ?string $model = CategoryRequest::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxArrowDown;
    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan';
    protected static ?string $navigationLabel = 'Ajuan Kategori';
    protected static ?string $modelLabel = 'Ajuan Kategori';
    protected static ?string $pluralModelLabel = 'Ajuan Kategori';

    public static function canViewAny(): bool
    {
        return auth()->user()?->is_admin;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::where('status', 'pending')->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return CategoryRequestsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCategoryRequests::route('/'),
        ];
    }
}
