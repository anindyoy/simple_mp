<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\User;
use Filament\Tables\Table;
use Filament\Actions\Action;
use App\Policies\ProductPolicy;
use Filament\Actions\EditAction;
use App\Models\ProductModeration;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\Filter;
use Livewire\Component as Livewire;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Services\ProductModerationService;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        SpatieMediaLibraryImageColumn::make('products')
                            ->collection('products')
                            ->circular()
                            ->stacked()
                            ->limit(3)
                            ->overlap(4)
                            ->remainingTextBadge(true)
                            ->height(120)
                            ->width(120),

                        TextColumn::make('is_active')
                            ->label('Aktif')
                            ->badge()
                            ->formatStateUsing(fn($state) => $state ? 'Aktif' : 'Tidak Aktif')
                            ->color(fn($state) => $state ? 'success' : 'danger')
                            ->alignEnd()
                            ->grow(false),
                    ]),

                    TextColumn::make('title')
                        ->label('Produk')
                        ->searchable()
                        ->sortable()
                        ->weight(FontWeight::Bold)
                        ->wrap(),

                    TextColumn::make('category.category_name')
                        ->label('Kategori')
                        ->sortable()
                        ->badge()
                        ->color('gray'),

                    TextColumn::make('lapak.name')
                        ->label('Lapak')
                        ->searchable()
                        ->hidden(!auth()->user()->is_admin)
                        ->sortable(),

                    TextColumn::make('price')
                        ->label('Harga')
                        ->money('IDR', locale: 'id')
                        ->sortable()
                        ->weight(FontWeight::Bold)
                        ->color('success'),

                    TextColumn::make('pushed_at')
                        ->label('Diangkat')
                        ->sortable()
                        ->formatStateUsing(function ($state, $record) {
                            if ($record->created_at->diffInMinutes($record->pushed_at) <= 5) {
                                return '-';
                            }
                            return $record->pushed_at?->diffForHumans();
                        })
                        ->description(fn($record) => 'Dibuat: ' . $record->created_at->format('d M Y')),

                    TextColumn::make('latestReactivationRequest.status')
                        ->label('Aktivasi Ulang')
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
                ])
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->defaultSort('pushed_at', 'desc')
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
            ->recordUrl(null)
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
                    ->label('Waktu Diangkat')
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
                    ->label('Angkat')
                    ->icon('heroicon-o-arrow-up')
                    ->color('success')

                    ->disabled(fn() => ! ProductPolicy::canPush())
                    ->hidden(fn() => auth()->user()->is_admin)

                    ->tooltip(fn() => ProductPolicy::pushTooltip())

                    ->action(function ($record, Livewire $livewire) {
                        if (! ProductPolicy::canPush()) {
                            Notification::make()
                                ->title('Belum bisa angkat')
                                ->body(ProductPolicy::blockedPushMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        $userId = auth()->id();
                        $canPush = true;
                        $blockedMessage = null;

                        DB::transaction(function () use ($record, $userId, &$canPush, &$blockedMessage) {
                            $user = User::query()->lockForUpdate()->find($userId);

                            if (! $user || $user->is_admin || (int) $user->push_tokens < 1) {
                                $canPush = false;
                                $blockedMessage = 'Token tidak cukup untuk mengangkat produk.';

                                return;
                            }

                            if (ProductPolicy::remainingPushCooldownSeconds() > 0) {
                                $canPush = false;
                                $blockedMessage = ProductPolicy::blockedPushMessage();

                                return;
                            }

                            $record->update([
                                'pushed_at' => now(),
                            ]);

                            $user->decrement('push_tokens', 1);
                        });

                        if (! $canPush) {
                            Notification::make()
                                ->title('Belum bisa angkat')
                                ->body($blockedMessage ?? ProductPolicy::blockedPushMessage())
                                ->danger()
                                ->send();

                            return;
                        }

                        $remainingTokens = (int) auth()->user()->fresh()->push_tokens;

                        Notification::make()
                            ->title('Produk berhasil diangkat')
                            ->body('Token tersisa: ' . $remainingTokens)
                            ->success()
                            ->send();

                        $livewire->dispatch('push-countdown-refresh');

                        if (method_exists($livewire, 'resetTable')) {
                            $livewire->resetTable();
                        }
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
