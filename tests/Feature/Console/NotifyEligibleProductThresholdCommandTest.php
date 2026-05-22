<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();
    Http::preventStrayRequests();
});

it('menampilkan info saat jumlah eligible masih aman', function () {
    Cache::put('products.schedule.eligible_ids', collect(range(1, 900)), 60);

    $this->artisan('products:notify-eligible-threshold')
        ->expectsOutput('Jumlah eligible produk aman: 900.')
        ->assertExitCode(0);

    Http::assertNothingSent();
});

it('tidak mengirim notifikasi jika sudah pernah dikirim dalam 24 jam', function () {
    Cache::put('products.schedule.eligible_ids', collect(range(1, 901)), 60);
    Cache::put('products.eligible_threshold_notified', true, now()->addDay());

    $this->artisan('products:notify-eligible-threshold')
        ->expectsOutput('Notifikasi ambang batas sudah dikirim dalam 24 jam terakhir.')
        ->assertExitCode(0);

    Http::assertNothingSent();
});

it('melewati kirim notifikasi saat konfigurasi telegram belum lengkap', function () {
    Cache::put('products.schedule.eligible_ids', collect(range(1, 901)), 60);

    config()->set('services.telegram.bot_token', null);
    config()->set('services.telegram.chat_id', null);

    $this->artisan('products:notify-eligible-threshold')
        ->expectsOutput('Konfigurasi Telegram belum lengkap, notifikasi dilewati.')
        ->assertExitCode(0);

    Http::assertNothingSent();
    expect(Cache::has('products.eligible_threshold_notified'))->toBeTrue();
});

it('mengirim notifikasi telegram saat jumlah eligible melewati ambang batas', function () {
    Cache::put('products.schedule.eligible_ids', collect(range(1, 950)), 60);

    config()->set('services.telegram.bot_token', 'test-token');
    config()->set('services.telegram.chat_id', '123456789');
    config()->set('app.url', 'http://simplemp.test');

    Http::fake([
        'https://api.telegram.org/*' => Http::response(['ok' => true], 200),
    ]);

    $this->artisan('products:notify-eligible-threshold')
        ->expectsOutput('Notifikasi ambang batas berhasil dikirim (jumlah: 950).')
        ->assertExitCode(0);

    Http::assertSent(function ($request) {
        return str($request->url())->contains('/sendMessage')
            && $request['chat_id'] === '123456789'
            && $request['parse_mode'] === 'HTML'
            && str($request['text'])->contains('950');
    });

    expect(Cache::has('products.eligible_threshold_notified'))->toBeTrue();
});
