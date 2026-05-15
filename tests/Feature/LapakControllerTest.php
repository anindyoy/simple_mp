<?php

use App\Models\Product;
use App\Models\Category;
use App\Models\LapakProfile;
use App\Http\Controllers\LapakController;
use Illuminate\View\View;

beforeEach(function () {
    $this->lapak = LapakProfile::factory()->create([
        'name' => 'Lapak Test',
        'profile_image' => 'https://example.com/foto.jpg',
    ]);
});

function getLapakView(LapakProfile $lapak): View
{
    return app(LapakController::class)->show($lapak);
}

test('dapat menampilkan halaman detail lapak', function () {
    $view = getLapakView($this->lapak);

    expect($view->name())
        ->toBe('lapak.show');
});

test('dapat memuat data lapak dengan benar', function () {
    $view = getLapakView($this->lapak);

    $lapak = $view->getData()['lapak'];

    expect($lapak->id)
        ->toBe($this->lapak->id);
});

test('hanya menampilkan produk yang aktif', function () {
    $productAktif = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => true,
    ]);

    $productTidakAktif = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => false,
    ]);

    $view = getLapakView($this->lapak);

    $lapak = $view->getData()['lapak'];

    expect($lapak->products->contains($productAktif))
        ->toBeTrue();

    expect($lapak->products->contains($productTidakAktif))
        ->toBeFalse();
});

test('mengurutkan produk berdasarkan pushed_at terbaru', function () {
    $product1 = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => true,
        'pushed_at' => now()->subDays(2),
    ]);

    $product2 = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => true,
        'pushed_at' => now()->subDay(),
    ]);

    $product3 = Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'is_active' => true,
        'pushed_at' => now(),
    ]);

    $view = getLapakView($this->lapak);

    $products = $view->getData()['lapak']
        ->products
        ->values();

    expect($products[0]->id)->toBe($product3->id);
    expect($products[1]->id)->toBe($product2->id);
    expect($products[2]->id)->toBe($product1->id);
});

test('memuat relasi category dari produk', function () {
    $category = Category::factory()->create();

    Product::factory()->create([
        'lapak_id' => $this->lapak->id,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $view = getLapakView($this->lapak);

    $product = $view->getData()['lapak']
        ->products
        ->first();

    expect($product->relationLoaded('category'))
        ->toBeTrue();

    expect($product->category->id)
        ->toBe($category->id);
});

test('menampilkan meta title dengan benar', function () {
    $view = getLapakView($this->lapak);

    $meta = $view->getData()['meta'];

    expect($meta['title'])
        ->toBe('Lapak Test | Lapak Cimanglid');
});

test('menampilkan meta description dengan benar', function () {
    $view = getLapakView($this->lapak);

    $meta = $view->getData()['meta'];

    expect($meta['description'])
        ->toBe('Lapak Lapak Test di marketplace warga Cimanglid. Lihat produk & hubungi penjual langsung.');
});

test('menampilkan meta keywords dengan benar', function () {
    $view = getLapakView($this->lapak);

    $meta = $view->getData()['meta'];

    expect($meta['keywords'])
        ->toBe('lapak cimanglid, Lapak Test, jual beli warga');
});

test('menampilkan meta image dengan benar', function () {
    $view = getLapakView($this->lapak);

    $meta = $view->getData()['meta'];

    expect($meta['image'])
        ->toBe('https://example.com/foto.jpg');
});

test('mengembalikan 404 jika lapak tidak aktif', function () {
    $lapak = LapakProfile::factory()->create([
        'is_active' => false,
    ]);

    expect(fn () => getLapakView($lapak))
        ->toThrow(Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);
});