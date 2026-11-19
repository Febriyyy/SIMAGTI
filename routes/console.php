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
        'Pengingat terkirim: %d catatan belum diisi, %d task mendekati tenggat, %d penilaian, %d bimbingan menunggu, %d tawaran kerja; %d tawaran kedaluwarsa.',
        $sent['idle'], $sent['due'], $sent['assessment'], $sent['guidance'], $sent['offers'], $sent['expired'],
    ));
})->purpose('Kirim pengingat otomatis harian (catatan, tenggat task, penilaian, bimbingan)');

// Pengingat bimbingan terjadwal dan sidang KP: sehari sebelumnya dan sekitar satu jam sebelum mulai.
Artisan::command('kp:pengingat-jadwal', function (SendReminders $reminders) {
    $sent = $reminders->upcoming();
    $this->info(sprintf('Pengingat jadwal terkirim: %d bimbingan, %d sidang.', $sent['guidance'], $sent['defense']));
})->purpose('Ingatkan bimbingan dan sidang KP yang akan datang (H-1 dan sekitar 1 jam sebelumnya)');

// Butuh cron "php artisan schedule:run" tiap menit di server hosting.
Schedule::command('kp:pengingat')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('kp:pengingat-jadwal')->everyFifteenMinutes()->withoutOverlapping();
