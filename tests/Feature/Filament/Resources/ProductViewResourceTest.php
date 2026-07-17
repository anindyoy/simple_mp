<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Livewire\Livewire;
use App\Models\LapakProfile;
use App\Models\ProductView;
use App\Filament\Resources\ProductViewResource;
use App\Filament\Resources\ProductViewResource\Pages\ListProductViews;

function makeProductView(Product $product, array $attributes = []): ProductView
{
    return ProductView::create(array_merge([
        'product_id' => $product->id,
        'ip_address' => '127.0.0.1',
        'expires_at' => now()->addHour(),
    ], $attributes));
}

beforeEach(function () {
    $this->user = User::factory()->create([
        'is_admin' => false,
    ]);

    $this->adminUser = User::factory()->create([
        'is_admin' => true,
    ]);

    $this->category = Category::factory()->create();

    $this->lapakOwner = User::factory()->create([
        'is_admin' => false,
    ]);

    $this->lapak = LapakProfile::factory()->create([
        'user_id' => $this->lapakOwner->id,
    ]);

    $this->product = Product::factory()->withoutImages()->create([
        'lapak_id' => $this->lapak->id,
        'category_id' => $this->category->id,
    ]);
});

// ==================== Access Control Tests ====================

it('restricts product view resource access to admins', function () {
    $productView = makeProductView($this->product);

    $this->actingAs($this->user);

    expect(ProductViewResource::canViewAny())->toBeFalse();
    expect(ProductViewResource::canCreate())->toBeFalse();
    expect(ProductViewResource::canEdit($productView))->toBeFalse();
    expect(ProductViewResource::canDelete($productView))->toBeFalse();

    $this->actingAs($this->adminUser);

    expect(ProductViewResource::canViewAny())->toBeTrue();
    expect(ProductViewResource::canCreate())->toBeFalse();
    expect(ProductViewResource::canEdit($productView))->toBeFalse();
    expect(ProductViewResource::canDelete($productView))->toBeFalse();
});

// ==================== Query Tests ====================

it('eager loads product id and title on the eloquent query', function () {
    makeProductView($this->product);

    $record = ProductViewResource::getEloquentQuery()->first();

    expect($record->relationLoaded('product'))->toBeTrue();
    expect($record->product->title)->toBe($this->product->title);
});

// ==================== Table Rendering Tests ====================

it('renders product view table with expected records', function () {
    $this->actingAs($this->adminUser);

    makeProductView($this->product);
    makeProductView($this->product);

    Livewire::test(ListProductViews::class)
        ->assertCanSeeTableRecords(ProductView::all())
        ->assertCountTableRecords(2);
});

it('searches product views by ip address', function () {
    $this->actingAs($this->adminUser);

    $matching = makeProductView($this->product, ['ip_address' => '10.0.0.5']);
    makeProductView($this->product, ['ip_address' => '192.168.1.1']);

    Livewire::test(ListProductViews::class)
        ->searchTable('10.0.0.5')
        ->assertCanSeeTableRecords([$matching])
        ->assertCountTableRecords(1);
});

it('marks a product view as expired when expires_at is in the past', function () {
    $this->actingAs($this->adminUser);

    $expired = makeProductView($this->product, ['expires_at' => now()->subHour()]);
    $active = makeProductView($this->product, ['expires_at' => now()->addHour()]);

    Livewire::test(ListProductViews::class)
        ->assertTableColumnStateSet('is_expired', true, $expired)
        ->assertTableColumnStateSet('is_expired', false, $active);
});
