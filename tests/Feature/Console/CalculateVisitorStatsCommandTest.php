<?php

use App\Models\Category;
use App\Models\LapakProfile;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::flush();
});

it('menghitung dan menampilkan jumlah pengunjung unik 24 jam terakhir', function () {
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

    $this->artisan('visitors:calculate-24h')
        ->expectsOutput('Pengunjung unik 24 jam terakhir: 1.')
        ->assertExitCode(0);

    expect(Cache::get('stats.visitors_24h'))->toBe(1);
});
