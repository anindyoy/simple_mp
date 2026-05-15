<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\TernaryFilter;
use STS\FilamentImpersonate\Actions\Impersonate;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                BadgeColumn::make('email_verified_at')
                    ->label('Email')
                    ->formatStateUsing(fn($state) => $state ? 'Terverifikasi' : 'Belum')
                    ->colors([
                        'success' => fn($state) => $state !== null,
                        'danger' => fn($state) => $state === null,
                    ]),

                BadgeColumn::make('products_count')
                    ->label('Jumlah Produk')
                    ->color(fn($state) => $state > 0 ? 'success' : 'gray')
                    ->sortable(),

                TextColumn::make('push_tokens')
                    ->label('Token Angkat Produk')
                    ->badge()
                    ->color(fn(int $state) => $state > 0 ? 'success' : 'danger')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('updated_at')
                    ->label('Update Terakhir')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filtersFormColumns(3)
            ->filters([
                TernaryFilter::make('email_verified_at')
                    ->label('Status Verifikasi Email')
                    ->queries(
                        true: fn(Builder $query) => $query->whereNotNull('email_verified_at'),
                        false: fn(Builder $query) => $query->whereNull('email_verified_at'),
                    ),

                DateRangeFilter::make('created_at')
                    ->label('Tanggal Daftar')
                    ->timezone('Asia/Jakarta')
                    ->displayFormat('DD/MM/YYYY')
                    ->format('d/m/Y')
                    ->defaultLast30Days()
                    ->ranges([
                        'Hari Ini' => [now()->startOfDay(), now()->endOfDay()],
                        'Kemarin' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
                        '7 Hari Terakhir' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
                        '30 Hari Terakhir' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
                        'Bulan Ini' => [now()->startOfMonth(), now()->endOfMonth()],
                        'Bulan Lalu' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
                    ])
                    ->useRangeLabels()
                    ->autoApply()
                    ->withIndicator(),

                DateRangeFilter::make('updated_at')
                    ->label('Tanggal Update')
                    ->timezone('Asia/Jakarta')
                    ->displayFormat('DD/MM/YYYY')
                    ->format('d/m/Y')
                    ->defaultLast30Days()
                    ->ranges([
                        'Hari Ini' => [now()->startOfDay(), now()->endOfDay()],
                        'Kemarin' => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
                        '7 Hari Terakhir' => [now()->subDays(6)->startOfDay(), now()->endOfDay()],
                        '30 Hari Terakhir' => [now()->subDays(29)->startOfDay(), now()->endOfDay()],
                        'Bulan Ini' => [now()->startOfMonth(), now()->endOfMonth()],
                        'Bulan Lalu' => [now()->subMonth()->startOfMonth(), now()->subMonth()->endOfMonth()],
                    ])
                    ->useRangeLabels()
                    ->autoApply()
                    ->withIndicator(),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query->withCount([
                    'lapak as products_count' => function ($q) {
                        $q->join('products', 'products.lapak_id', '=', 'lapak_profiles.id');
                    },
                ]);
            })
            ->recordActions([
                Impersonate::make()->hiddenLabel()->redirectTo('/admin'),

                Action::make('addPushToken')
                    ->label('Tambah Token')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->schema([
                        TextInput::make('amount')
                            ->label('Jumlah Token')
                            ->numeric()
                            ->minValue(1)
                            ->required(),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->increment('push_tokens', (int) $data['amount']);

                        Notification::make()
                            ->title('Token berhasil ditambahkan')
                            ->body('Saldo token sekarang: ' . (int) $record->fresh()->push_tokens)
                            ->success()
                            ->send();
                    }),

                Action::make('setPushToken')
                    ->label('Atur Token')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->schema([
                        TextInput::make('amount')
                            ->label('Saldo Token Baru')
                            ->numeric()
                            ->minValue(0)
                            ->required(),
                    ])
                    ->action(function ($record, array $data): void {
                        $record->update([
                            'push_tokens' => (int) $data['amount'],
                        ]);

                        Notification::make()
                            ->title('Saldo token angkat produk diperbarui')
                            ->body('Saldo token angkat produk sekarang: ' . (int) $record->fresh()->push_tokens)
                            ->success()
                            ->send();
                    }),

                EditAction::make()->hiddenLabel(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
