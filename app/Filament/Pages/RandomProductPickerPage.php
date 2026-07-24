<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use App\Models\Product;
use App\Models\Setting;
use Filament\Pages\Page;
use App\Models\LapakProfile;
use Illuminate\Support\Collection;
use App\Models\RandomProductHistory;
use App\Services\ProductScheduleService;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class RandomProductPickerPage extends Page
{
    protected static ?string $title = 'Pilih Produk Acak';

    protected static ?string $navigationLabel = 'Pilih Produk Acak';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-sparkles';

    protected static UnitEnum|string|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.random-product-picker-page';

    private const DEFAULT_MAX_PRODUCT_HISTORY = 10;

    private const DEFAULT_MAX_LAPAK_HISTORY = 7;

    private const SETTING_MAX_PRODUCT_HISTORY = 'random_product_picker_max_product_history';

    private const SETTING_MAX_LAPAK_HISTORY = 'random_product_picker_max_lapak_history';

    public ?Product $product = null;

    public bool $hasGenerated = false;

    public Collection $history;

    public int $maxProductHistory = self::DEFAULT_MAX_PRODUCT_HISTORY;

    public int $maxLapakHistory = self::DEFAULT_MAX_LAPAK_HISTORY;

    public int $activeProductCount = 0;

    public int $activeLapakCount = 0;

    public string $search = '';

    public int $page = 1;

    public int $totalHistory = 0;

    public function updatedSearch(): void
    {
        $this->page = 1;
        $this->refreshHistory();
    }

    public function nextPage(): void
    {
        $this->page++;
        $this->refreshHistory();
    }

    public function prevPage(): void
    {
        $this->page = max(1, $this->page - 1);
        $this->refreshHistory();
    }

    public function recommendedMaxProductHistory(): int
    {
        return (int) round($this->activeProductCount * 0.5);
    }

    public function recommendedMaxLapakHistory(): int
    {
        return (int) round($this->activeLapakCount * 0.5);
    }

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

        $this->maxProductHistory = Setting::getIntValue(self::SETTING_MAX_PRODUCT_HISTORY, self::DEFAULT_MAX_PRODUCT_HISTORY);
        $this->maxLapakHistory = Setting::getIntValue(self::SETTING_MAX_LAPAK_HISTORY, self::DEFAULT_MAX_LAPAK_HISTORY);

        $this->activeProductCount = Product::query()->where('is_active', true)->count();
        $this->activeLapakCount = LapakProfile::query()->where('is_active', true)->count();

        $this->refreshHistory();
    }

    public function refreshHistory(): void
    {
        $query = RandomProductHistory::query()
            ->with('product.category', 'product.lapak');

        if ($this->search !== '') {
            $query->whereHas('product', function ($q) {
                $q->where('title', 'like', "%{$this->search}%");
            })->orWhereHas('product.lapak', function ($q) {
                $q->where('name', 'like', "%{$this->search}%");
            });
        }

        $this->totalHistory = $query->count();
        $this->history = $query->latest()
            ->skip(($this->page - 1) * 20)
            ->take(20)
            ->get();
    }

    public function generate(): void
    {
        $eligibleIds = ProductScheduleService::getEligibleProductIds();

        $maxProductHistory = max(0, $this->maxProductHistory);
        $maxLapakHistory = max(0, $this->maxLapakHistory);

        $this->persistHistorySettings($maxProductHistory, $maxLapakHistory);

        $baseQuery = $this->buildEligibleProductQuery(
            $eligibleIds,
            $this->recentHistoryIds('product_id', $maxProductHistory),
            $this->recentHistoryIds('lapak_id', $maxLapakHistory),
        );

        $this->product = $this->pickProductByIncreasingPercentage($baseQuery)
            ?? $this->pickFallbackProduct($eligibleIds);

        $this->hasGenerated = true;

        if ($this->product) {
            $this->recordHistory($this->product);
            $this->refreshHistory();
        } else {
            Notification::make()
                ->title('Tidak ada produk aktif yang bisa dipilih saat ini')
                ->warning()
                ->send();
        }
    }

    private function persistHistorySettings(int $maxProductHistory, int $maxLapakHistory): void
    {
        Setting::setValue(self::SETTING_MAX_PRODUCT_HISTORY, (string) $maxProductHistory);
        Setting::setValue(self::SETTING_MAX_LAPAK_HISTORY, (string) $maxLapakHistory);
    }

    /**
     * IDs to exclude, taken from the most recent N history entries.
     */
    private function recentHistoryIds(string $column, int $limit): array
    {
        return RandomProductHistory::query()
            ->latest()
            ->take($limit)
            ->pluck($column)
            ->unique()
            ->toArray();
    }

    private function buildEligibleProductQuery(Collection|array $eligibleIds, array $excludedProductIds, array $excludedLapakIds): Builder
    {
        $query = Product::query()
            ->whereIn('id', $eligibleIds)
            ->where('is_active', true)
            ->with(['category', 'lapak']);

        if (! empty($excludedProductIds)) {
            $query->whereNotIn('id', $excludedProductIds);
        }

        if (! empty($excludedLapakIds)) {
            $query->whereNotIn('lapak_id', $excludedLapakIds);
        }

        return $query;
    }

    /**
     * Widen the eligible pool in 10% steps (30%..100%) of least-recently-pushed
     * products until a random pick succeeds.
     */
    private function pickProductByIncreasingPercentage(Builder $baseQuery): ?Product
    {
        $totalEligible = (clone $baseQuery)->count();

        for ($percentage = 30; $percentage <= 100; $percentage += 10) {
            $limit = (int) ceil($totalEligible * ($percentage / 100));

            if ($limit === 0) {
                continue;
            }

            $product = (clone $baseQuery)
                ->orderBy('pushed_at')
                ->limit($limit)
                ->inRandomOrder()
                ->first();

            if ($product) {
                return $product;
            }
        }

        return null;
    }

    private function pickFallbackProduct(Collection|array $eligibleIds): ?Product
    {
        return Product::query()
            ->whereIn('id', $eligibleIds)
            ->where('is_active', true)
            ->with(['category', 'lapak'])
            ->inRandomOrder()
            ->first();
    }

    private function recordHistory(Product $product): void
    {
        RandomProductHistory::create([
            'product_id' => $product->id,
            'lapak_id' => $product->lapak_id,
            'user_id' => auth()->id(),
        ]);
    }

    public function pushProduct(int $productId): void
    {
        $product = Product::query()->whereKey($productId)->firstOrFail();
        $product->update(['pushed_at' => now(), 'pushed_by' => 'system']);

        $this->refreshHistory();

        Notification::make()
            ->title('Produk berhasil diangkat')
            ->success()
            ->send();
    }

    public function deleteHistory(int $historyId): void
    {
        RandomProductHistory::query()->whereKey($historyId)->delete();

        $this->refreshHistory();

        Notification::make()
            ->title('Riwayat dihapus')
            ->success()
            ->send();
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
