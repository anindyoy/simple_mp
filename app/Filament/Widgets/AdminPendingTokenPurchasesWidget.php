<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TokenPurchases\TokenPurchaseResource;
use App\Models\TokenPurchase;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class AdminPendingTokenPurchasesWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = ['md' => 1];

    public static function canView(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Token Purchase Menunggu Konfirmasi')
            ->query(
                TokenPurchase::query()->where('status', 'pending')->oldest()->limit(5)
            )
            ->columns([
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable(),

                TextColumn::make('quantity')
                    ->label('Token')
                    ->formatStateUsing(fn(string $state) => $state . ' token'),

                TextColumn::make('total_price')
                    ->label('Harga')
                    ->formatStateUsing(fn(int $state) => 'Rp ' . number_format($state)),

                TextColumn::make('created_at')
                    ->label('Diajukan')
                    ->since(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn(TokenPurchase $record) => TokenPurchaseResource::getUrl('view', ['record' => $record->id])),
            ])
            ->paginated(false);
    }
}
