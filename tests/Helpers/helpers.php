<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\LapakProfile;
use App\Models\ProductModeration;

if (! function_exists('makeUser')) {
    function makeUser(bool $isAdmin = false): User
    {
        $user = User::factory()->create([
            'is_admin' => $isAdmin,
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

        return Product::factory()->create(array_merge($data, $overrides));
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
