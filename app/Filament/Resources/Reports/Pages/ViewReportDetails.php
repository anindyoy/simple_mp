<?php

namespace App\Filament\Resources\Reports\Pages;

use App\Models\Report;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Resources\Reports\ReportResource;
use Filament\Infolists\Components\RepeatableEntry;

class ViewReportDetails extends Page
{
    protected static string $resource = ReportResource::class;
    protected string $view = 'filament.resources.report-resource.pages.view-report-details';

    public $reports;
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
