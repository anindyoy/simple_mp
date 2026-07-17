<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use App\Models\Product;
use App\Models\RandomProductHistory;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use App\Services\ProductScheduleService;
use Illuminate\Support\Collection;

class RandomProductPickerPage extends Page
{
    protected static ?string $title = 'Pilih Produk Acak';

    protected static ?string $navigationLabel = 'Pilih Produk Acak';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-sparkles';

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.random-product-picker-page';

    public ?Product $product = null;

    public bool $hasGenerated = false;

    public Collection $history;

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

        $this->history = RandomProductHistory::query()
            ->with('product.category', 'product.lapak')
            ->latest()
            ->take(20)
            ->get();
    }

    public function generate(): void
    {
        $eligibleIds = ProductScheduleService::getEligibleProductIds();

        // Exclude products that appeared in the last 10 history entries
        $recentProductIds = RandomProductHistory::query()
            ->latest()
            ->take(10)
            ->pluck('product_id')
            ->unique()
            ->toArray();

        // Exclude products from lapak that appeared in the last 7 history entries
        $recentLapakIds = RandomProductHistory::query()
            ->latest()
            ->take(7)
            ->pluck('lapak_id')
            ->unique()
            ->toArray();

        $query = Product::query()
            ->whereIn('id', $eligibleIds)
            ->where('is_active', true)
            ->with(['category', 'lapak']);

        if (! empty($recentProductIds)) {
            $query->whereNotIn('id', $recentProductIds);
        }

        if (! empty($recentLapakIds)) {
            $query->whereNotIn('lapak_id', $recentLapakIds);
        }

        $this->product = $query->inRandomOrder()->first();

        // If all eligible products are in recent history, allow any eligible product
        if (! $this->product) {
            $this->product = Product::query()
                ->whereIn('id', $eligibleIds)
                ->where('is_active', true)
                ->with(['category', 'lapak'])
                ->inRandomOrder()
                ->first();
        }

        $this->hasGenerated = true;

        if ($this->product) {
            RandomProductHistory::create([
                'product_id' => $this->product->id,
                'lapak_id' => $this->product->lapak_id,
                'user_id' => auth()->id(),
            ]);

            // Refresh history
            $this->history = RandomProductHistory::query()
                ->with('product.category', 'product.lapak')
                ->latest()
                ->take(20)
                ->get();
        }

        if (! $this->product) {
            Notification::make()
                ->title('Tidak ada produk aktif yang bisa dipilih saat ini')
                ->warning()
                ->send();
        }
    }

    public function buildHistoryCopyText(Product $product): string
    {
        $lines = [
            'Nama Produk: ' . $product->title,
            'Kategori: ' . ($product->category?->category_name ?? '-'),
        ];

        if ($product->hasCondition()) {
            $lines[] = 'Kondisi: ' . $product->conditionLabel();
        }

        $lines[] = 'Harga: Rp ' . number_format((float) $product->price, 0, ',', '.');
        $lines[] = 'Bisa Diantar: ' . ($product->can_be_delivered ? 'Ya' : 'Tidak');
        $lines[] = 'Lapak: ' . ($product->lapak?->name ?? '-');
        $lines[] = 'Dibuat: ' . $product->created_at->translatedFormat('d F Y');
        $lines[] = 'Link Produk: ' . route('product.show', $product);

        return implode("\n", $lines);
    }

    public function getCopyText(): string
    {
        if (! $this->product) {
            return '';
        }

        $product = $this->product;

        $lines = [
            'Produk Pilihan Hari Ini',
            '',
            'Nama Produk: ' . $product->title,
            'Kategori: ' . ($product->category?->category_name ?? '-'),
        ];

        if ($product->hasCondition()) {
            $lines[] = 'Kondisi: ' . $product->conditionLabel();
        }

        $lines[] = 'Harga: Rp ' . number_format((float) $product->price, 0, ',', '.');
        $lines[] = 'Bisa Diantar: ' . ($product->can_be_delivered ? 'Ya' : 'Tidak');
        // $lines[] = 'Dilihat: ' . number_format($product->views_count) . ' kali';
        $lines[] = 'Lapak: ' . ($product->lapak?->name ?? '-');
        $lines[] = 'Dibuat: ' . $product->created_at->translatedFormat('d F Y');
        $lines[] = 'Link Produk: ' . route('product.show', $product);

        return implode("\n", $lines);
    }
}
