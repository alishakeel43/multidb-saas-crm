<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('inbound-emails:process')->everyFiveMinutes();
Schedule::command('bookings:send-reminders --all')->everyFifteenMinutes();
Schedule::command('saas:subscriptions:tick')->daily();
Schedule::command('saas:tenants:backup --all')->dailyAt('02:15');
Schedule::command('backup:run')->dailyAt('02:30');
Schedule::command('health:check')->everyFiveMinutes();
