<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Category;
use Filament\Forms;
use Filament\Schemas\Schema;
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
                                modifyQueryUsing: fn(Builder $query) =>
                                $query->where('user_id', auth()->id())
                            )
                            ->default(fn() => auth()->user()?->lapak?->id)
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
                            ->visible(
                                fn($get) => ($get('category_id') && Category::find($get('category_id'))?->supportsCondition()) || false
                            )
                            ->required(),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktifkan Produk')
                            ->disabled(fn($record) => ! auth()->user()?->is_admin && $record && ! $record->is_active)
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Gambar Produk')
                    ->description('Gambar pertama akan digunakan sebagai gambar utama')
                    ->schema([
                        Forms\Components\FileUpload::make('uploaded_images')
                            ->label('Gambar')
                            ->image()
                            ->imageEditor()
                            ->multiple()
                            ->reorderable()
                            ->maxFiles(5)
                            ->directory('products')
                            ->required(),
                    ]),
            ])
            ->columns(1);
    }
}
