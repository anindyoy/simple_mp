<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Models\Product;
use App\Models\Report;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Models\ProductModeration;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Enums\FontWeight;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Collection;
use App\Filament\Resources\Reports\ReportResource;
use Filament\Infolists\Components\RepeatableEntry;

class ViewReportDetails extends Page
{
    protected static string $resource = ReportResource::class;
    protected string $view = 'filament.resources.report-resource.pages.view-report-details';

    public Collection $reports;
    public $target;

    public function mount($type, $id)
    {
        $model = base64_decode($type);

        $this->target = $model::findOrFail($id);

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
                ->visible(fn (): bool => $this->target instanceof Product)
                ->disabled(fn (): bool => ! ($this->target instanceof Product) || ! $this->target->is_active)
                ->schema([
                    TextInput::make('reason')
                        ->label('Sebab Penonaktifan')
                        ->required()
                        ->maxLength(255)
                        ->default(fn (): ?string => $this->reports->first()?->reason),
                    Textarea::make('description')
                        ->label('Penjelasan untuk Pemilik Produk')
                        ->rows(4)
                        ->nullable(),
                ])
                ->action(function (array $data): void {
                    if (! ($this->target instanceof Product)) {
                        return;
                    }

                    $latestReport = $this->reports->first();

                    ProductModeration::create([
                        'product_id' => $this->target->id,
                        'report_id' => $latestReport?->id,
                        'type' => ProductModeration::TYPE_DEACTIVATION,
                        'status' => ProductModeration::STATUS_APPROVED,
                        'reason' => $data['reason'],
                        'description' => $data['description'] ?? null,
                        'reviewed_by_user_id' => auth()->id(),
                        'reviewed_at' => now(),
                    ]);

                    $this->target->update([
                        'is_active' => false,
                    ]);

                    Report::query()
                        ->where('reportable_type', Product::class)
                        ->where('reportable_id', $this->target->id)
                        ->where('status', 'pending')
                        ->update(['status' => 'reviewed']);

                    $this->reports = Report::where('reportable_type', Product::class)
                        ->where('reportable_id', $this->target->id)
                        ->latest()
                        ->get();

                    Notification::make()
                        ->title('Produk berhasil dinonaktifkan')
                        ->success()
                        ->send();
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
                            ->state(fn() => $this->target->title ?? $this->target->name)
                            ->weight(FontWeight::Bold),

                        TextEntry::make('type')
                            ->label('Tipe')
                            ->state(
                                fn() =>
                                str_contains(get_class($this->target), 'Product')
                                    ? 'Produk'
                                    : 'Lapak'
                            ),
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
