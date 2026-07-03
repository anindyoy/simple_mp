<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Reports\ReportResource;
use App\Models\LapakProfile;
use App\Models\Product;
use App\Models\Report;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class AdminPendingReportsWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = ['md' => 1];

    public static function canView(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Laporan Terbaru Menunggu Tinjauan')
            ->query(
                Report::query()->where('status', 'pending')->latest()->limit(5)
            )
            ->columns([
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->limit(40),

                TextColumn::make('reportable_type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        Product::class => 'Produk',
                        LapakProfile::class => 'Lapak',
                        default => class_basename($state),
                    }),

                TextColumn::make('reporter_name')
                    ->label('Pelapor'),

                TextColumn::make('created_at')
                    ->label('Dilaporkan')
                    ->since(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label('Lihat')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Report $record) => ReportResource::getUrl('details', [
                        'type' => base64_encode($record->reportable_type),
                        'id' => $record->reportable_id,
                    ])),
            ])
            ->paginated(false);
    }
}
