<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Tables\Table;
use Filament\Actions\Action;
use App\Policies\ProductPolicy;
use Filament\Actions\EditAction;
use App\Models\ProductModeration;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Services\ProductModerationService;
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
                    ->description(fn($record) => $record->condition ? 'Kondisi: ' . ucfirst($record->condition) : null)
                    ->state(function ($record): array {
                        $lines = [$record->title];

                        $reason = $record->latestDeactivation?->reason;

                        if ((! $record->is_active) && $reason) {
                            $lines[] = 'Dinonaktifkan, sebab: ' . $reason;
                        }

                        return $lines;
                    })
                    ->listWithLineBreaks()
                    ->color(fn($state): ?string => str_starts_with((string) $state, 'Dinonaktifkan, sebab:') ? 'danger' : null)
                    ->wrap(),

                TextColumn::make('lapak.name')
                    ->label('Lapak')
                    ->searchable()
                    ->hidden(!auth()->user()->is_admin)
                    ->sortable()
                    ->description(fn($record) => $record->lapak?->user ? 'Pemilik: ' . $record->lapak->user->name : null),

                TextColumn::make('category.category_name')
                    ->label('Kategori')
                    ->sortable(),

                TextColumn::make('price')
                    ->label('Harga')
                    ->money('IDR', locale: 'id')
                    ->sortable(),

                ToggleColumn::make('is_active')
                    ->label('Aktif')
                    ->disabled(
                        fn($record) => auth()->user()->is_admin || $record->latestDeactivation?->reason !== 'Produk tidak sesuai ketentuan'
                    ),

                TextColumn::make('latestReactivationRequest.status')
                    ->label('Aktivasi Ulang')
                    ->state(fn($record) => $record->latestReactivationRequest?->status)
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color(fn(?string $state) => match ($state) {
                        ProductModeration::STATUS_PENDING => 'warning',
                        ProductModeration::STATUS_APPROVED => 'success',
                        ProductModeration::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(?string $state) => match ($state) {
                        ProductModeration::STATUS_PENDING => 'Menunggu Moderasi',
                        ProductModeration::STATUS_APPROVED => 'Disetujui',
                        ProductModeration::STATUS_REJECTED => 'Ditolak',
                        default => '-',
                    }),

                TextColumn::make('pushed_at')
                    ->label('Disundul')
                    ->since()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->date('d M Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->modifyQueryUsing(
                fn(Builder $query) => $query->with([
                    'primaryImage',
                    'lapak',
                    'lapak.user',
                    'category',
                    'latestDeactivation',
                    'latestReactivationRequest',
                    'pendingReactivationRequest',
                ])
                    ->when(
                        !auth()->user()->is_admin,
                        fn($q) => $q->where('lapak_id', auth()->user()->lapak->id)
                    )
            )
            ->defaultSort('pushed_at', 'desc')
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

                Filter::make('pending_reactivation')
                    ->label('Menunggu Moderasi Aktivasi')
                    ->visible(fn() => auth()->user()->is_admin)
                    ->query(fn(Builder $query) => $query->whereHas(
                        'moderations',
                        fn(Builder $moderationQuery) => $moderationQuery
                            ->where('type', ProductModeration::TYPE_REACTIVATION)
                            ->where('status', ProductModeration::STATUS_PENDING)
                    )),

                SelectFilter::make('latest_reactivation_status')
                    ->label('Status Aktivasi Terakhir')
                    ->visible(fn() => auth()->user()->is_admin)
                    ->options([
                        ProductModeration::STATUS_PENDING => 'Menunggu Moderasi',
                        ProductModeration::STATUS_APPROVED => 'Disetujui',
                        ProductModeration::STATUS_REJECTED => 'Ditolak',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $status = $data['value'] ?? null;

                        if (! $status) {
                            return $query;
                        }

                        return $query->whereHas(
                            'latestReactivationRequest',
                            fn(Builder $reactivationQuery) => $reactivationQuery
                                ->where('status', $status)
                        );
                    }),

                Filter::make('has_rejected_reactivation_history')
                    ->label('Pernah Ditolak Aktivasi')
                    ->visible(fn() => auth()->user()->is_admin)
                    ->query(fn(Builder $query) => $query->whereHas(
                        'moderations',
                        fn(Builder $moderationQuery) => $moderationQuery
                            ->where('type', ProductModeration::TYPE_REACTIVATION)
                            ->where('status', ProductModeration::STATUS_REJECTED)
                    )),

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

                    ->disabled(fn() => ! ProductPolicy::canPush())
                    ->hidden(fn() => auth()->user()->is_admin)

                    ->tooltip(fn() => ProductPolicy::pushTooltip())

                    ->action(function ($record) {
                        if (! ProductPolicy::canPush()) {
                            Notification::make()
                                ->title('Belum bisa push')
                                ->body('Kamu hanya bisa push produk setiap 6 jam.')
                                ->danger()
                                ->send();

                            return;
                        }

                        $record->update([
                            'pushed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Produk berhasil dipush')
                            ->success()
                            ->send();
                    }),

                EditAction::make(),

                Action::make('requestReactivation')
                    ->label('Ajukan Aktif Kembali')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn($record) => ! auth()->user()->is_admin && ! $record->is_active)
                    ->disabled(fn($record) => (bool) $record->pendingReactivationRequest)
                    ->tooltip(fn($record) => $record->pendingReactivationRequest
                        ? 'Pengajuan sebelumnya masih menunggu moderasi admin.'
                        : 'Ajukan moderasi untuk mengaktifkan kembali produk.')
                    ->schema([
                        TextInput::make('reason')
                            ->label('Alasan Pengajuan')
                            ->required()
                            ->maxLength(255)
                            ->default('Produk sudah diperbaiki dan siap ditinjau ulang.'),
                    ])
                    ->action(function ($record, array $data, ProductModerationService $service) {

                        try {
                            $service->requestReactivation(
                                $record,
                                auth()->user(),
                                $data['reason']
                            );

                            Notification::make()
                                ->title('Pengajuan aktivasi ulang dikirim')
                                ->success()
                                ->send();
                        } catch (\DomainException $e) {

                            Notification::make()
                                ->title($e->getMessage())
                                ->warning()
                                ->send();
                        }
                    }),

                Action::make('approveReactivation')
                    ->label('Setujui Aktivasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($record) => auth()->user()->is_admin && (bool) $record->pendingReactivationRequest)
                    ->action(function ($record, ProductModerationService $service) {

                        try {
                            $service->approveReactivation(
                                $record,
                                auth()->user()
                            );

                            Notification::make()
                                ->title('Produk diaktifkan kembali')
                                ->success()
                                ->send();
                        } catch (\DomainException $e) {

                            Notification::make()
                                ->title($e->getMessage())
                                ->warning()
                                ->send();
                        }
                    }),

                Action::make('rejectReactivation')
                    ->label('Tolak Aktivasi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn($record) => auth()->user()->is_admin && (bool) $record->pendingReactivationRequest)
                    ->schema([
                        TextInput::make('reason')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function ($record, array $data, ProductModerationService $service) {

                        try {
                            $service->rejectReactivation(
                                $record,
                                auth()->user(),
                                $data['reason']
                            );

                            Notification::make()
                                ->title('Pengajuan aktivasi ditolak')
                                ->success()
                                ->send();
                        } catch (\DomainException $e) {

                            Notification::make()
                                ->title($e->getMessage())
                                ->warning()
                                ->send();
                        }
                    }),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
