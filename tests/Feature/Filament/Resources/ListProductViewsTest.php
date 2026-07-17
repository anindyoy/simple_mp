<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Livewire\Livewire;
use App\Models\LapakProfile;
use App\Models\ProductView;
use App\Filament\Resources\ProductViewResource;
use App\Filament\Resources\ProductViewResource\Pages\ListProductViews;

function makeListedProductView(Product $product, array $attributes = []): ProductView
{
    return ProductView::create(array_merge([
        'product_id' => $product->id,
        'ip_address' => '127.0.0.1',
        'expires_at' => now()->addHour(),
    ], $attributes));
}

beforeEach(function () {
    $this->admin = User::factory()->create([
        'is_admin' => true,
    ]);

    $this->user = User::factory()->create([
        'is_admin' => false,
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

describe('authorization', function () {
    test('admin dapat mengakses halaman product views', function () {
        $this->actingAs($this->admin);

        $this->get(ListProductViews::getUrl())
            ->assertOk();
    });

    test('non admin diarahkan dari halaman product views', function () {
        $this->actingAs($this->user);

        $this->get(ListProductViews::getUrl())
            ->assertRedirect();
    });
});

it('uses ProductViewResource as its resource', function () {
    $page = new ListProductViews();

    $reflection = new ReflectionClass($page);
    $property = $reflection->getProperty('resource');
    $property->setAccessible(true);

    expect($property->getValue($page))->toBe(ProductViewResource::class);
});

it('has no header actions since creating product views is disabled', function () {
    $this->actingAs($this->admin);

    $page = new ListProductViews();

    $reflection = new ReflectionClass($page);
    $method = $reflection->getMethod('getHeaderActions');
    $method->setAccessible(true);

    expect($method->invoke($page))->toBe([]);
});

it('lists product views sorted by latest viewed first', function () {
    $this->actingAs($this->admin);

    $older = makeListedProductView($this->product, ['created_at' => now()->subDay()]);
    $newer = makeListedProductView($this->product, ['created_at' => now()]);

    Livewire::test(ListProductViews::class)
        ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
});

it('renders empty state when there are no product views', function () {
    $this->actingAs($this->admin);

    Livewire::test(ListProductViews::class)
        ->assertCountTableRecords(0)
        ->assertSuccessful();
});
