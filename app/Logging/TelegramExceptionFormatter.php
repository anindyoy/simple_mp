<?php

namespace App\Logging;

use Illuminate\Support\Facades\Auth;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;
use Throwable;

class TelegramExceptionFormatter implements FormatterInterface
{
    public function format(LogRecord $record): string
    {
        $exception = $record->context['exception'] ?? null;

        $errorMessage = $record->message;
        $errorFile = '-';
        $errorLine = '-';

        if ($exception instanceof Throwable) {
            $errorMessage = $exception->getMessage();
            $errorFile = $exception->getFile();
            $errorLine = (string) $exception->getLine();
        }

        $authUser = Auth::user();

        $message = "Error: {$errorMessage}\n";
        $message .= "File: {$errorFile}\n";
        $message .= "Line: {$errorLine}\n";
        $message .= 'Domain: '.config('app.url')."\n\n";
        $message .= 'Username: '.($authUser?->name ?? '-')."\n";
        $message .= 'Email: '.($authUser?->email ?? '-');

        return $message;
    }

    public function formatBatch(array $records): string
    {
        return implode("\n\n", array_map(fn (LogRecord $record): string => $this->format($record), $records));
    }
}
