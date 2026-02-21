<?php

namespace App\Filament\Resources\Products\Tables;

use Carbon\Carbon;
use Filament\Tables\Table;
use Filament\Actions\Action;
use App\Models\ProductModeration;
use App\Policies\ProductPolicy;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\Filter;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\ImageColumn;
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
                    ->label('Aktif')
                    ->disabled(fn () => ! auth()->user()->is_admin),

                TextColumn::make('latestDeactivation.reason')
                    ->label('Sebab Nonaktif')
                    ->state(fn ($record) => $record->latestDeactivation?->reason)
                    ->placeholder('-')
                    ->wrap(),

                TextColumn::make('latestReactivationRequest.status')
                    ->label('Aktivasi Ulang')
                    ->state(fn ($record) => $record->latestReactivationRequest?->status)
                    ->badge()
                    ->color(fn (?string $state) => match ($state) {
                        ProductModeration::STATUS_PENDING => 'warning',
                        ProductModeration::STATUS_APPROVED => 'success',
                        ProductModeration::STATUS_REJECTED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (?string $state) => match ($state) {
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
                    ->visible(fn () => auth()->user()->is_admin)
                    ->query(fn (Builder $query) => $query->whereHas(
                        'moderations',
                        fn (Builder $moderationQuery) => $moderationQuery
                            ->where('type', ProductModeration::TYPE_REACTIVATION)
                            ->where('status', ProductModeration::STATUS_PENDING)
                    )),

                SelectFilter::make('latest_reactivation_status')
                    ->label('Status Aktivasi Terakhir')
                    ->visible(fn () => auth()->user()->is_admin)
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
                            fn (Builder $reactivationQuery) => $reactivationQuery
                                ->where('status', $status)
                        );
                    }),

                Filter::make('has_rejected_reactivation_history')
                    ->label('Pernah Ditolak Aktivasi')
                    ->visible(fn () => auth()->user()->is_admin)
                    ->query(fn (Builder $query) => $query->whereHas(
                        'moderations',
                        fn (Builder $moderationQuery) => $moderationQuery
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
                    ->visible(fn ($record) => ! auth()->user()->is_admin && ! $record->is_active)
                    ->disabled(fn ($record) => (bool) $record->pendingReactivationRequest)
                    ->tooltip(fn ($record) => $record->pendingReactivationRequest
                        ? 'Pengajuan sebelumnya masih menunggu moderasi admin.'
                        : 'Ajukan moderasi untuk mengaktifkan kembali produk.')
                    ->schema([
                        TextInput::make('reason')
                            ->label('Alasan Pengajuan')
                            ->required()
                            ->maxLength(255)
                            ->default('Produk sudah diperbaiki dan siap ditinjau ulang.'),
                    ])
                    ->action(function ($record, array $data) {
                        if ($record->pendingReactivationRequest) {
                            Notification::make()
                                ->title('Masih ada pengajuan aktif')
                                ->warning()
                                ->send();

                            return;
                        }

                        ProductModeration::create([
                            'product_id' => $record->id,
                            'type' => ProductModeration::TYPE_REACTIVATION,
                            'status' => ProductModeration::STATUS_PENDING,
                            'reason' => 'permohonan_aktivasi_ulang',
                            'description' => $data['reason'],
                            'requested_by_user_id' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Pengajuan aktivasi ulang dikirim')
                            ->success()
                            ->send();
                    }),

                Action::make('approveReactivation')
                    ->label('Setujui Aktivasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => auth()->user()->is_admin && (bool) $record->pendingReactivationRequest)
                    ->action(function ($record) {
                        $pendingRequest = $record->moderations()
                            ->where('type', ProductModeration::TYPE_REACTIVATION)
                            ->where('status', ProductModeration::STATUS_PENDING)
                            ->latest()
                            ->first();

                        if (! $pendingRequest) {
                            Notification::make()
                                ->title('Tidak ada pengajuan pending')
                                ->warning()
                                ->send();

                            return;
                        }

                        $pendingRequest->update([
                            'status' => ProductModeration::STATUS_APPROVED,
                            'reviewed_by_user_id' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        $record->update(['is_active' => true]);

                        Notification::make()
                            ->title('Produk diaktifkan kembali')
                            ->success()
                            ->send();
                    }),

                Action::make('rejectReactivation')
                    ->label('Tolak Aktivasi')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn ($record) => auth()->user()->is_admin && (bool) $record->pendingReactivationRequest)
                    ->schema([
                        TextInput::make('reason')
                            ->label('Alasan Penolakan')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->action(function ($record, array $data) {
                        $pendingRequest = $record->moderations()
                            ->where('type', ProductModeration::TYPE_REACTIVATION)
                            ->where('status', ProductModeration::STATUS_PENDING)
                            ->latest()
                            ->first();

                        if (! $pendingRequest) {
                            Notification::make()
                                ->title('Tidak ada pengajuan pending')
                                ->warning()
                                ->send();

                            return;
                        }

                        $pendingRequest->update([
                            'status' => ProductModeration::STATUS_REJECTED,
                            'description' => $data['reason'],
                            'reviewed_by_user_id' => auth()->id(),
                            'reviewed_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Pengajuan aktivasi ditolak')
                            ->success()
                            ->send();
                    }),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
