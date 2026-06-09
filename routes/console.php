<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('tokens:refill-daily')
    ->daily()
    ->at('00:00')
    ->withoutOverlapping();

Schedule::command('tokens:refill-weekly')
    ->fridays()
    ->at('00:00')
    ->withoutOverlapping();

Schedule::command('products:notify-eligible-threshold')
    ->cron('0 0,8,16 * * *')
    ->withoutOverlapping();

Schedule::command('telescope:prune', ['--hours' => 360])
    ->daily()
    ->at('03:00');
