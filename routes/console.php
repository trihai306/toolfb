<?php

use App\Jobs\DispatchScheduledPosts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// === AutoFB Scheduler ===

// Check for due scheduled posts every minute
Schedule::job(new DispatchScheduledPosts)->everyMinute()
    ->name('autofb:dispatch-scheduled-posts')
    ->withoutOverlapping()
    ->onOneServer();

// Clean up old failed jobs daily
Schedule::command('queue:prune-failed --hours=48')->daily()
    ->name('autofb:prune-failed-jobs');

// Prune stale batches
Schedule::command('queue:prune-batches --hours=48')->daily()
    ->name('autofb:prune-batches');
