<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LapakController;
use App\Http\Controllers\ProductController;

Route::get('/', [ProductController::class, 'index'])->name('home');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/lapak/{lapak}', [LapakController::class, 'show'])->name('lapak.show');
