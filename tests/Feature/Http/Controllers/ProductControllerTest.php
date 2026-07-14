<?php

use App\Models\User;
use App\Models\Product;
use App\Models\Setting;
use App\Models\Category;
use App\Models\LapakProfile;
use App\Livewire\ProductCatalog;
use Livewire\Livewire;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use App\Services\ProductScheduleService;

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

        Product::factory()->withoutImages()->create([
            'title' => 'iPhone 15',
            'category_id' => $category->id,
            'lapak_id' => $lapak->id,
            'created_at' => now()->subSeconds(5),
        ]);

        Product::factory()->withoutImages()->create([
            'title' => 'Samsung Galaxy',
            'category_id' => $category->id,
            'lapak_id' => $lapak->id,
            'created_at' => now()->subSeconds(2),
        ]);

        ProductScheduleService::rebuild($lapak->id);

        $titles = Livewire::test(ProductCatalog::class)
            ->viewData('products')
            ->pluck('title');

        expect($titles)->toContain('iPhone 15');
        expect($titles)->not->toContain('Samsung Galaxy');
    });

    it('dapat filter berdasarkan search', function () {
        $lapak = LapakProfile::factory()->create();

        Product::factory()->withoutImages()->create([
            'title' => 'Macbook Pro',
            'lapak_id' => $lapak->id,
            'pushed_at' => null,
            'created_at' => now()->subHours(9),
        ]);

        Product::factory()->withoutImages()->create([
            'title' => 'Asus ROG',
            'lapak_id' => $lapak->id,
            'pushed_at' => null,
            'created_at' => now()->subHours(5),
        ]);

        ProductScheduleService::rebuild($lapak->id);

        $titles = Livewire::test(ProductCatalog::class)
            ->set('search', 'Macbook')
            ->viewData('products')
            ->pluck('title');

        expect($titles)->toContain('Macbook Pro');
        expect($titles)->not->toContain('Asus ROG');
    });

    it('dapat filter berdasarkan kategori', function () {
        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();
        $lapak = LapakProfile::factory()->create();

        Product::factory()->withoutImages()->create([
            'title' => 'Produk A',
            'category_id' => $categoryA->id,
            'lapak_id' => $lapak->id,
            'pushed_at' => null,
            'created_at' => now()->subHours(9),
        ]);

        Product::factory()->withoutImages()->create([
            'title' => 'Produk B',
            'category_id' => $categoryB->id,
            'lapak_id' => $lapak->id,
            'pushed_at' => null,
            'created_at' => now()->subHours(5),
        ]);

        ProductScheduleService::rebuild($lapak->id);

        $titles = Livewire::test(ProductCatalog::class)
            ->set('categoryId', (string) $categoryA->id)
            ->viewData('products')
            ->pluck('title');

        expect($titles)->toContain('Produk A');
        expect($titles)->not->toContain('Produk B');
    });

    it('tidak menampilkan produk dari lapak non aktif', function () {
        $lapak = LapakProfile::factory()->create(['is_active' => false]);

        Product::factory()->withoutImages()->create([
            'title' => 'Produk Non Aktif',
            'lapak_id' => $lapak->id,
            'pushed_at' => null,
        ]);

        ProductScheduleService::rebuild($lapak->id);

        $titles = Livewire::test(ProductCatalog::class)
            ->viewData('products')
            ->pluck('title');

        expect($titles)->not->toContain('Produk Non Aktif');
    });

    it('menggunakan cache untuk categories', function () {
        Cache::forget('categories_list');

        $category = Category::factory()->create();
        $lapak = LapakProfile::factory()->create();

        Product::factory()->withoutImages()->create([
            'category_id' => $category->id,
            'lapak_id' => $lapak->id,
            'created_at' => now()->subHours(10),
        ]);

        ProductScheduleService::rebuild($lapak->id);

        Livewire::test(ProductCatalog::class);

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

        $product = Product::factory()->withoutImages()->create([
            'title' => 'Laptop Gaming',
            'category_id' => $category->id,
            'lapak_id' => $lapak->id,
            'description' => 'Deskripsi produk',
        ]);

        $response = $this->get(route('product.show', $product));

        $response
            ->assertOk()
            ->assertViewIs('product-detail')
            ->assertViewHas('product', fn($p) => $p->title === 'Laptop Gaming');
    });

    it('mengembalikan 404 jika produk tidak aktif', function () {
        $lapak = LapakProfile::factory()->create();

        $product = Product::factory()->withoutImages()->create([
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

        $product = Product::factory()->withoutImages()->create([
            'lapak_id' => $lapak->id,
        ]);

        $this->get(route('product.show', $product))
            ->assertNotFound();
    });

    it('set hasReported true jika user pernah report produk', function () {
        $user = User::factory()->create();

        $lapak = LapakProfile::factory()->create();

        $product = Product::factory()->withoutImages()->create([
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

        $mainProduct = Product::factory()->withoutImages()->create([
            'title' => 'Produk Utama',
            'lapak_id' => $lapak->id,
        ]);

        Product::factory()->withoutImages()->create([
            'title' => 'Produk Lain',
            'lapak_id' => $lapak->id,
        ]);

        Product::factory()->withoutImages()->create([
            'title' => 'Produk Non Aktif',
            'lapak_id' => $lapak->id,
            'is_active' => false,
        ]);

        $response = $this->get(route('product.show', $mainProduct));

        $otherTitles = $response->viewData('otherProductsInLapak')->pluck('title');

        expect($otherTitles)->toContain('Produk Lain');
        expect($otherTitles)->not->toContain('Produk Non Aktif');
    });

    it('menambah views_count saat pertama kali diakses', function () {
        $lapak = LapakProfile::factory()->create();

        $product = Product::factory()->withoutImages()->create([
            'lapak_id' => $lapak->id,
            'views_count' => 0,
        ]);

        $this->get(route('product.show', $product))->assertOk();

        expect($product->fresh()->views_count)->toBe(1);
    });

    it('tidak menambah views_count jika direload dari IP yang sama dalam jeda waktu', function () {
        $lapak = LapakProfile::factory()->create();

        $product = Product::factory()->withoutImages()->create([
            'lapak_id' => $lapak->id,
            'views_count' => 0,
        ]);

        $this->get(route('product.show', $product))->assertOk();
        $this->get(route('product.show', $product))->assertOk();
        $this->get(route('product.show', $product))->assertOk();

        expect($product->fresh()->views_count)->toBe(1);
    });

    it('menambah views_count lagi setelah melewati jeda waktu (default 6 jam)', function () {
        $lapak = LapakProfile::factory()->create();

        $product = Product::factory()->withoutImages()->create([
            'lapak_id' => $lapak->id,
            'views_count' => 0,
        ]);

        $this->get(route('product.show', $product))->assertOk();

        $this->travel(7)->hours();

        $this->get(route('product.show', $product))->assertOk();

        expect($product->fresh()->views_count)->toBe(2);
    });

    it('menghormati durasi jeda kustom dari setting product_view_guard_hours', function () {
        Setting::factory()->create([
            'key' => 'product_view_guard_hours',
            'value' => '1',
        ]);

        $lapak = LapakProfile::factory()->create();

        $product = Product::factory()->withoutImages()->create([
            'lapak_id' => $lapak->id,
            'views_count' => 0,
        ]);

        $this->get(route('product.show', $product))->assertOk();

        $this->travel(30)->minutes();
        $this->get(route('product.show', $product))->assertOk();
        expect($product->fresh()->views_count)->toBe(1);

        $this->travel(31)->minutes();
        $this->get(route('product.show', $product))->assertOk();
        expect($product->fresh()->views_count)->toBe(2);
    });

    it('menghitung views_count terpisah untuk IP yang berbeda', function () {
        $lapak = LapakProfile::factory()->create();

        $product = Product::factory()->withoutImages()->create([
            'lapak_id' => $lapak->id,
            'views_count' => 0,
        ]);

        $this->call('GET', route('product.show', $product), server: ['REMOTE_ADDR' => '10.0.0.1'])
            ->assertOk();
        $this->call('GET', route('product.show', $product), server: ['REMOTE_ADDR' => '10.0.0.2'])
            ->assertOk();

        expect($product->fresh()->views_count)->toBe(2);
    });

    it('tidak menambah views_count untuk produk yang 404', function () {
        $lapak = LapakProfile::factory()->create();

        $product = Product::factory()->withoutImages()->create([
            'lapak_id' => $lapak->id,
            'is_active' => false,
            'views_count' => 0,
        ]);

        $this->get(route('product.show', $product))->assertNotFound();

        expect($product->fresh()->views_count)->toBe(0);
    });
});
