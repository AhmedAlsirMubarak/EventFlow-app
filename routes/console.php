<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:send-due-reminders')->everyFiveMinutes();

// Prune backups older than the retention window (config/backup.php) before today's
// backup runs, then take today's backup — same order spatie/laravel-backup's own
// docs recommend. Both need `php artisan schedule:run` actually firing (a cron
// entry or `schedule:work`) to ever execute — see the README's "Backups" section.
Schedule::command('backup:clean')->daily()->at('01:00')->onOneServer();
Schedule::command('backup:run')->daily()->at('01:30')->onOneServer();
