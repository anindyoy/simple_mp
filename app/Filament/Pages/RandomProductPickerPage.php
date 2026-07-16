<?php

namespace App\Filament\Pages;

use UnitEnum;
use BackedEnum;
use App\Models\Product;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use App\Services\ProductScheduleService;

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

    public function generate(): void
    {
        $eligibleIds = ProductScheduleService::getEligibleProductIds();

        $this->product = Product::query()
            ->whereIn('id', $eligibleIds)
            ->where('is_active', true)
            ->with(['category', 'lapak'])
            ->inRandomOrder()
            ->first();

        $this->hasGenerated = true;

        if (! $this->product) {
            Notification::make()
                ->title('Tidak ada produk aktif yang bisa dipilih saat ini')
                ->warning()
                ->send();
        }
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
