<?php

use App\Jobs\WorkerHeartbeat;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('codeforge:repositories:reconcile')
    ->dailyAt('02:15')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::job(new WorkerHeartbeat)
    ->everyMinute()
    ->withoutOverlapping()
    ->onOneServer();
