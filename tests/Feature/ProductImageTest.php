<?php

use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Str;
use App\Models\LapakProfile;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create();

    $this->lapak = LapakProfile::factory()->create([
        'user_id' => $this->user->id,
    ]);

    $this->actingAs($this->user);

    // siapkan file dummy sebagai source
    Storage::disk('public')->put('seed-samples/sample.jpg', 'dummy');
});

it('creates image with unique randomized filename', function () {
    $image1 = ProductImage::factory()->create();
    $image2 = ProductImage::factory()->create();

    expect($image1->image_url)->not->toBe($image2->image_url);

    Storage::disk('public')->assertExists($image1->image_url);
    Storage::disk('public')->assertExists($image2->image_url);
});

it('stores copied image file in storage', function () {
    $image = ProductImage::factory()->create();

    Storage::disk('public')->assertExists($image->image_url);
});

it('deletes file when product image is deleted', function () {
    $image = ProductImage::factory()->create();

    $path = $image->image_url;

    // pastikan file ada
    Storage::disk('public')->assertExists($path);

    // delete model
    $image->delete();

    // file harus ikut hilang
    Storage::disk('public')->assertMissing($path);
});

it('deletes all image files when product is deleted', function () {
    $product = Product::factory()
        ->has(ProductImage::factory()->count(3), 'images')
        ->create();

    $paths = $product->images->pluck('image_url');

    foreach ($paths as $path) {
        Storage::disk('public')->assertExists($path);
    }

    $product->delete();

    foreach ($paths as $path) {
        Storage::disk('public')->assertMissing($path);
    }
});
