<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BiroVerificationController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InternshipApplicationController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\KaprodiApprovalController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MentorTaskController;
use App\Http\Controllers\PlacementController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentTaskController;
use App\Http\Controllers\TaskDetailController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

// Undangan mentor / ketua divisi: halaman publik, keabsahan dicek lewat token.
Route::get('/undangan/{token}', [InvitationController::class, 'show'])->name('undangan.show');
Route::post('/undangan/{token}', [InvitationController::class, 'store'])->middleware('throttle:10,1')->name('undangan.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Detail task (mahasiswa pemilik atau mentornya; dicek di controller)
    Route::get('/tugas/{task}', [TaskDetailController::class, 'show'])->name('tugas.show')->whereNumber('task');

    // Mahasiswa
    Route::middleware(EnsureRole::class.':mahasiswa')->group(function () {
        Route::get('/tugas', [StudentTaskController::class, 'index'])->name('tugas.index');
        Route::patch('/tugas/{task}/bukti', [StudentTaskController::class, 'evidence'])->name('tugas.bukti');

        Route::get('/pengajuan-kp', [InternshipApplicationController::class, 'index'])->name('pengajuan.index');
        Route::post('/pengajuan-kp', [InternshipApplicationController::class, 'store'])->name('pengajuan.store');
    });

    // Mentor
    Route::middleware(EnsureRole::class.':mentor')->prefix('mentor')->group(function () {
        Route::get('/tugas', [MentorTaskController::class, 'index'])->name('mentor.tugas');
        Route::post('/tugas', [MentorTaskController::class, 'store'])->name('mentor.tugas.store');
        Route::patch('/tugas/{task}', [MentorTaskController::class, 'update'])->name('mentor.tugas.update');
        Route::post('/meeting', [MeetingController::class, 'store'])->name('mentor.meeting.store');
    });

    // Data master. Hak akses per aksi dijaga Policy; middleware hanya menyaring peran yang boleh masuk.
    Route::middleware(EnsureRole::class.':biro,kaprodi')->group(function () {
        Route::get('/perusahaan', [CompanyController::class, 'index'])->name('perusahaan.index');
        Route::get('/penempatan', [PlacementController::class, 'index'])->name('penempatan.index');
    });

    // Biro kerjasama
    Route::middleware(EnsureRole::class.':biro')->prefix('biro')->group(function () {
        Route::get('/verifikasi', [BiroVerificationController::class, 'index'])->name('biro.verifikasi');
        Route::patch('/verifikasi/{internship}', [BiroVerificationController::class, 'update'])
            ->whereNumber('internship')
            ->name('biro.verifikasi.update');
    });

    Route::middleware(EnsureRole::class.':biro')->group(function () {
        Route::get('/perusahaan/tambah', [CompanyController::class, 'create'])->name('perusahaan.create');
        Route::post('/perusahaan', [CompanyController::class, 'store'])->name('perusahaan.store');
        Route::get('/perusahaan/{company}/ubah', [CompanyController::class, 'edit'])->whereNumber('company')->name('perusahaan.edit');
        Route::patch('/perusahaan/{company}', [CompanyController::class, 'update'])->whereNumber('company')->name('perusahaan.update');
    });

    // Kaprodi
    Route::middleware(EnsureRole::class.':kaprodi')->group(function () {
        Route::get('/kaprodi/persetujuan', [KaprodiApprovalController::class, 'index'])->name('kaprodi.persetujuan');
        Route::patch('/kaprodi/persetujuan/{internship}', [KaprodiApprovalController::class, 'update'])
            ->whereNumber('internship')
            ->name('kaprodi.persetujuan.update');
        Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->name('log.index');

        Route::get('/mahasiswa', [StudentController::class, 'index'])->name('mahasiswa.index');
        Route::post('/mahasiswa', [StudentController::class, 'store'])->name('mahasiswa.store');

        Route::get('/penempatan/tambah', [PlacementController::class, 'create'])->name('penempatan.create');
        Route::post('/penempatan', [PlacementController::class, 'store'])->name('penempatan.store');
    });
});
