<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Livewire as LivewireComponent;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use App\Filament\Widgets\LapakGrowthChartWidget;
use App\Filament\Widgets\LapakGrowthTableWidget;
use App\Filament\Widgets\ProductGrowthChartWidget;
use App\Filament\Widgets\ProductGrowthTableWidget;

class GrowthReportPage extends Page
{
    use HasFiltersForm;

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Pertumbuhan Lapak & Produk';

    protected static ?string $title = 'Laporan Pertumbuhan Lapak & Produk';

    protected static ?string $slug = 'laporan/pertumbuhan';

    protected static UnitEnum|string|null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 50;

    protected string $view = 'filament.pages.growth-report-page';

    public static function shouldRegisterNavigation(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    public function mount(): void
    {
        abort_unless((bool) auth()->user()?->is_admin, 403);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('period')
                    ->label('Periode')
                    ->options([
                        'daily' => 'Harian',
                        'monthly' => 'Bulanan',
                    ])
                    ->default('daily')
                    ->native(false)
                    ->required(),

                DatePicker::make('start_date')
                    ->label('Tanggal Mulai')
                    ->default(fn () => now()->subDays(30)->startOfDay())
                    ->native(false)
                    ->required(),

                DatePicker::make('end_date')
                    ->label('Tanggal Akhir')
                    ->default(fn () => now())
                    ->native(false)
                    ->required(),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Grafik Pertumbuhan')
                    ->collapsible()
                    ->schema([
                        $this->makeWidgetComponent(LapakGrowthChartWidget::class),
                        $this->makeWidgetComponent(ProductGrowthChartWidget::class),
                    ])
                    ->columns(2),

                Section::make('Tabel Pertumbuhan')
                    ->collapsible()
                    ->schema([
                        $this->makeWidgetComponent(LapakGrowthTableWidget::class),
                        $this->makeWidgetComponent(ProductGrowthTableWidget::class),
                    ])
                    ->columns(2),
            ]);
    }

    protected function makeWidgetComponent(string $widgetClass): LivewireComponent
    {
        return LivewireComponent::make($widgetClass, fn (): array => [
            ...$widgetClass::getDefaultProperties(),
            'pageFilters' => $this->filters,
        ])->key($widgetClass)->liberatedFromContainerGrid();
    }
}
