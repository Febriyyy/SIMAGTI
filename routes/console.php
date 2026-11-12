<?php

use App\Actions\Reminders\SendReminders;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Pengingat otomatis untuk mahasiswa, Company Supervisor, dan dosen pembimbing (lonceng + email).
Artisan::command('kp:pengingat', function (SendReminders $reminders) {
    $sent = $reminders->handle();
    $this->info(sprintf(
        'Pengingat terkirim: %d catatan belum diisi, %d task mendekati tenggat, %d penilaian, %d bimbingan menunggu.',
        $sent['idle'], $sent['due'], $sent['assessment'], $sent['guidance'],
    ));
})->purpose('Kirim pengingat otomatis harian (catatan, tenggat task, penilaian, bimbingan)');

// Dijalankan setiap hari pukul 07.00 (butuh cron "php artisan schedule:run" tiap menit di server hosting).
Schedule::command('kp:pengingat')->dailyAt('07:00')->withoutOverlapping();
