<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

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
