<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                TextInput::make('category_name')
                    ->label('Nama Kategori')
                    ->required()
                    ->maxLength(50)
                    ->unique(ignoreRecord: true),

                Toggle::make('supports_condition')
                    ->label('Mendukung Kondisi Produk')
                    ->helperText('Aktifkan jika produk dalam kategori ini memiliki kondisi (baru/bekas)'),
            ]);
    }
}
