<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentReviewController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\BiroVerificationController;
use App\Http\Controllers\BoardColumnController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InternshipApplicationController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\KaprodiApprovalController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\PlacementController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentTaskController;
use App\Http\Controllers\SupervisorActivationController;
use App\Http\Controllers\SyllabusController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDetailController;
use App\Http\Controllers\TaskEvidenceController;
use App\Http\Controllers\TimesheetController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

/*
 * Aturan umum (poin 18, privasi): semua perubahan data lewat POST/PATCH/DELETE dengan token CSRF,
 * tidak ada data pribadi di alamat URL (hanya id), dan setiap halaman/berkas dicek hak aksesnya lewat Policy.
 */

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'));

// Undangan Company Supervisor: halaman publik, keabsahan dicek lewat token.
Route::get('/undangan/{token}', [InvitationController::class, 'show'])->name('undangan.show');
Route::post('/undangan/{token}', [InvitationController::class, 'store'])->middleware('throttle:10,1')->name('undangan.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // ---------- Task (dilihat mahasiswa, Company Supervisor, dosen pembimbing; dicek WeeklyTaskPolicy) ----------
    Route::get('/tugas/{task}', [TaskDetailController::class, 'show'])->name('tugas.show')->whereNumber('task');
    Route::post('/tugas/{task}/komentar', [TaskCommentController::class, 'store'])
        ->whereNumber('task')->middleware('throttle:30,1')->name('tugas.komentar');
    Route::get('/tugas/{task}/lampiran/{attachment}', [TaskAttachmentController::class, 'show'])
        ->whereNumber(['task', 'attachment'])->scopeBindings()->name('tugas.lampiran.show');
    Route::get('/tugas/{task}/bukti/{evidence}', [TaskEvidenceController::class, 'show'])
        ->whereNumber(['task', 'evidence'])->scopeBindings()->name('tugas.bukti.show');
    Route::get('/timesheet/{entry}/foto', [TimesheetController::class, 'photo'])->whereNumber('entry')->name('timesheet.foto');

    // ---------- Mahasiswa ----------
    Route::middleware(EnsureRole::class.':mahasiswa')->group(function () {
        // Status KP (dulu "Pengajuan KP"): pengajuan dan kemajuannya langkah demi langkah
        Route::get('/status-kp', [InternshipApplicationController::class, 'index'])->name('kp.status');
        Route::post('/status-kp', [InternshipApplicationController::class, 'store'])->middleware('throttle:10,1')->name('kp.apply');

        // Silabus
        Route::get('/silabus', [SyllabusController::class, 'index'])->name('silabus.index');
        Route::post('/silabus', [SyllabusController::class, 'store'])->name('silabus.store');
        Route::patch('/silabus/{item}', [SyllabusController::class, 'update'])->whereNumber('item')->name('silabus.update');
        Route::delete('/silabus/{item}', [SyllabusController::class, 'destroy'])->whereNumber('item')->name('silabus.destroy');
        Route::post('/silabus/kirim', [SyllabusController::class, 'submit'])->name('silabus.submit');
        Route::post('/silabus/ubah', [SyllabusController::class, 'reopen'])->name('silabus.reopen');

        // Kegiatan: board, list, proyek, kolom, task
        Route::get('/kegiatan', [StudentTaskController::class, 'index'])->name('kegiatan.index');
        Route::patch('/kegiatan/pengaturan', [StudentTaskController::class, 'settings'])->name('kegiatan.pengaturan');
        Route::post('/kegiatan/tugas', [TaskController::class, 'store'])->middleware('throttle:60,1')->name('kegiatan.tugas.store');
        Route::patch('/kegiatan/tugas/{task}', [TaskController::class, 'update'])->whereNumber('task')->name('kegiatan.tugas.update');
        Route::patch('/kegiatan/tugas/{task}/pindah', [TaskController::class, 'move'])->whereNumber('task')->name('kegiatan.tugas.move');
        Route::delete('/kegiatan/tugas/{task}', [TaskController::class, 'destroy'])->whereNumber('task')->name('kegiatan.tugas.destroy');
        Route::post('/kegiatan/kolom', [BoardColumnController::class, 'store'])->name('kegiatan.kolom.store');
        Route::patch('/kegiatan/kolom/{column}', [BoardColumnController::class, 'update'])->whereNumber('column')->name('kegiatan.kolom.update');
        Route::delete('/kegiatan/kolom/{column}', [BoardColumnController::class, 'destroy'])->whereNumber('column')->name('kegiatan.kolom.destroy');
        Route::post('/kegiatan/proyek', [ProjectController::class, 'store'])->name('kegiatan.proyek.store');
        Route::patch('/kegiatan/proyek/{project}', [ProjectController::class, 'update'])->whereNumber('project')->name('kegiatan.proyek.update');
        Route::post('/tugas/{task}/checklist', [ChecklistController::class, 'store'])->whereNumber('task')->name('checklist.store');
        Route::patch('/tugas/{task}/checklist/{item}', [ChecklistController::class, 'update'])
            ->whereNumber(['task', 'item'])->scopeBindings()->name('checklist.update');
        Route::delete('/tugas/{task}/checklist/{item}', [ChecklistController::class, 'destroy'])
            ->whereNumber(['task', 'item'])->scopeBindings()->name('checklist.destroy');

        // Bukti pengerjaan
        Route::patch('/tugas/{task}/bukti', [StudentTaskController::class, 'evidence'])->middleware('throttle:20,1')->name('tugas.bukti');
        Route::delete('/tugas/{task}/bukti/{evidence}', [TaskEvidenceController::class, 'destroy'])
            ->whereNumber(['task', 'evidence'])->scopeBindings()->name('tugas.bukti.destroy');

        // Timesheet / logbook
        Route::get('/timesheet', [TimesheetController::class, 'index'])->name('timesheet.index');
        Route::post('/timesheet', [TimesheetController::class, 'store'])->middleware('throttle:60,1')->name('timesheet.store');
        Route::patch('/timesheet/{entry}', [TimesheetController::class, 'update'])->whereNumber('entry')->name('timesheet.update');
        Route::delete('/timesheet/{entry}', [TimesheetController::class, 'destroy'])->whereNumber('entry')->name('timesheet.destroy');

        Route::get('/kegiatan/unduh', [ExportController::class, 'mine'])->name('kegiatan.unduh');
    });

    // ---------- Pemantauan: Company Supervisor, dosen pembimbing, kaprodi ----------
    Route::middleware(EnsureRole::class.':supervisor,dospem,kaprodi')->group(function () {
        Route::get('/pantau', [MonitorController::class, 'index'])->name('pantau.index');
        Route::get('/pantau/{internship}', [MonitorController::class, 'show'])->whereNumber('internship')->name('pantau.show');
        Route::get('/pantau/{internship}/unduh', [ExportController::class, 'internship'])->whereNumber('internship')->name('pantau.unduh');
    });

    Route::middleware(EnsureRole::class.':supervisor,dospem')->group(function () {
        Route::patch('/pantau/{internship}/silabus', [SyllabusController::class, 'review'])->whereNumber('internship')->name('silabus.review');
        Route::post('/meeting', [MeetingController::class, 'store'])->name('meeting.store');

        // Penilaian akhir (sementara): diisi Company Supervisor (seminar: dosen pembimbing)
        Route::get('/penilaian', [AssessmentController::class, 'index'])->name('penilaian.index');
        Route::get('/penilaian/{internship}', [AssessmentController::class, 'edit'])->whereNumber('internship')->name('penilaian.edit');
        Route::put('/penilaian/{internship}', [AssessmentController::class, 'update'])->whereNumber('internship')->name('penilaian.update');
    });

    Route::middleware(EnsureRole::class.':supervisor')->group(function () {
        Route::patch('/pantau/{internship}/posisi', [MonitorController::class, 'position'])->whereNumber('internship')->name('pantau.posisi');
    });

    // Dosen pembimbing memvalidasi penilaian akhir
    Route::middleware(EnsureRole::class.':dospem')->group(function () {
        Route::get('/validasi', [AssessmentReviewController::class, 'index'])->name('validasi.index');
        Route::get('/validasi/{internship}', [AssessmentReviewController::class, 'show'])->whereNumber('internship')->name('validasi.show');
        Route::patch('/validasi/{internship}', [AssessmentReviewController::class, 'update'])
            ->whereNumber('internship')->middleware('throttle:30,1')->name('validasi.update');
    });

    // ---------- TU Fakultas: aktivasi Company Supervisor ----------
    Route::middleware(EnsureRole::class.':tu')->prefix('tu')->group(function () {
        Route::get('/aktivasi', [SupervisorActivationController::class, 'index'])->name('tu.aktivasi');
        Route::post('/aktivasi/undang', [SupervisorActivationController::class, 'invite'])->middleware('throttle:30,1')->name('tu.aktivasi.undang');
        Route::post('/aktivasi/tautkan', [SupervisorActivationController::class, 'link'])->name('tu.aktivasi.tautkan');
        Route::patch('/aktivasi/email', [SupervisorActivationController::class, 'updateEmail'])->name('tu.aktivasi.email');
    });

    // ---------- Data master ----------
    Route::middleware(EnsureRole::class.':biro,kaprodi')->group(function () {
        Route::get('/perusahaan', [CompanyController::class, 'index'])->name('perusahaan.index');
        Route::get('/perusahaan/{company}', [CompanyController::class, 'show'])->whereNumber('company')->name('perusahaan.show');
    });

    Route::middleware(EnsureRole::class.':biro,kaprodi,tu')->group(function () {
        Route::get('/penempatan', [PlacementController::class, 'index'])->name('penempatan.index');
    });

    // Biro kerjasama
    Route::middleware(EnsureRole::class.':biro')->prefix('biro')->group(function () {
        Route::get('/verifikasi', [BiroVerificationController::class, 'index'])->name('biro.verifikasi');
        Route::patch('/verifikasi/{internship}', [BiroVerificationController::class, 'update'])->whereNumber('internship')->name('biro.verifikasi.update');
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
        Route::patch('/kaprodi/persetujuan/{internship}', [KaprodiApprovalController::class, 'update'])->whereNumber('internship')->name('kaprodi.persetujuan.update');
        Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->name('log.index');

        Route::get('/mahasiswa', [StudentController::class, 'index'])->name('mahasiswa.index');
        Route::post('/mahasiswa', [StudentController::class, 'store'])->name('mahasiswa.store');

        Route::get('/penempatan/tambah', [PlacementController::class, 'create'])->name('penempatan.create');
        Route::post('/penempatan', [PlacementController::class, 'store'])->name('penempatan.store');
    });
});
