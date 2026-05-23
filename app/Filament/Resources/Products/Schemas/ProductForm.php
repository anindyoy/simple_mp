<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms;
use App\Models\Category;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Produk')
                    ->schema([
                        // Forms\Components\Select::make('lapak_id')
                        //     ->label('Lapak / Toko')
                        //     ->relationship(
                        //         'lapak',
                        //         'name',
                        //         modifyQueryUsing: fn(Builder $query) =>
                        //         $query->where('user_id', auth()->id())
                        //     )
                        //     ->default(fn() => auth()->user()?->lapak?->id)
                        //     ->searchable()
                        //     ->preload()
                        //     ->required(),

                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'category_name')
                            ->required(),

                        Forms\Components\TextInput::make('title')
                            ->label('Judul Produk')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->rows(5)
                            ->required(),

                        Forms\Components\TextInput::make('price')
                            ->label('Harga')
                            ->numeric()
                            ->prefix('Rp')
                            ->required(),

                        Forms\Components\Select::make('condition')
                            ->label('Kondisi')
                            ->options([
                                'baru' => 'Baru',
                                'seken' => 'Seken',
                            ])
                            ->visible(
                                fn($get) => ($get('category_id') && Category::find($get('category_id'))?->supportsCondition()) || false
                            )
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktifkan Produk')
                            ->disabled(fn($record) => ! auth()->user()?->is_admin && $record && ! $record->is_active)
                            ->default(true),

                        Forms\Components\Toggle::make('can_be_delivered')
                            ->label('Bisa Diantar')
                            ->helperText('Centang jika produk ini bisa diantarkan ke pembeli')
                            ->default(fn() => auth()->user()?->lapak?->can_be_delivered ?? false),
                    ])
                    ->columns(2),

                Section::make('Gambar Produk')
                    ->description('Gambar pertama akan digunakan sebagai gambar utama')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('images')
                            ->label('Gambar')
                            ->collection('products')
                            ->multiple()
                            ->reorderable()
                            ->panelLayout('grid')
                            ->image()
                            ->imageEditor()
                            ->maxFiles(5)
                            ->required()
                    ]),
            ])
            ->columns(1);
    }
}
