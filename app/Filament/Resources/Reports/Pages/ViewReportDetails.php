<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Models\Report;
use App\Models\Product;
use App\Models\LapakProfile;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Models\ProductModeration;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Collection;
use Filament\Support\Enums\FontWeight;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use App\Services\ProductModerationService;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\Reports\ReportResource;
use Filament\Infolists\Components\RepeatableEntry;
use App\Filament\Resources\Products\ProductResource;

class ViewReportDetails extends Page
{
    protected static string $resource = ReportResource::class;
    protected string $view = 'filament.resources.report-resource.pages.view-report-details';

    public Collection $reports;
    public $target;

    public function mount($type, $id)
    {
        $model = base64_decode($type);

        if ($model === \App\Models\Product::class) {
            $this->target = $model::with(['lapak.user'])->findOrFail($id);
        } else {
            $this->target = $model::with(['user'])->findOrFail($id);
        }

        $this->reports = Report::where('reportable_type', $model)
            ->where('reportable_id', $id)
            ->latest()
            ->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('deactivateProduct')
                ->label('Nonaktifkan Produk')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->disabled(fn(): bool => ! $this->target?->is_active)
                ->schema([
                    TextInput::make('reason')
                        ->label('Sebab Penonaktifan')
                        ->required()
                        ->maxLength(255)
                        ->default(fn(): ?string => $this->reports->first()?->reason),
                    Textarea::make('description')
                        ->label('Penjelasan untuk Pemilik Produk')
                        ->rows(4)
                        ->nullable(),
                ])
                ->action(function (array $data, ProductModerationService $service): void {

                    $latestReport = $this->reports->first();

                    try {

                        $service->deactivateTarget(
                            $this->target,
                            auth()->user(),
                            $data['reason'],
                            $data['description'] ?? null,
                            $latestReport
                        );

                        // tentukan owner tergantung tipe
                        $owner = match (true) {
                            $this->target instanceof Product =>
                            $this->target->lapak?->user,

                            $this->target instanceof LapakProfile =>
                            $this->target->user,

                            default => null,
                        };

                        if ($owner) {
                            Notification::make()
                                ->title('Dinonaktifkan oleh admin')
                                ->body(
                                    ($this->target instanceof Product
                                        ? 'Produk "' . $this->target->title . '"'
                                        : 'Lapak "' . $this->target->name . '"')
                                        . ' dinonaktifkan. Sebab: ' . $data['reason']
                                )
                                ->danger()
                                ->sendToDatabase($owner);
                        }

                        Notification::make()
                            ->title('Berhasil dinonaktifkan')
                            ->success()
                            ->send();
                    } catch (\Throwable $e) {
                        \Log::error('Error deactivating target: ' . $e->getMessage());
                        Notification::make()
                            ->title('Terjadi kesalahan')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->record($this->target)
            ->schema([

                Section::make('Informasi Target')
                    ->schema([

                        TextEntry::make('name')
                            ->label('Nama')
                            ->state(
                                fn() =>
                                $this->target->title ?? $this->target->name
                            )
                            ->weight(FontWeight::Bold),

                        TextEntry::make('type')
                            ->label('Tipe')
                            ->state(
                                fn() =>
                                $this->target instanceof \App\Models\Product
                                    ? 'Produk'
                                    : 'Lapak'
                            ),

                        // 🔹 Nama Toko (hanya jika produk)
                        TextEntry::make('nama_toko')
                            ->label('Nama Toko')
                            ->visible(
                                fn() =>
                                $this->target instanceof \App\Models\Product
                            )
                            ->state(
                                fn() =>
                                $this->target->lapak?->name
                            ),

                        // 🔹 Nama Pemilik Lapak (selalu tampil)
                        TextEntry::make('nama_pemilik')
                            ->label('Pemilik Lapak')
                            ->state(function () {

                                if ($this->target instanceof \App\Models\Product) {
                                    return $this->target->lapak?->user?->name;
                                }

                                return $this->target->user?->name;
                            }),

                    ])
                    ->columns(2),

                Section::make('Daftar Laporan')
                    ->schema([
                        RepeatableEntry::make('reports')
                            ->state($this->reports)
                            ->schema([
                                TextEntry::make('reason')
                                    ->label('Alasan')
                                    ->badge()
                                    ->color('danger'),

                                TextEntry::make('status')
                                    ->badge()
                                    ->color(
                                        fn($state) =>
                                        $state === 'pending'
                                            ? 'warning'
                                            : ($state === 'reviewed'
                                                ? 'success'
                                                : 'danger')
                                    ),

                                TextEntry::make('description')
                                    ->label('Keterangan')
                                    ->columnSpanFull(),

                                TextEntry::make('created_at')
                                    ->label('Dilaporkan Pada')
                                    ->dateTime(),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }
}
