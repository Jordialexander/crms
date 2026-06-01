<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cek dan kirim reminder jadwal implementasi (mendekati 24 jam)
Schedule::command('cr:check-upcoming')->everyMinute();

// Cek SLA implementasi in_progress yang melewati batas jadwal selesai
Schedule::command('cr:check-overdue')->everyMinute();
