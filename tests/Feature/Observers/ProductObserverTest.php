<?php

use Illuminate\Support\Facades\Cache;

it('invalidates eligible cache when product is created', function () {
    $user = makeUser();
    $lapak = $user->lapak()->firstOrFail();

    Cache::put('products.schedule.eligible_ids', ['stale'], 60);

    makeProduct($lapak);

    expect(Cache::has('products.schedule.eligible_ids'))->toBeFalse();
});

it('invalidates eligible cache when product is deleted', function () {
    $user = makeUser();
    $lapak = $user->lapak()->firstOrFail();
    $product = makeProduct($lapak);

    Cache::put('products.schedule.eligible_ids', ['stale'], 60);

    $product->delete();

    expect(Cache::has('products.schedule.eligible_ids'))->toBeFalse();
});

it('invalidates eligible cache when product is updated with meaningful changes', function () {
    $user = makeUser();
    $lapak = $user->lapak()->firstOrFail();
    $product = makeProduct($lapak);

    Cache::put('products.schedule.eligible_ids', ['stale'], 60);

    $product->update([
        'title' => 'Judul Produk Diperbarui',
    ]);

    expect(Cache::has('products.schedule.eligible_ids'))->toBeFalse();
});

it('does not invalidate eligible cache when only updated_at changes', function () {
    $user = makeUser();
    $lapak = $user->lapak()->firstOrFail();
    $product = makeProduct($lapak);

    Cache::put('products.schedule.eligible_ids', ['stale'], 60);

    $product->touch();

    expect(Cache::has('products.schedule.eligible_ids'))->toBeTrue();
});
