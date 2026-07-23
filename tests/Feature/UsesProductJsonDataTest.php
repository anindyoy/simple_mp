<?php

declare(strict_types=1);

use App\Traits\UsesProductJsonData;

/**
 * Helper class untuk mengaktifkan trait agar bisa di-test.
 */
class UsesProductJsonDataTestHelper
{
    use UsesProductJsonData;

    public function callLoadJsonData(): void
    {
        $this->loadJsonData();
    }

    public function callMakeProductTitle(string $categoryName): array
    {
        return $this->makeProductTitle($categoryName);
    }

    public function callGeneratePrice(string $categoryName): int
    {
        return $this->generatePrice($categoryName);
    }
}

test('loadJsonData memuat data dari file item_produk.json tanpa error', function () {
    $helper = new UsesProductJsonDataTestHelper();

    $helper->callLoadJsonData();

    // Tidak throws exception — artinya sukses
    expect(true)->toBeTrue();
});

test('makeProductTitle menghasilkan title dan slug untuk kategori yang dikenal', function (string $category) {
    $helper = new UsesProductJsonDataTestHelper();
    $helper->callLoadJsonData();

    [$title, $slug] = $helper->callMakeProductTitle($category);

    expect($title)->toBeString()->not->toBeEmpty();
    expect($slug)->toBeString()->not->toBeEmpty();
    expect(str_contains($slug, '-'))->toBeTrue();
})->with([
    'Makanan',
    'Minuman',
    'Pakaian',
    'Buah & Sayuran',
    'Elektronik',
    'Gadget',
    'Jasa',
    'Lainnya',
    'Makanan',
    'Minuman',
    'Otomotif',
    'Properti',
    'Suplemen',
]);

test('makeProductTitle menghasilkan title dari fake()->words untuk kategori yang tidak dikenal', function () {
    $helper = new UsesProductJsonDataTestHelper();
    $helper->callLoadJsonData();

    [$title, $slug] = $helper->callMakeProductTitle('KategoriTidakDikenalXYZ');

    expect($title)->toBeString()->not->toBeEmpty();
    expect($slug)->toBeString()->not->toBeEmpty();
    expect(str_contains($slug, '-'))->toBeTrue();
});

test('generatePrice mengembalikan integer dalam range yang wajar', function (string $category, int $expectedMin, int $expectedMax) {
    $helper = new UsesProductJsonDataTestHelper();
    $helper->callLoadJsonData();

    $price = $helper->callGeneratePrice($category);

    expect($price)->toBeInt();
    expect($price)->toBeGreaterThanOrEqual($expectedMin);
    expect($price)->toBeLessThanOrEqual($expectedMax);
})->with([
    ['Makanan', 5_000, 75_000],
    ['Minuman', 3_000, 35_000],
    ['Pakaian', 25_000, 450_000],
]);

test('generatePrice menggunakan range default untuk kategori yang tidak dikenal', function () {
    $helper = new UsesProductJsonDataTestHelper();
    $helper->callLoadJsonData();

    $price = $helper->callGeneratePrice('KategoriTidakDikenalXYZ');

    expect($price)->toBeInt();
    expect($price)->toBeGreaterThanOrEqual(10_000);
    expect($price)->toBeLessThanOrEqual(2_000_000);
});