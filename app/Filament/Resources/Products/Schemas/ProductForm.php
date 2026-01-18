<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Produk')
                    ->schema([
                        Forms\Components\Select::make('lapak_id')
                            ->label('Lapak / Toko')
                            ->relationship(
                                'lapak',
                                'name',
                                modifyQueryUsing: fn (Builder $query) =>
                                    $query->where('user_id', auth()->id())
                            )
                            ->default(fn () => auth()->user()?->lapak?->id)
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'category_name')
                            ->searchable()
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
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktifkan Produk')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Gambar Produk')
                    ->description('Pilih satu gambar sebagai gambar utama')
                    ->schema([
                        Repeater::make('images')
                            ->relationship()
                            ->schema([
                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Gambar')
                                    ->image()
                                    ->imageEditor()
                                    ->directory('products')
                                    ->required(),

                                Forms\Components\Toggle::make('is_primary')
                                    ->label('Gambar Utama')
                                    ->helperText('Hanya satu gambar utama'),
                            ])
                            ->minItems(1)
                            ->maxItems(5)
                            ->columns(2)
                            ->defaultItems(1),
                    ]),
            ])
            ->columns(1);
    }
}
