<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-due-reminders')->everyFiveMinutes();

// One morning digest per person of tasks due tomorrow / today / newly overdue,
// plus escalations for tasks 3+ days late. 8 AM in the team's own timezone.
Schedule::command('app:send-task-deadline-alerts')
    ->dailyAt('08:00')
    ->timezone(config('app.business_timezone'))
    ->onOneServer();

// Prune backups older than the retention window (config/backup.php) before today's
// backup runs, then take today's backup — same order spatie/laravel-backup's own
// docs recommend. Both need `php artisan schedule:run` actually firing (a cron
// entry or `schedule:work`) to ever execute — see the README's "Backups" section.
Schedule::command('backup:clean')->daily()->at('01:00')->onOneServer();
Schedule::command('backup:run')->daily()->at('01:30')->onOneServer();

// Permanently remove records that have sat in "Recently deleted" for 30 days
// (App\Models\Concerns\Restorable). Runs after the nightly backup, so anything
// purged is still in the previous night's backup.
Schedule::command('model:prune')->daily()->at('02:30')->onOneServer();
