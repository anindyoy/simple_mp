<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\LapakController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserRulesController;
use App\Http\Controllers\UserTokenController;
use App\Http\Controllers\Auth\PublicAuthController;

Route::get('/', [ProductController::class, 'index'])->name('products.index');
Route::get('/peraturan-pengguna', [UserRulesController::class, 'index'])->name('rules.index');
Route::get('/product/{product:slug}', [ProductController::class, 'show'])->name('product.show');
Route::get('/lapak/{lapak}', [LapakController::class, 'show'])->name('lapak.show');
Route::get('/email/verify', [PublicAuthController::class, 'showVerificationNotice'])->name('verification.notice');

Route::post('/login', [PublicAuthController::class, 'login'])->name('login.public');
Route::post('/logout', [PublicAuthController::class, 'logout'])->name('logout.public');
Route::post('/register', [PublicAuthController::class, 'register'])->name('register.public');
Route::post('/turnstile/client-error', [PublicAuthController::class, 'logTurnstileClientError'])
    ->middleware('throttle:30,1')
    ->name('turnstile.client-error');
Route::post('/email/verification-notification', [PublicAuthController::class, 'resendVerificationEmail'])
    ->middleware('throttle:6,1')
    ->name('verification.send');
Route::get('/email/verify/{id}/{hash}', [PublicAuthController::class, 'verifyEmail'])
    ->middleware(['signed', 'throttle:6,1'])
    ->name('verification.verify');
Route::post('/report', [ReportController::class, 'store'])->name('report.store');

Route::middleware('auth')->get('/debug/telegram-exception', function () {
    throw new Exception('Simulasi exception dari route debug Telegram');
})->name('debug.telegram-exception');

Route::middleware(['auth', 'throttle:10,1'])->get('/debug/telegram-test', function () {
    try {
        $message = 'Test log Telegram dari debug route '.now()->toDateTimeString();
        Log::channel('telegram')->error($message);

        return response()->json([
            'ok' => true,
            'message' => 'Log Telegram berhasil dikirim.',
            'payload' => $message,
        ]);
    } catch (\Throwable $exception) {
        return response()->json([
            'ok' => false,
            'message' => 'Gagal mengirim log Telegram.',
            'error' => $exception->getMessage(),
        ], 500);
    }
})->name('debug.telegram-test');

// Token purchase routes (requires authentication)
Route::middleware('auth')->group(function () {
    Route::controller(UserTokenController::class)->group(function () {
        // Token purchase
        Route::get('/tokens/purchase', 'showPurchase')->name('tokens.purchase');
        Route::post('/tokens/purchase', 'storePurchase')->name('tokens.store-purchase');
        Route::get('/tokens/purchase/{tokenPurchase}', 'showPurchaseDetails')->name('tokens.show-purchase');
        Route::post('/tokens/purchase/{tokenPurchase}/proof', 'uploadProof')->name('tokens.upload-proof');

        // History
        Route::get('/tokens/history', 'history')->name('tokens.history');
    });
});
