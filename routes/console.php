<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


Schedule::command('onboarding:cleanup --minutes=60')->hourly()->withoutOverlapping();

Schedule::command('onboarding:prune --days=30')->weekly()->sundays()->at('02:00');