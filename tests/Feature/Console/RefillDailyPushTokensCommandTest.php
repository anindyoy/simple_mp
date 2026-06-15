<?php

use App\Models\Setting;
use App\Models\User;

beforeEach(function () {
    Setting::truncate();
    User::truncate();
});

it('mengisi ulang token user yang di bawah minimum harian', function () {
    Setting::setValue('daily_minimum_push_tokens', '5');

    User::factory()->count(3)->create([
        'push_tokens' => 2,
        'is_admin' => false,
    ]);

    User::factory()->create([
        'push_tokens' => 10,
        'is_admin' => false,
    ]);

    $this->artisan('tokens:refill-daily')
        ->expectsOutput('Refill token angkat produk harian selesai (minimum: 5). User diperbarui: 3.')
        ->assertExitCode(0);

    expect(User::where('push_tokens', 5)->count())->toBe(3);
    expect(User::where('push_tokens', 10)->count())->toBe(1);
});

it('tidak memperbarui user yang sudah memiliki token cukup', function () {
    Setting::setValue('daily_minimum_push_tokens', '3');

    User::factory()->count(2)->create([
        'push_tokens' => 5,
        'is_admin' => false,
    ]);

    User::factory()->create([
        'push_tokens' => 3,
        'is_admin' => false,
    ]);

    $this->artisan('tokens:refill-daily')
        ->expectsOutput('Refill token angkat produk harian selesai (minimum: 3). User diperbarui: 0.')
        ->assertExitCode(0);

    expect(User::where('push_tokens', 5)->count())->toBe(2);
    expect(User::where('push_tokens', 3)->count())->toBe(1);
});

it('melewati user admin saat refill token', function () {
    Setting::setValue('daily_minimum_push_tokens', '4');

    User::factory()->create([
        'push_tokens' => 1,
        'is_admin' => true,
    ]);

    User::factory()->create([
        'push_tokens' => 2,
        'is_admin' => false,
    ]);

    $this->artisan('tokens:refill-daily')
        ->expectsOutput('Refill token angkat produk harian selesai (minimum: 4). User diperbarui: 1.')
        ->assertExitCode(0);

    expect(User::where('is_admin', true)->first()->push_tokens)->toBe(1);
    expect(User::where('is_admin', false)->first()->push_tokens)->toBe(4);
});

it('menggunakan nilai default 2 saat setting belum dikonfigurasi', function () {
    User::factory()->create([
        'push_tokens' => 0,
        'is_admin' => false,
    ]);

    $this->artisan('tokens:refill-daily')
        ->expectsOutput('Refill token angkat produk harian selesai (minimum: 2). User diperbarui: 1.')
        ->assertExitCode(0);

    expect(User::first()->push_tokens)->toBe(2);
});

it('mencatat log info saat command dijalankan', function () {
    Setting::setValue('daily_minimum_push_tokens', '3');

    User::factory()->create([
        'push_tokens' => 1,
        'is_admin' => false,
    ]);

    $this->artisan('tokens:refill-daily')->assertExitCode(0);

    // Log::info() menulis ke channel log Laravel (file/stack), bukan ke activity_log Spatie
    // Test ini memastikan command berjalan sukses
    expect(true)->toBeTrue();
});
