<?php

namespace App\Livewire;

use App\Models\Product;
use App\Models\Setting;
use Livewire\Component;
use App\Models\Category;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;
use App\Services\ProductScheduleService;

class ProductCatalog extends Component
{
    use WithPagination;

    #[Url(as: 'search', except: '')]
    public string $search = '';

    #[Url(as: 'category', except: '')]
    public string $categoryId = '';

    #[Url(as: 'condition', except: '')]
    public string $condition = '';

    #[Url(as: 'deliverable', except: false)]
    public bool $deliverable = false;

    public function resetFilters(): void
    {
        $this->reset(['search', 'categoryId', 'condition', 'deliverable']);
        $this->resetPage();
    }

    public function render()
    {
        $eligibleProductIds = ProductScheduleService::getEligibleProductIds();

        $products = Product::with([
            'media',
            'lapak:id,name,slug,address_raw',
            'category:id,category_name',
        ])
            ->whereIn('id', $eligibleProductIds)
            ->whereHas('lapak', fn($q) => $q->where('is_active', true))
            ->where('is_active', true)
            ->when($this->search,      fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->categoryId,  fn($q) => $q->where('category_id', $this->categoryId))
            ->when($this->condition,   fn($q) => $q->where('condition', $this->condition))
            ->when($this->deliverable, fn($q) => $q->where('can_be_delivered', true))
            ->orderBy('pushed_at', 'desc')
            ->paginate(25);

        $categories = Cache::remember(
            'categories_list',
            3600,
            fn() => Category::orderBy('category_name')->get()
        );

        return view('livewire.product-catalog', [
            'products'   => $products,
            'categories' => $categories,
        ]);
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }
}
