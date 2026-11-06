<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Close demo game sessions once they pass their expiry time.
Schedule::command('luxbet:expire-game-sessions')->everyFiveMinutes()->withoutOverlapping();
