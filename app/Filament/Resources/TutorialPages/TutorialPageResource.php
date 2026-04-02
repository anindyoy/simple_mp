<?php

namespace App\Filament\Resources\TutorialPages;

use App\Filament\Resources\TutorialPages\Pages\CreateTutorialPage;
use App\Filament\Resources\TutorialPages\Pages\EditTutorialPage;
use App\Filament\Resources\TutorialPages\Pages\ListTutorialPages;
use App\Filament\Resources\TutorialPages\Schemas\TutorialPageForm;
use App\Filament\Resources\TutorialPages\Tables\TutorialPagesTable;
use App\Models\TutorialPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class TutorialPageResource extends Resource
{
    protected static ?string $model = TutorialPage::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return TutorialPageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TutorialPagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTutorialPages::route('/'),
            'create' => CreateTutorialPage::route('/create'),
            'edit' => EditTutorialPage::route('/{record}/edit'),
        ];
    }
}
