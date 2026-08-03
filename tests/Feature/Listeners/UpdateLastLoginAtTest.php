<?php

use App\Listeners\UpdateLastLoginAt;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('terdaftar sebagai listener untuk event Login', function () {
    Event::fake();

    Event::assertListening(Login::class, UpdateLastLoginAt::class);
});

it('meng-update last_login_at saat handle dipanggil', function () {
    $user = User::factory()->create([
        'last_login_at' => null,
    ]);

    $event = new Login('web', $user, true);
    (new UpdateLastLoginAt())->handle($event);

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull()
        ->and($user->last_login_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('meng-update last_login_at dengan waktu sekarang', function () {
    $user = User::factory()->create([
        'last_login_at' => now()->subDays(5),
    ]);

    $oldLogin = $user->last_login_at;
    $event = new Login('web', $user, true);
    (new UpdateLastLoginAt())->handle($event);

    $user->refresh();
    expect($user->last_login_at)->not->toBe($oldLogin)
        ->and($user->last_login_at)->toBeGreaterThan($oldLogin);
});

it('tidak melempar exception saat event Login di-dispatch', function () {
    $user = User::factory()->create();

    expect(fn () => event(new Login('web', $user, true)))
        ->not->toThrow(Exception::class);
});