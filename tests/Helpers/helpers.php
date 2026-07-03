<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\LapakProfile;
use App\Models\ProductModeration;
use Filament\Widgets\StatsOverviewWidget\Stat;

if (! function_exists('makeUser')) {
    function makeUser(bool $isAdmin = false, int $tokens = 10): User
    {
        $user = User::factory()->create([
            'is_admin'    => $isAdmin,
            'push_tokens' => $tokens,
        ]);

        $user->lapak()->save(
            LapakProfile::factory()->make()
        );

        return $user->fresh();
    }
}

if (! function_exists('makeCategory')) {
    function makeCategory(): Category
    {
        return Category::factory()->create();
    }
}

if (! function_exists('makeProduct')) {
    function makeProduct(LapakProfile $lapak, array $overrides = []): Product
    {
        $category = $overrides['category'] ?? makeCategory();

        $data = [
            'lapak_id'    => $lapak->id,
            'category_id' => $category->id,
            'is_active'   => true,
            'pushed_at'   => now(),
        ];

        if ($category->supportsCondition()) {
            $data['condition'] = 'baru';
        }

        return Product::factory()->withoutImages()->create(array_merge($data, $overrides));
    }
}

if (! function_exists('makePendingModeration')) {
    function makePendingModeration(Product $product, string $reason = 'Siap ditinjau'): ProductModeration
    {
        return ProductModeration::factory()->create([
            'product_id'  => $product->id,
            'type'        => ProductModeration::TYPE_REACTIVATION,
            'status'      => ProductModeration::STATUS_PENDING,
            'reason'      => $reason,
            // 'reviewed_by' => null,
        ]);
    }
}

if (! function_exists('getWidgetStats')) {
    /**
     * Invoke the protected getStats() method of a StatsOverviewWidget for direct assertions.
     *
     * @return array<Stat>
     */
    function getWidgetStats(object $widget): array
    {
        $method = new ReflectionMethod($widget, 'getStats');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }
}

if (! function_exists('findStat')) {
    /**
     * Matches by substring since a Stat's label may be an Htmlable (e.g. a label with an info tooltip).
     *
     * @param  array<Stat>  $stats
     */
    function findStat(array $stats, string $label): Stat
    {
        return collect($stats)->firstOrFail(
            fn(Stat $stat) => str_contains((string) $stat->getLabel(), $label)
        );
    }
}

if (! function_exists('getWidgetChartData')) {
    /**
     * Invoke the protected getData() method of a ChartWidget for direct assertions.
     *
     * @return array<string, mixed>
     */
    function getWidgetChartData(object $widget): array
    {
        $method = new ReflectionMethod($widget, 'getData');
        $method->setAccessible(true);

        return $method->invoke($widget);
    }
}
