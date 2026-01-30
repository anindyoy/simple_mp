<?php

namespace App\Filament\Resources\Products\Tables;

use Carbon\Carbon;
use App\Models\Product;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                ImageColumn::make('primaryImage.image_url')
                    ->label('Foto')
                    ->disk('public')
                    ->height(48)
                    ->width(48)
                    ->square()
                    ->defaultImageUrl(url('/images/no-image.png')),

                TextColumn::make('title')
                    ->label('Produk')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('lapak.name')
                    ->label('Lapak')
                    ->searchable()
                    ->hidden(!auth()->user()->is_admin)
                    ->sortable(),

                TextColumn::make('category.category_name')
                    ->label('Kategori')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                BadgeColumn::make('condition')
                    ->label('Kondisi')
                    ->colors([
                        'success' => 'baru',
                        'warning' => 'seken',
                    ])
                    ->formatStateUsing(fn($state) => ucfirst($state)),

                ToggleColumn::make('is_active')
                    ->label('Aktif'),

                TextColumn::make('pushed_at')
                    ->label('Disundul')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(
                fn(Builder $query) => $query->with(['primaryImage', 'lapak', 'category'])
                    ->when(
                        !auth()->user()->is_admin,
                        fn($q) => $q->where('lapak_id', auth()->user()->lapak->id)
                    )
            )
            ->filtersFormColumns(3)
            ->filters([
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'category_name'),

                SelectFilter::make('lapak_id')
                    ->label('Lapak')
                    ->hidden(!auth()->user()->is_admin)
                    ->relationship('lapak', 'name'),

                SelectFilter::make('condition')
                    ->label('Kondisi')
                    ->options([
                        'baru' => 'Baru',
                        'seken' => 'Seken',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),

                Filter::make('price_range')
                    ->label('Rentang Harga')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('min_price')
                            ->numeric()
                            ->label('Harga Min'),
                        \Filament\Forms\Components\TextInput::make('max_price')
                            ->numeric()
                            ->label('Harga Max'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['min_price'],
                                fn($q) => $q->where('price', '>=', $data['min_price'])
                            )
                            ->when(
                                $data['max_price'],
                                fn($q) => $q->where('price', '<=', $data['max_price'])
                            );
                    }),

                Filter::make('pushed_at')
                    ->label('Tanggal Sundul')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Dari tanggal'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Sampai tanggal'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        return $query
                            ->when(
                                $data['from'],
                                fn($q) => $q->whereDate('pushed_at', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn($q) => $q->whereDate('pushed_at', '<=', $data['until'])
                            );
                    }),
            ])

            ->recordActions([
                Action::make('push')
                    ->label('Push')
                    ->icon('heroicon-o-arrow-up')
                    ->color('warning')

                    // Disable tombol jika user belum boleh push
                    ->disabled(function () {
                        $user = auth()->user();

                        if ($user->is_admin) {
                            return false;
                        }

                        $lastPush = Product::where('lapak_id', $user->lapak->id)
                            ->whereNotNull('pushed_at')
                            ->max('pushed_at');

                        if (!$lastPush) {
                            return false;
                        }

                        return Carbon::parse($lastPush)->addHours(6)->isFuture();
                    })

                    // Tooltip info
                    ->tooltip(function () {
                        $user = auth()->user();

                        $lastPush = Product::where('lapak_id', $user->lapak->id)
                            ->whereNotNull('pushed_at')
                            ->max('pushed_at');

                        if (!$lastPush) {
                            return 'Push produk ke atas';
                        }

                        $nextPush = Carbon::parse($lastPush)->addHours(6);

                        if ($nextPush->isFuture()) {
                            return 'Bisa push lagi pada ' . $nextPush->format('d M Y H:i');
                        }

                        return 'Push produk ke atas';
                    })

                    ->action(function ($record) {
                        $user = auth()->user();

                        if (!$user->is_admin) {
                            $lastPush = Product::where('lapak_id', $user->lapak->id)
                                ->whereNotNull('pushed_at')
                                ->max('pushed_at');

                            if (
                                $lastPush &&
                                Carbon::parse($lastPush)->addHours(6)->isFuture()
                            ) {
                                Notification::make()
                                    ->title('Belum bisa push')
                                    ->body('Kamu hanya bisa push produk setiap 6 jam.')
                                    ->danger()
                                    ->send();

                                return;
                            }
                        }

                        // update pushed_at produk yang dipilih
                        $record->update([
                            'pushed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Produk berhasil dipush')
                            ->body('Produk kamu berhasil disundul ke atas.')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
