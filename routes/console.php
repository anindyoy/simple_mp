<?php

use Illuminate\Support\Facades\Schedule;

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

if (config('app.demo_mode')) {
    Schedule::command('db:seed', ['--class' => 'UpdateCatalogSeeder'])
        ->daily()
        ->at('06:00')
        ->withoutOverlapping();
}
