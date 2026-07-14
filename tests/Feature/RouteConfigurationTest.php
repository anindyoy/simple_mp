<?php

use Illuminate\Support\Facades\Route;
use Spatie\ResponseCache\Middlewares\DoNotCacheResponse;

it('route product.show tidak boleh di-cache oleh response cache karena menghitung views_count tiap request', function () {
    $route = Route::getRoutes()->getByName('product.show');

    expect($route)->not->toBeNull();
    expect($route->gatherMiddleware())->toContain(DoNotCacheResponse::class);
});
