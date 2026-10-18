<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('booking:auto-cancel-pending')
    ->everyThirtyMinutes()
    ->withoutOverlapping();

Schedule::command('documents:auto-delete')
    ->dailyAt('02:00')
    ->withoutOverlapping();
