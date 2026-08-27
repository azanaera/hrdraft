<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Daily is safe regardless of each policy's actual accrual cadence — the
// command only posts an entry once a full period has elapsed since the last
// one, so running it more often than any policy's period is a no-op for
// employments not yet due.
Schedule::command('time-off:accrue')->daily();
