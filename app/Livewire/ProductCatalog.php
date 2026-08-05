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
use App\Services\VisitorStatsService;

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

    public bool $supportsCondition = true;

    public function resetFilters(): void
    {
        $this->reset(['search', 'categoryId', 'condition', 'deliverable']);
        $this->resetPage();
    }

    public function updatedCategoryId($value): void
    {
        $this->resetPage();

        if ($value === '' || $value === null) {
            $this->supportsCondition = true;

            return;
        }

        $category = Category::find($value);
        $this->supportsCondition = $category?->supportsCondition() ?? false;

        if (! $this->supportsCondition) {
            $this->condition = '';
        }
    }

    public function setCategory(string $categoryId): void
    {
        $this->categoryId = $categoryId;
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

        $categoryProductCounts = Product::whereIn('id', $eligibleProductIds)
            ->whereHas('lapak', fn($q) => $q->where('is_active', true))
            ->where('is_active', true)
            ->selectRaw('category_id, count(*) as aggregate')
            ->groupBy('category_id')
            ->pluck('aggregate', 'category_id');

        $quickCategories = $categories
            ->map(fn(Category $category) => [
                'id'            => $category->id,
                'category_name' => $category->category_name,
                'product_count' => $categoryProductCounts->get($category->id, 0),
            ])
            ->filter(fn(array $category) => $category['product_count'] > 0)
            ->sortByDesc('product_count')
            ->values();

        $showVisitorCount = filter_var(Setting::getValue('show_visitor_count', '1'), FILTER_VALIDATE_BOOLEAN);

        // Ensure supportsCondition is in sync with the current categoryId on every render
        $this->supportsCondition = ! $this->categoryId
            ? true
            : ($categories->firstWhere('id', $this->categoryId)?->supportsCondition() ?? false);

        return view('livewire.product-catalog', [
            'products'              => $products,
            'categories'            => $categories,
            'categoryProductCounts' => $categoryProductCounts,
            'quickCategories'       => $quickCategories,
            'showVisitorCount'      => $showVisitorCount,
            'visitorCount24h'       => $showVisitorCount ? VisitorStatsService::getCached() : null,
            'supportsCondition'     => $this->supportsCondition,
        ]);
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }
}
