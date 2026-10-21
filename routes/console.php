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

// Daily encrypted database backup (T-35)
Schedule::command('backup:database')
    ->dailyAt('01:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Daily subscription reconciliation with payment gateways (T-44 / SEC-25 / billing.md)
Schedule::command('billing:reconcile')
    ->dailyAt('03:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();

// Queue worker for shared hosting (cPanel friendly: runs pending jobs then terminates cleanly)
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
