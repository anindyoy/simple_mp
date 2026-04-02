<?php

namespace App\Filament\Resources\TutorialPages\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

class TutorialPageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('url')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->placeholder('/admin/products'),

                TextInput::make('title')
                    ->placeholder('Judul tutorial'),

                Repeater::make('images')
                    ->relationship()
                    ->label('Tutorial Steps')
                    ->schema([
                        FileUpload::make('image')
                            ->image()
                            ->directory('tutorials')
                            ->required(),

                        Textarea::make('caption')
                            ->rows(2)
                            ->placeholder('Penjelasan step'),

                        TextInput::make('sort_order')
                            ->numeric()
                            ->default(0),
                    ])
                    ->orderable('sort_order')
                    ->collapsible()
                    ->cloneable()
                    ->defaultItems(1),
            ]);
    }
}
