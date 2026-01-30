<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LapakController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Auth\PublicAuthController;

Route::get('/', [ProductController::class, 'index'])->name('products.index');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/lapak/{lapak}', [LapakController::class, 'show'])->name('lapak.show');

Route::post('/login', [PublicAuthController::class, 'login'])->name('login.public');
Route::post('/logout', [PublicAuthController::class, 'logout'])->name('logout.public');
Route::post('/register', [PublicAuthController::class, 'register'])->name('register.public');
