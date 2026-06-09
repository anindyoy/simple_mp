<?php

namespace App\Listeners;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\NullHandler;
use Illuminate\Auth\Events\Verified;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\TelegramBotHandler;

class SendTelegramOnEmailVerified
{
    public function handle(Verified $event): void
    {
        $logger = $this->makeLogger();
        $user = $event->user;

        $logger->info(implode("\n", [
            '✅ <b>Akun Baru Terverifikasi</b>',
            '',
            "Nama: <b>{$user->name}</b>",
            "Email: <code>{$user->email}</code>",
            'Waktu: ' . now()->format('d M Y, H:i') . ' WIB',
            '',
            'Domain: ' . config('app.url'),
        ]));
    }

    private function makeLogger(): Logger
    {
        $logger = new Logger('telegram');

        $token = (string) config('services.telegram.bot_token');
        $chatId = (string) config('services.telegram.chat_id');

        if (blank($token) || blank($chatId)) {
            $logger->pushHandler(new NullHandler());

            return $logger;
        }

        $handler = new TelegramBotHandler(
            apiKey: $token,
            channel: $chatId,
            level: Level::Info,
            parseMode: 'HTML',
        );

        $handler->setFormatter(new LineFormatter('%message%'));

        $logger->pushHandler($handler);

        return $logger;
    }
}
