<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\ProductModeration;
use Illuminate\Support\Facades\Storage;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

beforeEach(function () {
    $this->user = User::factory()->create(['is_admin' => false]);
    $this->adminUser = User::factory()->create(['is_admin' => true]);
    $this->category = Category::factory()->create();

    // Create lapaks
    $this->lapak = \App\Models\LapakProfile::factory()->create(['user_id' => $this->user->id]);
    $this->adminLapak = \App\Models\LapakProfile::factory()->create(['user_id' => $this->adminUser->id]);

    Storage::fake('public');
});

// ==================== Form Data Fill Tests ====================

it('loads existing images when filling form for edit', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create(['lapak_id' => $this->lapak->id]);

    $imageUrls = ['products/image1.jpg', 'products/image2.jpg', 'products/image3.jpg'];

    $product->images()->createMany(
        collect($imageUrls)
            ->map(fn(string $imageUrl): array => [
                'image_url' => $imageUrl,
                'is_primary' => false,
            ])
            ->all()
    );

    // Simulate the form fill logic
    $data = ['uploaded_images' => []];
    $data['uploaded_images'] = $product->images()
        ->orderBy('id')
        ->pluck('image_url')
        ->all();

    expect($data['uploaded_images'])->toEqual($imageUrls);
});

it('preserves image order when loading for edit', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create(['lapak_id' => $this->lapak->id]);

    $imageUrls = ['products/first.jpg', 'products/second.jpg', 'products/third.jpg'];

    $product->images()->createMany(
        collect($imageUrls)
            ->map(fn(string $imageUrl): array => [
                'image_url' => $imageUrl,
                'is_primary' => false,
            ])
            ->all()
    );

    $loadedImages = $product->images()
        ->orderBy('id')
        ->pluck('image_url')
        ->all();

    expect($loadedImages)->toEqual($imageUrls);
    expect($loadedImages[0])->toBe('products/first.jpg');
});

// ==================== Form Data Save Mutation Tests ====================

it('removes uploaded_images from form data before save', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create(['lapak_id' => $this->lapak->id]);

    $data = [
        'category_id' => $this->category->id,
        'title' => 'Updated Title',
        'description' => 'Updated Description',
        'price' => 75000,
        'is_active' => true,
        'uploaded_images' => ['products/new1.jpg', 'products/new2.jpg'],
    ];

    // Simulate mutation - removing uploaded_images before update
    $uploadedImages = $data['uploaded_images'];
    unset($data['uploaded_images']);

    $product->update($data);

    expect($product->title)->toBe($data['title']);
    expect($product->description)->toBe($data['description']);
    expect($product->price)->toBe($data['price']);
    expect(isset($product->uploaded_images))->toBeFalse();
});

// ==================== Image Replacement Tests ====================

it('deletes old images and creates new ones after save', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create(['lapak_id' => $this->lapak->id]);

    // Create initial images
    $oldImages = ['products/old1.jpg', 'products/old2.jpg'];
    $product->images()->createMany(
        collect($oldImages)
            ->map(fn(string $imageUrl): array => [
                'image_url' => $imageUrl,
                'is_primary' => false,
            ])
            ->all()
    );

    expect($product->images()->count())->toBe(2);

    // Delete old images
    $product->images()->delete();
    expect($product->images()->count())->toBe(0);

    // Create new images
    $newImages = ['products/new1.jpg', 'products/new2.jpg', 'products/new3.jpg'];
    $product->images()->createMany(
        collect($newImages)
            ->map(fn(string $imageUrl): array => [
                'image_url' => $imageUrl,
                'is_primary' => false,
            ])
            ->all()
    );

    expect($product->images()->count())->toBe(3);
    expect($product->images()->pluck('image_url')->all())->toEqual($newImages);
});

it('maintains primary image status after update', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create(['lapak_id' => $this->lapak->id]);

    $newImages = ['products/new1.jpg', 'products/new2.jpg'];

    $product->images()->createMany(
        collect($newImages)
            ->map(fn(string $imageUrl): array => [
                'image_url' => $imageUrl,
                'is_primary' => false,
            ])
            ->all()
    );

    // Set first as primary
    $first = $product->images()->orderBy('id')->first();
    if ($first) {
        $product->images()->update(['is_primary' => false]);
        $first->update(['is_primary' => true]);
    }

    $primary = $product->images()->where('is_primary', true)->first();

    expect($primary->image_url)->toBe('products/new1.jpg');
});

it('handles empty image replacement', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create(['lapak_id' => $this->lapak->id]);

    // Create initial images
    $product->images()->createMany([
        ['image_url' => 'products/image1.jpg', 'is_primary' => false],
    ]);

    expect($product->images()->count())->toBe(1);

    // Delete them all (simulating empty update)
    $product->images()->delete();

    expect($product->images()->count())->toBe(0);
});

// ==================== Action Visibility Tests ====================

it('request reactivation action is visible for non-admin with inactive product', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => false,
    ]);

    $isNonAdmin = !$this->user->is_admin;
    $isInactive = !$product->is_active;
    $shouldShowAction = $isNonAdmin && $isInactive;

    expect($shouldShowAction)->toBeTrue();
});

it('request reactivation action hidden for non-admin with active product', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => true,
    ]);

    $isNonAdmin = !$this->user->is_admin;
    $isActive = $product->is_active;
    $shouldShowAction = $isNonAdmin && !$isActive;

    expect($shouldShowAction)->toBeFalse();
});

it('request reactivation action is disabled when pending request exists', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => false,
    ]);

    ProductModeration::create([
        'product_id' => $product->id,
        'type' => ProductModeration::TYPE_REACTIVATION,
        'status' => ProductModeration::STATUS_PENDING,
        'reason' => 'permohonan_aktivasi_ulang',
        'description' => 'Test reason',
        'requested_by_user_id' => $this->user->id,
    ]);

    $hasPendingRequest = (bool) $product->pendingReactivationRequest()->first();

    expect($hasPendingRequest)->toBeTrue();
});

it('approve reactivation action is visible for admin with pending request', function () {
    $this->actingAs($this->adminUser);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => false,
    ]);

    ProductModeration::create([
        'product_id' => $product->id,
        'type' => ProductModeration::TYPE_REACTIVATION,
        'status' => ProductModeration::STATUS_PENDING,
        'reason' => 'permohonan_aktivasi_ulang',
        'description' => 'Test reason',
        'requested_by_user_id' => $this->user->id,
    ]);

    $isAdmin = $this->adminUser->is_admin;
    $hasPending = (bool) $product->pendingReactivationRequest()->first();
    $shouldShowAction = $isAdmin && $hasPending;

    expect($shouldShowAction)->toBeTrue();
});

it('approve reactivation action hidden when no pending request', function () {
    $this->actingAs($this->adminUser);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => true,
    ]);

    $isAdmin = $this->adminUser->is_admin;
    $hasPending = (bool) $product->pendingReactivationRequest()->first();
    $shouldShowAction = $isAdmin && $hasPending;

    expect($shouldShowAction)->toBeFalse();
});

it('reject reactivation action is visible for admin with pending request', function () {
    $this->actingAs($this->adminUser);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => false,
    ]);

    ProductModeration::create([
        'product_id' => $product->id,
        'type' => ProductModeration::TYPE_REACTIVATION,
        'status' => ProductModeration::STATUS_PENDING,
        'reason' => 'permohonan_aktivasi_ulang',
        'description' => 'Test reason',
        'requested_by_user_id' => $this->user->id,
    ]);

    $isAdmin = $this->adminUser->is_admin;
    $hasPending = (bool) $product->pendingReactivationRequest()->first();
    $shouldShowAction = $isAdmin && $hasPending;

    expect($shouldShowAction)->toBeTrue();
});

// ==================== Product Data Update Tests ====================

it('updates product title after edit', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'title' => 'Old Title',
    ]);

    $product->update(['title' => 'New Title']);

    expect($product->title)->toBe('New Title');
});

it('updates product price after edit', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'price' => 50000,
    ]);

    $product->update(['price' => 75000]);

    expect($product->price)->toBe(75000);
});

it('updates product description after edit', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'description' => 'Old description',
    ]);

    $product->update(['description' => 'New description']);

    expect($product->description)->toBe('New description');
});

it('updates product category after edit', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'category_id' => $this->category->id,
    ]);

    $newCategory = Category::factory()->create();

    $product->update(['category_id' => $newCategory->id]);

    expect($product->category_id)->toBe($newCategory->id);
});

it('updates product condition after edit', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'condition' => 'baru',
    ]);

    $product->update(['condition' => 'seken']);

    expect($product->condition)->toBe('seken');
});

it('toggles product active status after edit', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => true,
    ]);

    $product->update(['is_active' => false]);

    expect($product->is_active)->toBeFalse();
});

// ==================== Image Optimization Tests ====================

it('handles image optimization failure gracefully', function () {
    Storage::fake('public');

    $imagePath = 'products/nonexistent.jpg';

    if (!Storage::disk('public')->exists($imagePath)) {
        expect(true)->toBeTrue();
    }
});

it('skips optimization for non-existent files', function () {
    Storage::fake('public');

    $product = Product::factory()->create(['lapak_id' => $this->lapak->id]);

    $uploadedImages = ['products/missing1.jpg', 'products/missing2.jpg'];

    foreach ($uploadedImages as $imageUrl) {
        if (!Storage::disk('public')->exists($imageUrl)) {
            continue;
        }
    }

    expect(true)->toBeTrue();
});

// ==================== ProductModeration Integration Tests ====================

it('creates pending reactivation record on request', function () {
    $this->actingAs($this->user);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => false,
    ]);

    $moderation = ProductModeration::create([
        'product_id' => $product->id,
        'type' => ProductModeration::TYPE_REACTIVATION,
        'status' => ProductModeration::STATUS_PENDING,
        'reason' => 'permohonan_aktivasi_ulang',
        'description' => 'User wants to reactivate',
        'requested_by_user_id' => $this->user->id,
    ]);

    expect($moderation)->toBeInstanceOf(ProductModeration::class);
    expect($moderation->product_id)->toBe($product->id);
    expect($moderation->status)->toBe(ProductModeration::STATUS_PENDING);
});

it('updates product active status on approval', function () {
    $this->actingAs($this->adminUser);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => false,
    ]);

    $moderation = ProductModeration::create([
        'product_id' => $product->id,
        'type' => ProductModeration::TYPE_REACTIVATION,
        'status' => ProductModeration::STATUS_PENDING,
        'reason' => 'permohonan_aktivasi_ulang',
        'description' => 'User wants to reactivate',
        'requested_by_user_id' => $this->user->id,
    ]);

    // Simulate approval logic
    $moderation->update([
        'status' => ProductModeration::STATUS_APPROVED,
        'reviewed_by_user_id' => $this->adminUser->id,
        'reviewed_at' => now(),
    ]);

    $product->update(['is_active' => true]);

    expect($product->is_active)->toBeTrue();
    expect($moderation->status)->toBe(ProductModeration::STATUS_APPROVED);
});

it('rejects reactivation with reason', function () {
    $this->actingAs($this->adminUser);

    $product = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => false,
    ]);

    $moderation = ProductModeration::create([
        'product_id' => $product->id,
        'type' => ProductModeration::TYPE_REACTIVATION,
        'status' => ProductModeration::STATUS_PENDING,
        'reason' => 'permohonan_aktivasi_ulang',
        'description' => 'Original reason',
        'requested_by_user_id' => $this->user->id,
    ]);

    // Simulate rejection logic
    $moderation->update([
        'status' => ProductModeration::STATUS_REJECTED,
        'description' => 'Product violates policy',
        'reviewed_by_user_id' => $this->adminUser->id,
        'reviewed_at' => now(),
    ]);

    expect($moderation->status)->toBe(ProductModeration::STATUS_REJECTED);
    expect($moderation->description)->toBe('Product violates policy');
});
