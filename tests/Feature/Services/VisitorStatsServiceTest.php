<?php

use App\Models\Category;
use App\Models\LapakProfile;
use App\Models\Product;
use App\Models\ProductView;
use App\Models\User;
use App\Models\VisitorStats;
use App\Services\VisitorStatsService;
use Illuminate\Support\Facades\Cache;

function makeVisitorProductView(array $attributes = []): ProductView
{
    $lapak = LapakProfile::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    $product = Product::factory()->withoutImages()->create([
        'lapak_id' => $lapak->id,
        'category_id' => Category::factory()->create()->id,
    ]);

    return ProductView::create(array_merge([
        'product_id' => $product->id,
        'ip_address' => '127.0.0.1',
        'expires_at' => now()->addHour(),
    ], $attributes));
}

beforeEach(function () {
    Cache::flush();
});

it('menghitung jumlah ip unik yang mengunjungi dalam 24 jam terakhir', function () {
    makeVisitorProductView(['ip_address' => '10.0.0.1', 'created_at' => now()->subHours(1)]);
    makeVisitorProductView(['ip_address' => '10.0.0.1', 'created_at' => now()->subHours(2)]);
    makeVisitorProductView(['ip_address' => '10.0.0.2', 'created_at' => now()->subHours(10)]);

    expect(VisitorStatsService::calculate())->toBe(2);
});

it('mengabaikan kunjungan yang lebih tua dari 24 jam', function () {
    makeVisitorProductView(['ip_address' => '10.0.0.1', 'created_at' => now()->subHours(25)]);
    makeVisitorProductView(['ip_address' => '10.0.0.2', 'created_at' => now()->subHours(1)]);

    expect(VisitorStatsService::calculate())->toBe(1);
});

it('menyimpan hasil perhitungan ke cache', function () {
    makeVisitorProductView(['ip_address' => '10.0.0.1']);

    VisitorStatsService::calculate();

    expect(Cache::get('stats.visitors_24h'))->toBe(1);
});

it('mengambil dari cache tanpa menghitung ulang jika sudah tersedia', function () {
    Cache::put('stats.visitors_24h', 999, now()->addHour());

    expect(VisitorStatsService::getCached())->toBe(999);
});

it('menghitung otomatis saat cache kosong', function () {
    makeVisitorProductView(['ip_address' => '10.0.0.1']);

    expect(VisitorStatsService::getCached())->toBe(1);
});

it('mencatat jumlah pengunjung unik hari ini ke tabel visitor_stats', function () {
    makeVisitorProductView(['ip_address' => '10.0.0.1', 'created_at' => now()->subHour()]);
    makeVisitorProductView(['ip_address' => '10.0.0.1', 'created_at' => now()->subMinutes(30)]);
    makeVisitorProductView(['ip_address' => '10.0.0.2', 'created_at' => now()->subMinutes(15)]);

    VisitorStatsService::recordDaily();

    $record = VisitorStats::where('date', today()->toDateString())->first();

    expect($record)->not->toBeNull()
        ->and($record->visitor_count)->toBe(2);
});

it('memperbarui data jika sudah ada catatan untuk hari ini', function () {
    VisitorStats::create(['date' => today()->toDateString(), 'visitor_count' => 0]);

    makeVisitorProductView(['ip_address' => '10.0.0.1']);

    VisitorStatsService::recordDaily();

    $record = VisitorStats::where('date', today()->toDateString())->first();

    expect($record->visitor_count)->toBe(1);
});

it('hanya menghitung kunjungan hari ini untuk recordDaily', function () {
    makeVisitorProductView(['ip_address' => '10.0.0.1', 'created_at' => now()->subDay()->subHour()]);
    makeVisitorProductView(['ip_address' => '10.0.0.2', 'created_at' => now()->subHour()]);

    VisitorStatsService::recordDaily();

    $record = VisitorStats::where('date', today()->toDateString())->first();

    expect($record->visitor_count)->toBe(1);
});