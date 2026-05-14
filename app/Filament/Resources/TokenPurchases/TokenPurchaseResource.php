<?php

namespace App\Filament\Resources\TokenPurchases;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Models\TokenPurchase;
use Filament\Resources\Resource;
use Livewire\Component as Livewire;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\TokenPurchases\Pages\ViewTokenPurchase;
use App\Filament\Resources\TokenPurchases\Pages\ListTokenPurchases;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class TokenPurchaseResource extends Resource
{
    protected static ?string $model = TokenPurchase::class;

    protected static ?string $modelLabel = 'Riwayat Pembelian Token';

    protected static UnitEnum|string|null $navigationGroup = 'Akun';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 50;

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Non-admin users only see their own purchases
        if (!$user?->is_admin) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        // Only admin can edit
        return (bool) auth()->user()?->is_admin;
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        // Admin can view all, users can only view their own
        return (bool) $user?->is_admin || $record->user_id === $user?->id;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        $isAdmin = (bool) auth()->user()?->is_admin;

        $fields = [];

        // Show user_id only for admin
        if ($isAdmin) {
            $fields[] = Select::make('user_id')
                ->label('User')
                ->relationship('user', 'name')
                ->searchable()
                ->disabled();
        }

        $fields = array_merge($fields, [
            TextInput::make('quantity')
                ->label('Jumlah Token')
                ->numeric()
                ->disabled(),

            TextInput::make('total_price')
                ->label('Total Harga')
                ->numeric()
                ->disabled()
                ->prefix('Rp '),

            TextInput::make('bank_account')
                ->label('Rekening Bank')
                ->disabled(),

            FileUpload::make('proof_of_payment')
                ->label('Bukti Transfer')
                ->image()
                ->disk('public')
                ->visibility('public')
                ->directory('token-purchases/proof')
                ->openable()
                ->downloadable()
                ->disabled(),
        ]);

        // Show status and notes only for admin
        if ($isAdmin) {
            $fields = array_merge($fields, [
                Select::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Menunggu Konfirmasi',
                        'confirmed' => 'Dikonfirmasi',
                        'cancelled' => 'Dibatalkan',
                    ])
                    ->required(),

                Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(3),
            ]);
        } else {
            // Users can only see status as read-only
            $fields[] = Select::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Menunggu Konfirmasi',
                    'confirmed' => 'Dikonfirmasi',
                    'cancelled' => 'Dibatalkan',
                ])
                ->disabled();

            $fields[] = Textarea::make('notes')
                ->label('Keterangan Pembatalan')
                ->rows(3)
                ->disabled()
                ->columnSpanFull()
                ->visible(fn(?TokenPurchase $record): bool => $record?->status === 'cancelled' && filled($record?->notes));
        }

        return $schema->schema($fields);
    }

    public static function table(Table $table): Table
    {
        $isAdmin = (bool) auth()->user()?->is_admin;

        $columns = [];

        // Only show user.name column for admin
        if ($isAdmin) {
            $columns[] = TextColumn::make('user.name')
                ->label('User')
                ->sortable()
                ->searchable();
        }

        $columns = array_merge($columns, [
            TextColumn::make('quantity')
                ->label('Token')
                ->sortable()
                ->formatStateUsing(fn(string $state) => $state . ' token'),

            TextColumn::make('total_price')
                ->label('Harga')
                ->sortable()
                ->formatStateUsing(fn(int $state) => 'Rp ' . number_format($state)),

            TextColumn::make('status')
                ->label('Status')
                ->sortable()
                ->badge()
                ->color(fn(string $state) => match ($state) {
                    'pending' => 'warning',
                    'confirmed' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                })
                ->formatStateUsing(fn(string $state) => match ($state) {
                    'pending' => 'Menunggu Konfirmasi',
                    'confirmed' => 'Dikonfirmasi',
                    'cancelled' => 'Dibatalkan',
                    default => $state,
                }),

            TextColumn::make('created_at')
                ->label('Tanggal')
                ->sortable()
                ->dateTime('d/m/Y H:i'),
        ]);

        if (! $isAdmin) {
            $columns[] = TextColumn::make('notes')
                ->label('Keterangan')
                ->state(fn(TokenPurchase $record): string => $record->status === 'cancelled'
                    ? ((string) ($record->notes ?: '-'))
                    : '-')
                ->limit(40)
                ->tooltip(fn(TokenPurchase $record): ?string => $record->status === 'cancelled' && filled($record->notes)
                    ? (string) $record->notes
                    : null)
                ->wrap();
        }

        // Only show proof_of_payment for admin
        if ($isAdmin) {
            $columns[] = ImageColumn::make('proof_of_payment')
                ->label('Bukti Pembayaran')
                ->disk('public')
                ->visibility('public');
        }

        $filters = [
            SelectFilter::make('status')
                ->label('Status')
                ->options([
                    'pending' => 'Menunggu Konfirmasi',
                    'confirmed' => 'Dikonfirmasi',
                    'cancelled' => 'Dibatalkan',
                ]),

            DateRangeFilter::make('created_at')
                ->label('Rentang Tanggal')
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
        ];

        if ($isAdmin) {
            array_unshift(
                $filters,
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
            );
        }

        return $table
            ->columns($columns)
            ->defaultSort('created_at', 'desc')
            ->filters($filters)
            ->actions([
                // Confirm action - only for admin
                ...$isAdmin ? [
                    Action::make('confirm')
                        ->label('Konfirmasi')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(TokenPurchase $record) => $record->status === 'pending')
                        ->action(function (TokenPurchase $record, Livewire $livewire): void {
                            static::confirmPurchase($record);

                            $livewire->dispatch('push-countdown-refresh');

                            if (method_exists($livewire, 'resetTable')) {
                                $livewire->resetTable();
                            }
                        }),

                    Action::make('cancel')
                        ->label('Batalkan')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn(TokenPurchase $record) => $record->status === 'pending')
                        ->schema([
                            Textarea::make('notes')
                                ->label('Keterangan Pembatalan')
                                ->required()
                                ->maxLength(1000)
                                ->rows(3),
                        ])
                        ->action(function (TokenPurchase $record, array $data, Livewire $livewire): void {
                            static::cancelPurchase($record, (string) ($data['notes'] ?? ''));

                            if (method_exists($livewire, 'resetTable')) {
                                $livewire->resetTable();
                            }
                        }),
                ] : [],

                Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn(TokenPurchase $record) => static::getUrl('view', ['record' => $record->id])),
            ]);
    }

    /**
     * Confirm token purchase and add tokens to user
     */
    protected static function confirmPurchase(TokenPurchase $record): void
    {
        $record->update([
            'status' => 'confirmed',
            'confirmed_at' => now(),
        ]);

        // Add tokens to user balance
        $record->user->addTokens($record->quantity);

        \Filament\Notifications\Notification::make()
            ->title('Pembelian token dikonfirmasi admin')
            ->body('Pembelian ' . $record->quantity . ' token telah dikonfirmasi. Token sudah ditambahkan ke akun Anda.')
            ->success()
            ->sendToDatabase($record->user);

        \Filament\Notifications\Notification::make()
            ->title('Pembelian token dikonfirmasi')
            ->success()
            ->send();
    }

    /**
     * Cancel token purchase
     */
    protected static function cancelPurchase(TokenPurchase $record, string $notes): void
    {
        $record->update([
            'status' => 'cancelled',
            'notes' => $notes,
        ]);

        \Filament\Notifications\Notification::make()
            ->title('Pembelian token dibatalkan admin')
            ->body('Pembelian token Anda dibatalkan. Keterangan: ' . $notes)
            ->danger()
            ->sendToDatabase($record->user);

        \Filament\Notifications\Notification::make()
            ->title('Pembelian token dibatalkan')
            ->warning()
            ->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTokenPurchases::route('/'),
            'view' => ViewTokenPurchase::route('/{record}'),
        ];
    }
}
