<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Category;
use App\Models\LapakProfile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use App\Services\ProductScheduleService;
use App\Http\Controllers\ProductController;

beforeEach(function () {
    Route::get('/products', [\App\Http\Controllers\ProductController::class, 'index'])
        ->name('products.index');

    Route::get('/products/{product}', [\App\Http\Controllers\ProductController::class, 'show'])
        ->name('product.show');
});

describe('ProductController@index', function () {
    it('menampilkan daftar produk aktif yang eligible', function () {
        $category = Category::factory()->create();

        $lapak = LapakProfile::factory()->create();

        $eligibleProduct = Product::factory()->create([
            'title' => 'iPhone 15',
            'category_id' => $category->id,
            'lapak_id' => $lapak->id,
        ]);

        $notEligibleProduct = Product::factory()->create([
            'title' => 'Samsung Galaxy',
            'category_id' => $category->id,
            'lapak_id' => $lapak->id,
        ]);

        ProductScheduleService::rebuild($eligibleProduct->id);

        $response = $this->get(route('products.index'));

        $response
            ->assertOk()
            ->assertViewIs('main')
            ->assertViewHas('products')
            ->assertSee('iPhone 15')
            ->assertDontSee('Samsung Galaxy');
    });

    it('dapat filter berdasarkan search', function () {
        $lapak = LapakProfile::factory()->create();

        $productA = Product::factory()->create([
            'title' => 'Macbook Pro',
            'lapak_id' => $lapak->id,
        ]);

        $productB = Product::factory()->create([
            'title' => 'Asus ROG',
            'lapak_id' => $lapak->id,
        ]);

        ProductScheduleService::rebuild($productA->id);
        ProductScheduleService::rebuild($productB->id);

        $response = $this->get(route('products.index', [
            'search' => 'Macbook',
        ]));

        $response
            ->assertOk()
            ->assertSee('Macbook Pro')
            ->assertDontSee('Asus ROG');
    });

    it('dapat filter berdasarkan kategori', function () {
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();

        $lapak = LapakProfile::factory()->create();

        $productA = Product::factory()->create([
            'title' => 'Produk A',
            'category_id' => $categoryA->id,
            'lapak_id' => $lapak->id,
        ]);

        $productB = Product::factory()->create([
            'title' => 'Produk B',
            'category_id' => $categoryB->id,
            'lapak_id' => $lapak->id,
        ]);

        ProductScheduleService::rebuild($productA->id);
        ProductScheduleService::rebuild($productB->id);

        $response = $this->get(route('products.index', [
            'category' => $categoryA->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('Produk A')
            ->assertDontSee('Produk B');
    });

    it('tidak menampilkan produk dari lapak non aktif', function () {
        $lapak = LapakProfile::factory()->create([
            'is_active' => false,
        ]);

        $product = Product::factory()->create([
            'title' => 'Produk Non Aktif',
            'lapak_id' => $lapak->id,
        ]);

        ProductScheduleService::rebuild($product->id);
        $response = $this->get(route('products.index'));

        $response
            ->assertOk()
            ->assertDontSee('Produk Non Aktif');
    });

    it('menggunakan cache untuk categories', function () {
        Cache::forget('categories_list');

        $category = Category::factory()->create();

        $lapak = LapakProfile::factory()->create();

        Product::factory()->create([
            'category_id' => $category->id,
            'lapak_id' => $lapak->id,
            'created_at' => now()->subHours(10),
        ]);

        ProductScheduleService::rebuild($lapak->id);

        $response = $this->get(route('products.index'));

        $response
            ->assertOk()
            ->assertViewHas('categories');

        expect(Cache::has('categories_list'))->toBeTrue();
    });
});

describe('ProductController@show', function () {
    it('menampilkan detail produk aktif', function () {
        Setting::factory()->create([
            'key' => 'site_title',
            'value' => 'Lapak Test',
        ]);

        $category = Category::factory()->create([
            'category_name' => 'Elektronik',
        ]);

        $lapak = LapakProfile::factory()->create([
            'name' => 'Toko Saya',
        ]);

        $product = Product::factory()->create([
            'title' => 'Laptop Gaming',
            'category_id' => $category->id,
            'lapak_id' => $lapak->id,
            'description' => 'Deskripsi produk',
        ]);

        $response = $this->get(route('product.show', $product));

        $response
            ->assertOk()
            ->assertViewIs('product-detail')
            ->assertViewHas('product')
            ->assertSee('Laptop Gaming');
    });

    it('mengembalikan 404 jika produk tidak aktif', function () {
        $lapak = LapakProfile::factory()->create();

        $product = Product::factory()->create([
            'lapak_id' => $lapak->id,
            'is_active' => false,
        ]);

        $this->get(route('product.show', $product))
            ->assertNotFound();
    });

    it('mengembalikan 404 jika lapak tidak aktif', function () {
        $lapak = LapakProfile::factory()->create([
            'is_active' => false,
        ]);

        $product = Product::factory()->create([
            'lapak_id' => $lapak->id,
        ]);

        $this->get(route('product.show', $product))
            ->assertNotFound();
    });

    it('set hasReported true jika user pernah report produk', function () {
        $user = User::factory()->create();

        $lapak = LapakProfile::factory()->create();

        $product = Product::factory()->create([
            'lapak_id' => $lapak->id,
        ]);

        $product->reports()->create([
            'user_id' => $user->id,
            'reason' => 'Spam',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('product.show', $product));

        $response
            ->assertOk()
            ->assertViewHas('hasReported', true);
    });

    it('menampilkan produk lain dalam lapak yang sama', function () {
        $lapak = LapakProfile::factory()->create();

        $mainProduct = Product::factory()->create([
            'title' => 'Produk Utama',
            'lapak_id' => $lapak->id,
        ]);

        $otherProduct = Product::factory()->create([
            'title' => 'Produk Lain',
            'lapak_id' => $lapak->id,
        ]);

        Product::factory()->create([
            'title' => 'Produk Non Aktif',
            'lapak_id' => $lapak->id,
            'is_active' => false,
        ]);

        $response = $this->get(route('product.show', $mainProduct));

        $response
            ->assertOk()
            ->assertSee('Produk Lain')
            ->assertDontSee('Produk Non Aktif');
    });
});
