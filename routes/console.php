<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Email attendees their 3-day and 24-hour reminders. Runs hourly; the command
// is idempotent (per-attendee timestamp columns), so this cadence comfortably
// covers both windows without any risk of double-sending.
Schedule::command('events:send-reminders')->hourly()->withoutOverlapping();
