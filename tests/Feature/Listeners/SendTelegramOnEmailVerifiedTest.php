<?php

use App\Listeners\SendTelegramOnEmailVerified;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Monolog\Handler\NullHandler;
use Monolog\Handler\TelegramBotHandler;
use Monolog\Logger;

beforeEach(function () {
    config()->set('services.telegram.bot_token', null);
    config()->set('services.telegram.chat_id', null);
});

it('terdaftar sebagai listener untuk event Verified', function () {
    Event::fake();

    Event::assertListening(Verified::class, SendTelegramOnEmailVerified::class);
});

it('makeLogger menggunakan NullHandler saat konfigurasi telegram tidak lengkap', function () {
    $listener = new SendTelegramOnEmailVerified();
    $logger = (new ReflectionMethod($listener, 'makeLogger'))->invoke($listener);

    expect($logger)->toBeInstanceOf(Logger::class)
        ->and($logger->getHandlers()[0])->toBeInstanceOf(NullHandler::class);
});

it('makeLogger menggunakan TelegramBotHandler saat konfigurasi tersedia', function () {
    config()->set('services.telegram.bot_token', 'test-token');
    config()->set('services.telegram.chat_id', '123456789');

    $listener = new SendTelegramOnEmailVerified();
    $logger = (new ReflectionMethod($listener, 'makeLogger'))->invoke($listener);

    expect($logger)->toBeInstanceOf(Logger::class)
        ->and($logger->getHandlers()[0])->toBeInstanceOf(TelegramBotHandler::class);
});

it('tidak melempar exception saat handle dipanggil tanpa konfigurasi telegram', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    expect(fn () => (new SendTelegramOnEmailVerified())->handle(new Verified($user)))
        ->not->toThrow(Exception::class);
});

it('tidak melempar exception saat event Verified di-dispatch', function () {
    $user = User::factory()->create(['email_verified_at' => null]);

    expect(fn () => event(new Verified($user)))
        ->not->toThrow(Exception::class);
});
