<?php

use App\Models\LapakProfile;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->lapak = LapakProfile::factory()->create([
        'name' => 'Lapak Test',
        'profile_image' => 'https://example.com/foto.jpg',
    ]);
});

test('dapat menampilkan halaman detail lapak', function () {
    $response = $this->get(route('lapak.show', $this->lapak));

    $response->assertStatus(200);
    $response->assertViewIs('lapak.show');
});

test('dapat memuat data lapak dengan benar', function () {
    $response = $this->get(route('lapak.show', $this->lapak));

    $response->assertViewHas('lapak', function ($lapak) {
        return $lapak->id === $this->lapak->id;
    });
});

test('hanya menampilkan produk yang aktif', function () {
    // Produk aktif
    $productAktif = Product::factory()->create([
        'lapak_profile_id' => $this->lapak->id,
        'is_active' => true,
        'pushed_at' => now(),
    ]);

    // Produk tidak aktif
    $productTidakAktif = Product::factory()->create([
        'lapak_profile_id' => $this->lapak->id,
        'is_active' => false,
        'pushed_at' => now(),
    ]);

    $response = $this->get(route('lapak.show', $this->lapak));

    $response->assertViewHas('lapak', function ($lapak) use ($productAktif, $productTidakAktif) {
        return $lapak->products->contains($productAktif)
            && !$lapak->products->contains($productTidakAktif);
    });
});

test('mengurutkan produk berdasarkan pushed_at terbaru', function () {
    $product1 = Product::factory()->create([
        'lapak_profile_id' => $this->lapak->id,
        'is_active' => true,
        'pushed_at' => now()->subDays(2),
    ]);

    $product2 = Product::factory()->create([
        'lapak_profile_id' => $this->lapak->id,
        'is_active' => true,
        'pushed_at' => now()->subDay(),
    ]);

    $product3 = Product::factory()->create([
        'lapak_profile_id' => $this->lapak->id,
        'is_active' => true,
        'pushed_at' => now(),
    ]);

    $response = $this->get(route('lapak.show', $this->lapak));

    $response->assertViewHas('lapak', function ($lapak) use ($product1, $product2, $product3) {
        $products = $lapak->products;
        return $products[0]->id === $product3->id
            && $products[1]->id === $product2->id
            && $products[2]->id === $product1->id;
    });
});

test('memuat relasi images dari produk', function () {
    $product = Product::factory()->create([
        'lapak_profile_id' => $this->lapak->id,
        'is_active' => true,
    ]);

    ProductImage::factory()->count(3)->create([
        'product_id' => $product->id,
    ]);

    $response = $this->get(route('lapak.show', $this->lapak));

    $response->assertViewHas('lapak', function ($lapak) use ($product) {
        return $lapak->products->first()->relationLoaded('images')
            && $lapak->products->first()->images->count() === 3;
    });
});

test('memuat relasi category dari produk', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->create([
        'lapak_profile_id' => $this->lapak->id,
        'category_id' => $category->id,
        'is_active' => true,
    ]);

    $response = $this->get(route('lapak.show', $this->lapak));

    $response->assertViewHas('lapak', function ($lapak) use ($category) {
        return $lapak->products->first()->relationLoaded('category')
            && $lapak->products->first()->category->id === $category->id;
    });
});

test('menampilkan meta title dengan benar', function () {
    $response = $this->get(route('lapak.show', $this->lapak));

    $response->assertViewHas('meta', function ($meta) {
        return $meta['title'] === 'Lapak Test | Lapak Cimanglid';
    });
});

test('menampilkan meta description dengan benar', function () {
    $response = $this->get(route('lapak.show', $this->lapak));

    $response->assertViewHas('meta', function ($meta) {
        return $meta['description'] === 'Lapak Lapak Test di marketplace warga Cimanglid. Lihat produk & hubungi penjual langsung.';
    });
});

test('menampilkan meta keywords dengan benar', function () {
    $response = $this->get(route('lapak.show', $this->lapak));

    $response->assertViewHas('meta', function ($meta) {
        return $meta['keywords'] === 'lapak cimanglid, Lapak Test, jual beli warga';
    });
});

test('menampilkan meta image dengan benar', function () {
    $response = $this->get(route('lapak.show', $this->lapak));

    $response->assertViewHas('meta', function ($meta) {
        return $meta['image'] === 'https://example.com/foto.jpg';
    });
});

test('mengembalikan 404 jika lapak tidak ditemukan', function () {
    $response = $this->get(route('lapak.show', 99999));

    $response->assertStatus(404);
});