<?php

use App\Models\Category;
use App\Models\LapakProfile;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\User;
use App\Models\VisitorStats;

it('mencatat dan menampilkan pesan sukses saat data pengunjung harian direkam', function () {
    $lapak = LapakProfile::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    $product = Product::factory()->withoutImages()->create([
        'lapak_id' => $lapak->id,
        'category_id' => Category::factory()->create()->id,
    ]);

    ProductView::create([
        'product_id' => $product->id,
        'ip_address' => '10.0.0.1',
        'expires_at' => now()->addHour(),
    ]);

    $this->artisan('visitors:record-daily')
        ->expectsOutput('Data pengunjung harian berhasil dicatat.')
        ->assertExitCode(0);

    $record = VisitorStats::where('date', today()->toDateString())->first();
    expect($record)->not->toBeNull()
        ->and($record->visitor_count)->toBe(1);
});