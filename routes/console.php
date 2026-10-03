<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('notify:stale-cvs', function () {
    $staleCount = \App\Models\Player::where('updated_at', '<=', now()->subYear())->count();
    if ($staleCount > 0) {
        \Illuminate\Support\Facades\Log::info("System Check: {$staleCount} player profiles (CVs) have not been updated for over a year.");
        // This could be extended to send emails to admins
    }
})->purpose('Notify admins about stale CVs')->daily();

// Auto-update contract_status to EXPIRED for players whose contract_end_date has passed.
// Runs every day at midnight.
Schedule::command('contracts:update-expired')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground();
