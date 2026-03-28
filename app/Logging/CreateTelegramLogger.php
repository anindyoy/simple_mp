<?php

namespace App\Logging;

use Monolog\Handler\NullHandler;
use Monolog\Handler\TelegramBotHandler;
use Monolog\Level;
use Monolog\Logger;

class CreateTelegramLogger
{
    public function __invoke(array $config): Logger
    {
        $logger = new Logger('telegram');

        $apiKey = (string) config('services.telegram.bot_token', '');
        $chatId = (string) config('services.telegram.chat_id', '');
        $level = Level::fromName(strtoupper((string) ($config['level'] ?? 'error')));

        if (blank($apiKey) || blank($chatId)) {
            $logger->pushHandler(new NullHandler($level));

            return $logger;
        }

        $handler = new TelegramBotHandler(
            $apiKey,
            $chatId,
            $level,
        );

        $formatter = $config['formatter'] ?? null;

        if (is_string($formatter) && class_exists($formatter)) {
            $handler->setFormatter(app($formatter));
        }

        $logger->pushHandler($handler);

        return $logger;
    }
}
