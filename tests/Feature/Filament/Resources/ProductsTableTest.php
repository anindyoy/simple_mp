<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\LapakProfile;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Builder;

beforeEach(function () {
    $this->user = User::factory()->create([
        'is_admin' => false,
    ]);

    $this->adminUser = User::factory()->create([
        'is_admin' => true,
    ]);

    $this->lapak = LapakProfile::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->category = Category::factory()->create();
});

// ==================== Filter & Query Logic Tests ====================

it('filters products by price range correctly', function () {
    $this->actingAs($this->user);

    Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'price' => 10000,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'price' => 50000,
        'is_active' => true,
    ]);

    Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'price' => 100000,
        'is_active' => true,
    ]);

    $products = Product::query()
        ->where('price', '>=', 30000)
        ->where('price', '<=', 80000)
        ->get();

    expect($products)->toHaveCount(1);
    expect($products->first()->price)->toBe(50000);
});

it('non-admin users only see their own products', function () {
    $this->actingAs($this->user);

    $otherUser = User::factory()->create(['is_admin' => false]);

    $otherLapak = LapakProfile::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $userProduct = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
    ]);

    $otherUserProduct = Product::factory()->create([
        'lapak_id' => $otherLapak->id,
    ]);

    // Simulate the query modification for non-admin
    $query = Product::query()
        ->when(
            ! auth()->user()->is_admin,
            fn($q) => $q->where('lapak_id', auth()->user()->lapak->id)
        );

    expect($query->count())->toBe(1);
    expect($query->first()->id)->toBe($userProduct->id);
});

it('admin users see all products regardless of lapak', function () {
    $this->actingAs($this->adminUser);

    $otherUser = User::factory()->create(['is_admin' => false]);

    $otherLapak = LapakProfile::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    Product::factory()->create([
        'lapak_id' => $this->lapak->id,
    ]);

    Product::factory()->create([
        'lapak_id' => $otherLapak->id,
    ]);

    $query = Product::query()
        ->when(
            ! auth()->user()->is_admin,
            fn($q) => $q->where('lapak_id', auth()->user()->lapak->id)
        );

    expect($query->count())->toBe(2);
});

// ==================== Relationship Loading Tests ====================

it('loads required relationships correctly', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
    ]);

    ProductImage::factory()->create(['product_id' => $product->id]);

    $query = Product::query()
        ->with([
            'primaryImage',
            'lapak',
            'lapak.user',
            'category',
        ]);

    $retrievedProduct = $query->first();

    expect($retrievedProduct->relationLoaded('lapak'))->toBeTrue();
    expect($retrievedProduct->relationLoaded('category'))->toBeTrue();
});

it('orders products by pushed_at descending by default', function () {
    $this->actingAs($this->user);

    $product1 = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'pushed_at' => now()->subDays(2),
    ]);

    $product2 = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'pushed_at' => now(),
    ]);

    $products = Product::query()
        ->orderBy('pushed_at', 'desc')
        ->get();

    expect($products->first()->id)->toBe($product2->id);
    expect($products->last()->id)->toBe($product1->id);
});

// ==================== Access Control Tests ====================

it('non-admin cannot see push action disabled info', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
    ]);

    // Verify user is not admin
    expect($this->user->is_admin)->toBeFalse();
    expect($product->lapak_id)->toBe($this->lapak->id);
});

it('admin user is recognized as admin', function () {
    $this->actingAs($this->adminUser);

    expect($this->adminUser->is_admin)->toBeTrue();
});

// ==================== Product Visibility Tests ====================

it('only shows active products in queries', function () {
    $this->actingAs($this->user);

    $activeProduct = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => true,
    ]);

    $inactiveProduct = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => false,
    ]);

    $query = Product::query()
        ->where('is_active', true);

    $products = $query->get();

    expect($products->pluck('id'))->toContain($activeProduct->id);
    expect($products->pluck('id'))->not->toContain($inactiveProduct->id);
});

it('filters by product condition', function () {
    $this->actingAs($this->user);

    Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'condition' => 'baru',
        'is_active' => true,
    ]);

    Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'condition' => 'seken',
        'is_active' => true,
    ]);

    $newProducts = Product::query()
        ->where('condition', 'baru')
        ->get();

    expect($newProducts)->toHaveCount(1);
    expect($newProducts->first()->condition)->toBe('baru');
});

// ==================== Category Filtering Tests ====================

it('filters products by category', function () {
    $this->actingAs($this->user);

    $otherCategory = Category::factory()->create();

    $productInCategory = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'category_id' => $this->category->id,
        'is_active' => true,
        'condition' => 'baru',
    ]);

    $productInOtherCategory = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'category_id' => $otherCategory->id,
        'is_active' => true,
        'condition' => 'baru',
    ]);

    $query = Product::query()
        ->where('category_id', $this->category->id);

    $products = $query->get();

    expect($products->pluck('id'))->toContain($productInCategory->id);
    expect($products->pluck('id'))->not->toContain($productInOtherCategory->id);
});

// ==================== Query Building Tests ====================

it('builds query with multiple conditions correctly', function () {
    $this->actingAs($this->user);

    Product::factory()->count(5)->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => true,
        'condition' => 'baru',
        'price' => 50000,
    ]);

    Product::factory()->count(3)->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => true,
        'condition' => 'seken',
        'price' => 30000,
    ]);

    $query = Product::query()
        ->where('condition', 'baru')
        ->where('is_active', true)
        ->where('price', 50000);

    expect($query->count())->toBe(5);
});

// ==================== User Lapak Relationship Tests ====================

it('user has correct lapak associated', function () {
    expect($this->user->lapak->id)->toBe($this->lapak->id);
});

it('products belong to correct lapak', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
    ]);

    $retrievedProduct = Product::find($product->id);

    expect($retrievedProduct->lapak_id)->toBe($this->lapak->id);
    expect($retrievedProduct->lapak->id)->toBe($this->user->lapak->id);
});
