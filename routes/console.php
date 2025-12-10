<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Auto-close shifts daily at 00:01
Schedule::command('shift:auto-close')
    ->dailyAt('00:01')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping();
