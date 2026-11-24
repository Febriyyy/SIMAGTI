<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ActivityMonitorController;
use App\Http\Controllers\ActivityPlanController;
use App\Http\Controllers\AdminCampusController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssessmentReviewController;
use App\Http\Controllers\AssessmentStandardController;
use App\Http\Controllers\AssessmentWeightController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\BoardColumnController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\ChangeRequestController;
use App\Http\Controllers\ChecklistController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\CompanyReviewController;
use App\Http\Controllers\ConversionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DefenseController;
use App\Http\Controllers\DefenseMinutesController;
use App\Http\Controllers\DefenseScheduleController;
use App\Http\Controllers\DivisionHeadController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentQueueController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\GoalController;
use App\Http\Controllers\GuidanceController;
use App\Http\Controllers\InternshipApplicationController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\JobPostingController;
use App\Http\Controllers\KaprodiApprovalController;
use App\Http\Controllers\KaprodiSignatureController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\LogoController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PlacementController;
use App\Http\Controllers\PortfolioController;
use App\Http\Controllers\PortfolioItemController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RecapController;
use App\Http\Controllers\ReportChapterController;
use App\Http\Controllers\ReportDiscussionController;
use App\Http\Controllers\ReportDraftController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentTaskController;
use App\Http\Controllers\SupervisorActivationController;
use App\Http\Controllers\SupervisorConfirmationController;
use App\Http\Controllers\TaskAttachmentController;
use App\Http\Controllers\TaskCommentController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskDetailController;
use App\Http\Controllers\TaskEvidenceController;
use App\Http\Controllers\TaskTemplateController;
use App\Http\Controllers\TimesheetController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\WrappedController;
use App\Http\Middleware\EnsureActiveAccount;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

/*
 * Aturan umum (poin 18, privasi): semua perubahan data lewat POST/PATCH/DELETE dengan token CSRF,
 * tidak ada data pribadi di alamat URL (hanya id), dan setiap halaman/berkas dicek hak aksesnya lewat Policy.
 */

Route::get('/', LandingController::class)->name('home');

// Undangan Company Supervisor: halaman publik, keabsahan dicek lewat token.
// Portofolio mahasiswa publik (tanpa login, hanya bila mahasiswa menyalakannya)
Route::get('/p/{token}', [PortfolioController::class, 'show'])->middleware('throttle:60,1')->where('token', '[A-Za-z0-9]{20,64}')->name('portofolio.publik');
Route::get('/p/{token}/berkas/{file}', [PortfolioController::class, 'publicFile'])->middleware('throttle:120,1')->where('token', '[A-Za-z0-9]{20,64}')->whereNumber('file')->name('portofolio.publik.berkas');
Route::get('/undangan/{token}', [InvitationController::class, 'show'])->name('undangan.show');
Route::post('/undangan/{token}', [InvitationController::class, 'store'])->middleware('throttle:10,1')->name('undangan.store');

// Verifikasi sertifikat KP lewat QR: publik, tidak menampilkan NIM.
// Logo kampus dan perusahaan untuk sertifikat (bukan data pribadi; dipakai juga halaman verifikasi tanpa login)
Route::get('/logo/kampus', [LogoController::class, 'campus'])->middleware('throttle:120,1')->name('logo.kampus');
Route::get('/logo/fakultas', [LogoController::class, 'faculty'])->middleware('throttle:120,1')->name('logo.fakultas');
Route::get('/logo/perusahaan/{company}', [LogoController::class, 'company'])->whereNumber('company')->middleware('throttle:120,1')->name('logo.perusahaan');

Route::get('/sertifikat/verifikasi/{token}', [CertificateController::class, 'verify'])
    ->where('token', '[A-Za-z0-9]{20,64}')->middleware('throttle:60,1')->name('sertifikat.verifikasi');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');

    // Lupa kata sandi: tautan atur ulang lewat email
    Route::get('/lupa-sandi', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/lupa-sandi', [PasswordResetController::class, 'email'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/atur-sandi/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/atur-sandi', [PasswordResetController::class, 'update'])->middleware('throttle:10,1')->name('password.update');
});

Route::middleware(['auth', EnsureActiveAccount::class, EnsurePasswordChanged::class])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Ganti kata sandi (semua peran). Akun dengan kata sandi sementara diarahkan ke sini terus.
    Route::get('/akun/sandi', [PasswordController::class, 'edit'])->name('akun.sandi');
    Route::put('/akun/sandi', [PasswordController::class, 'update'])->middleware('throttle:10,1')->name('akun.sandi.update');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Portofolio mahasiswa untuk pihak kampus, dosen pembimbing, dan Company Supervisor (dicek Portfolio::canBeViewedBy)
    Route::get('/portofolio/mahasiswa/{student}', [PortfolioController::class, 'view'])->whereNumber('student')->name('portofolio.lihat');
    Route::get('/portofolio/berkas/{file}', [PortfolioController::class, 'file'])->whereNumber('file')->name('portofolio.berkas');

    // ---------- Task (dilihat mahasiswa, Company Supervisor, dosen pembimbing; dicek WeeklyTaskPolicy) ----------
    Route::get('/tugas/{task}', [TaskDetailController::class, 'show'])->name('tugas.show')->whereNumber('task');
    Route::post('/tugas/{task}/komentar', [TaskCommentController::class, 'store'])
        ->whereNumber('task')->middleware('throttle:30,1')->name('tugas.komentar');
    Route::get('/tugas/{task}/lampiran/{attachment}', [TaskAttachmentController::class, 'show'])
        ->whereNumber(['task', 'attachment'])->scopeBindings()->name('tugas.lampiran.show');
    Route::get('/tugas/{task}/bukti/{evidence}', [TaskEvidenceController::class, 'show'])
        ->whereNumber(['task', 'evidence'])->scopeBindings()->name('tugas.bukti.show');
    // Notifikasi (lonceng)
    Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifikasi.index');
    Route::get('/notifikasi/{id}', [NotificationController::class, 'open'])->name('notifikasi.open');
    Route::post('/notifikasi/baca', [NotificationController::class, 'readAll'])->name('notifikasi.baca');

    // Berkas dokumen KP (dicek InternshipPolicy view)
    Route::get('/dokumen/{document}/berkas', [DocumentController::class, 'file'])->whereNumber('document')->name('dokumen.berkas');
    Route::get('/dokumen/{document}/cetak', [DocumentController::class, 'letter'])->whereNumber('document')->name('dokumen.cetak');

    // Sertifikat KP yang diterima (dicek InternshipPolicy view)
    Route::get('/sertifikat/{internship}', [CertificateController::class, 'show'])->whereNumber('internship')->name('sertifikat.cetak');

    Route::get('/timesheet/{entry}/foto', [TimesheetController::class, 'photo'])->whereNumber('entry')->name('timesheet.foto');

    // Surat resmi tawaran kerja (hanya mahasiswa penerima dan Company Supervisor penempatannya)
    Route::get('/rekrutmen/{candidate}/surat', [ConversionController::class, 'letter'])->whereNumber('candidate')->name('rekrutmen.surat');

    // Berkas laporan per bab (dicek ReportChapter::canBeReadBy)
    Route::get('/laporan-bab/{chapter}/berkas', [ReportChapterController::class, 'file'])->whereNumber('chapter')->name('laporan.berkas');
    Route::get('/laporan-bab/versi/{version}/berkas', [ReportChapterController::class, 'version'])->whereNumber('version')->name('laporan.versi');

    // Laporan KP bersama mahasiswa dan dosen pembimbing: diskusi per bab, berkas koreksi, laporan akhir (dicek DiscussReport)
    Route::get('/laporan-kp/{internship}', [ReportDiscussionController::class, 'show'])->whereNumber('internship')->name('laporan.diskusi');
    Route::post('/laporan-kp/{internship}/komentar', [ReportDiscussionController::class, 'comment'])->whereNumber('internship')->middleware('throttle:30,1')->name('laporan.komentar');
    Route::get('/laporan-kp/komentar/{comment}/berkas', [ReportDiscussionController::class, 'commentFile'])->whereNumber('comment')->name('laporan.komentar.berkas');
    Route::get('/laporan-kp/{internship}/akhir/berkas', [ReportDiscussionController::class, 'finalFile'])->whereNumber('internship')->name('laporan.akhir.berkas');

    // Berita acara sidang KP (mahasiswa, dosen pembimbing, kaprodi), setelah nilai sidang disahkan
    Route::get('/sidang/{internship}/berita-acara', [DefenseMinutesController::class, 'show'])->whereNumber('internship')->name('sidang.berita');

    // ---------- Mahasiswa ----------
    Route::middleware(EnsureRole::class.':mahasiswa')->group(function () {
        // Status KP (dulu "Pengajuan KP"): pengajuan dan kemajuannya langkah demi langkah
        Route::get('/status-kp', [InternshipApplicationController::class, 'index'])->name('kp.status');
        Route::post('/status-kp', [InternshipApplicationController::class, 'store'])->middleware('throttle:10,1')->name('kp.apply');
        // Ulasan tempat magang setelah masa magang berakhir
        Route::post('/status-kp/ulasan', [CompanyReviewController::class, 'store'])->middleware('throttle:10,1')->name('ulasan.simpan');

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
        Route::post('/kegiatan/goals', [GoalController::class, 'store'])->name('goal.store');
        Route::patch('/kegiatan/goals/{goal}', [GoalController::class, 'update'])->whereNumber('goal')->name('goal.update');
        Route::delete('/kegiatan/goals/{goal}', [GoalController::class, 'destroy'])->whereNumber('goal')->name('goal.destroy');
        Route::post('/tugas/{task}/checklist', [ChecklistController::class, 'store'])->whereNumber('task')->name('checklist.store');
        Route::patch('/tugas/{task}/checklist/{item}', [ChecklistController::class, 'update'])
            ->whereNumber(['task', 'item'])->scopeBindings()->name('checklist.update');
        Route::delete('/tugas/{task}/checklist/{item}', [ChecklistController::class, 'destroy'])
            ->whereNumber(['task', 'item'])->scopeBindings()->name('checklist.destroy');

        // Bukti pengerjaan
        Route::patch('/tugas/{task}/bukti', [StudentTaskController::class, 'evidence'])->middleware('throttle:20,1')->name('tugas.bukti');
        Route::delete('/tugas/{task}/bukti/{evidence}', [TaskEvidenceController::class, 'destroy'])
            ->whereNumber(['task', 'evidence'])->scopeBindings()->name('tugas.bukti.destroy');

        // Timesheet: rencana kegiatan (Gantt) diisi dulu, lalu catatan kegiatan harian
        Route::get('/timesheet', [TimesheetController::class, 'index'])->name('timesheet.index');
        // Draf laporan KP otomatis (cetak / salin teks)
        Route::get('/laporan', [ReportDraftController::class, 'show'])->name('laporan.draf');
        // KP Wrapped: rangkuman animasi setelah KP selesai
        Route::get('/wrapped', [WrappedController::class, 'show'])->name('wrapped');
        // Portofolio mahasiswa (diisi sejak sebelum magang): profil, skill, proyek, sertifikat pelatihan
        Route::get('/portofolio', [PortfolioController::class, 'edit'])->name('portofolio.edit');
        Route::patch('/portofolio', [PortfolioController::class, 'update'])->name('portofolio.update');
        Route::post('/portofolio/proyek', [PortfolioItemController::class, 'storeProject'])->middleware('throttle:30,1')->name('portofolio.proyek.store');
        Route::post('/portofolio/proyek/{project}', [PortfolioItemController::class, 'updateProject'])->whereNumber('project')->middleware('throttle:30,1')->name('portofolio.proyek.update');
        Route::delete('/portofolio/proyek/{project}', [PortfolioItemController::class, 'destroyProject'])->whereNumber('project')->name('portofolio.proyek.destroy');
        Route::post('/portofolio/sertifikat', [PortfolioItemController::class, 'storeCertificate'])->middleware('throttle:30,1')->name('portofolio.sertifikat.store');
        Route::post('/portofolio/sertifikat/{certificate}', [PortfolioItemController::class, 'updateCertificate'])->whereNumber('certificate')->middleware('throttle:30,1')->name('portofolio.sertifikat.update');
        Route::delete('/portofolio/sertifikat/{certificate}', [PortfolioItemController::class, 'destroyCertificate'])->whereNumber('certificate')->name('portofolio.sertifikat.destroy');
        Route::post('/timesheet/rencana', [ActivityPlanController::class, 'store'])->middleware('throttle:60,1')->name('rencana.store');
        Route::patch('/timesheet/rencana/{plan}', [ActivityPlanController::class, 'update'])->whereNumber('plan')->name('rencana.update');
        Route::delete('/timesheet/rencana/{plan}', [ActivityPlanController::class, 'destroy'])->whereNumber('plan')->name('rencana.destroy');
        Route::post('/timesheet', [TimesheetController::class, 'store'])->middleware('throttle:60,1')->name('timesheet.store');
        Route::patch('/timesheet/{entry}', [TimesheetController::class, 'update'])->whereNumber('entry')->name('timesheet.update');
        Route::delete('/timesheet/{entry}', [TimesheetController::class, 'destroy'])->whereNumber('entry')->name('timesheet.destroy');

        Route::get('/kegiatan/unduh', [ExportController::class, 'mine'])->name('kegiatan.unduh');

        // Bimbingan dengan dosen
        Route::get('/bimbingan', [GuidanceController::class, 'index'])->name('bimbingan.index');
        Route::post('/bimbingan', [GuidanceController::class, 'store'])->middleware('throttle:30,1')->name('bimbingan.store');
        Route::delete('/bimbingan/{session}', [GuidanceController::class, 'destroy'])->whereNumber('session')->name('bimbingan.destroy');

        // Dokumen KP
        Route::get('/dokumen', [DocumentController::class, 'index'])->name('dokumen.index');
        Route::post('/dokumen/surat-pengantar', [DocumentController::class, 'requestLetter'])->name('dokumen.minta');
        Route::post('/dokumen/laporan/{chapter}', [ReportChapterController::class, 'upload'])
            ->whereNumber('chapter')->middleware('throttle:20,1')->name('laporan.unggah');
        Route::get('/laporan-kp', [ReportDiscussionController::class, 'mine'])->name('laporan.saya');
        Route::post('/laporan-kp/{internship}/akhir', [ReportDiscussionController::class, 'uploadFinal'])
            ->whereNumber('internship')->middleware('throttle:20,1')->name('laporan.akhir');
        Route::post('/dokumen/unggah/{type}', [DocumentController::class, 'upload'])
            ->whereIn('type', ['surat_balasan'])->middleware('throttle:20,1')->name('dokumen.unggah');

        // Pengajuan perubahan
        Route::get('/perubahan', [ChangeRequestController::class, 'index'])->name('perubahan.index');
        Route::post('/perubahan', [ChangeRequestController::class, 'store'])->middleware('throttle:10,1')->name('perubahan.store');

        // Jawaban atas tawaran kerja dari tempat magang
        Route::get('/tawaran-kerja', [ConversionController::class, 'show'])->name('rekrutmen.tawaran');
        // Lowongan magang yang sedang dibuka
        Route::get('/lowongan-magang', [JobPostingController::class, 'browse'])->name('lowongan.index');
        Route::patch('/tawaran-kerja', [ConversionController::class, 'respond'])->middleware('throttle:10,1')->name('rekrutmen.jawab');
    });

    // ---------- Pemantauan: Company Supervisor, dosen pembimbing, kaprodi ----------
    Route::middleware(EnsureRole::class.':supervisor,dospem,kaprodi')->group(function () {
        Route::get('/pantau', [MonitorController::class, 'index'])->name('pantau.index');
        Route::get('/pantau/{internship}', [MonitorController::class, 'show'])->whereNumber('internship')->name('pantau.show');
        Route::get('/pantau/{internship}/unduh', [ExportController::class, 'internship'])->whereNumber('internship')->name('pantau.unduh');
        // Activity monitor: aktivitas terbaru mahasiswa yang dipantau
        Route::get('/aktivitas', [ActivityMonitorController::class, 'index'])->name('aktivitas.index');
    });

    Route::middleware(EnsureRole::class.':supervisor')->group(function () {
        // Meeting mingguan diatur Company Supervisor
        Route::post('/meeting', [MeetingController::class, 'store'])->name('meeting.store');

        // Data Sertifikat: logo perusahaan, nama dan tanda tangan ketua divisi
        Route::get('/data-sertifikat', [DivisionHeadController::class, 'index'])->name('sertifikat.data');
        Route::put('/data-sertifikat/divisi/{division}', [DivisionHeadController::class, 'update'])->whereNumber('division')->middleware('throttle:30,1')->name('sertifikat.data.divisi');
        Route::put('/data-sertifikat/perusahaan/{company}', [DivisionHeadController::class, 'logo'])->whereNumber('company')->middleware('throttle:20,1')->name('sertifikat.data.logo');
        Route::delete('/data-sertifikat/perusahaan/{company}', [DivisionHeadController::class, 'destroyLogo'])->whereNumber('company')->name('sertifikat.data.logo.destroy');

        // Template task: kumpulan task baku yang diterapkan ke mahasiswa magang sekali klik
        Route::get('/template-task', [TaskTemplateController::class, 'index'])->name('template.index');
        Route::post('/template-task', [TaskTemplateController::class, 'store'])->middleware('throttle:30,1')->name('template.store');
        Route::put('/template-task/{template}', [TaskTemplateController::class, 'update'])->whereNumber('template')->name('template.update');
        Route::delete('/template-task/{template}', [TaskTemplateController::class, 'destroy'])->whereNumber('template')->name('template.destroy');
        Route::post('/template-task/{template}/terapkan', [TaskTemplateController::class, 'apply'])->whereNumber('template')->middleware('throttle:20,1')->name('template.terapkan');

        // Penilaian akhir di akhir periode KP: diisi Company Supervisor dengan skala standar kampus
        Route::get('/penilaian', [AssessmentController::class, 'index'])->name('penilaian.index');
        Route::get('/penilaian/{internship}', [AssessmentController::class, 'edit'])->whereNumber('internship')->name('penilaian.edit');
        Route::put('/penilaian/{internship}', [AssessmentController::class, 'update'])->whereNumber('internship')->name('penilaian.update');

        Route::patch('/pantau/{internship}/posisi', [MonitorController::class, 'position'])->whereNumber('internship')->name('pantau.posisi');
        Route::patch('/pantau/{internship}/konfirmasi', [SupervisorConfirmationController::class, 'update'])->whereNumber('internship')->name('supervisor.konfirmasi');
        Route::post('/pantau/{internship}/ganti-supervisor', [ChangeRequestController::class, 'storeBySupervisor'])->whereNumber('internship')->name('perubahan.supervisor');
        // Rekomendasi jadi karyawan (rekomendasikan, tawarkan, catat hasil, batal)
        Route::post('/pantau/{internship}/rekrutmen', [ConversionController::class, 'update'])->whereNumber('internship')->middleware('throttle:30,1')->name('rekrutmen.update');
        Route::post('/pantau/{internship}/rekrutmen/tawaran', [ConversionController::class, 'offer'])->whereNumber('internship')->middleware('throttle:20,1')->name('rekrutmen.tawarkan');
        Route::patch('/pantau/{internship}/rekrutmen/batas', [ConversionController::class, 'extend'])->whereNumber('internship')->middleware('throttle:20,1')->name('rekrutmen.perpanjang');
    });

    // Tinjauan laporan per bab: Company Supervisor (bab berisi informasi perusahaan) lalu dosen pembimbing
    Route::middleware(EnsureRole::class.':supervisor,dospem')->group(function () {
        Route::get('/laporan-bab', [ReportChapterController::class, 'index'])->name('laporan.antrean');
        Route::patch('/laporan-bab/{chapter}', [ReportChapterController::class, 'review'])->whereNumber('chapter')->middleware('throttle:30,1')->name('laporan.tinjau');
    });

    // Dosen pembimbing memvalidasi penilaian akhir
    Route::middleware(EnsureRole::class.':dospem')->group(function () {
        Route::get('/validasi', [AssessmentReviewController::class, 'index'])->name('validasi.index');
        Route::get('/validasi/{internship}', [AssessmentReviewController::class, 'show'])->whereNumber('internship')->name('validasi.show');
        Route::patch('/validasi/{internship}', [AssessmentReviewController::class, 'update'])
            ->whereNumber('internship')->middleware('throttle:30,1')->name('validasi.update');

        Route::get('/antrean-bimbingan', [GuidanceController::class, 'queue'])->name('bimbingan.antrean');
        Route::post('/antrean-bimbingan/jadwal', [GuidanceController::class, 'schedule'])->middleware('throttle:30,1')->name('bimbingan.jadwal');
        Route::patch('/antrean-bimbingan/{session}', [GuidanceController::class, 'decide'])->whereNumber('session')->name('bimbingan.decide');
        Route::put('/laporan-kp/{internship}/nilai', [ReportDiscussionController::class, 'grade'])->whereNumber('internship')->middleware('throttle:30,1')->name('laporan.nilai');

        // Jadwal sidang KP mahasiswa bimbingan (nilainya disahkan kaprodi)
        Route::get('/jadwal-sidang', [DefenseScheduleController::class, 'index'])->name('dospem.sidang');
        Route::put('/jadwal-sidang/{internship}', [DefenseScheduleController::class, 'schedule'])->whereNumber('internship')->middleware('throttle:30,1')->name('dospem.sidang.jadwal');

        // Hasil akhir KP
        Route::get('/hasil', [ResultController::class, 'index'])->name('hasil.index');
        Route::get('/hasil/{internship}', [ResultController::class, 'edit'])->whereNumber('internship')->name('hasil.edit');
        Route::patch('/hasil/{internship}', [ResultController::class, 'update'])->whereNumber('internship')->name('hasil.update');
    });

    // Lowongan magang: TU Fakultas (semua perusahaan) dan Company Supervisor (perusahaannya)
    Route::middleware(EnsureRole::class.':tu,supervisor')->group(function () {
        Route::get('/lowongan', [JobPostingController::class, 'manage'])->name('lowongan.kelola');
        Route::post('/lowongan', [JobPostingController::class, 'store'])->middleware('throttle:30,1')->name('lowongan.store');
        Route::put('/lowongan/{posting}', [JobPostingController::class, 'update'])->whereNumber('posting')->name('lowongan.update');
        Route::patch('/lowongan/{posting}/status', [JobPostingController::class, 'status'])->whereNumber('posting')->name('lowongan.status');
        Route::delete('/lowongan/{posting}', [JobPostingController::class, 'destroy'])->whereNumber('posting')->name('lowongan.destroy');
    });

    // Antrean pengajuan perubahan: Company Supervisor (perpanjangan), kaprodi, TU Fakultas (ganti supervisor)
    Route::middleware(EnsureRole::class.':supervisor,kaprodi,tu')->group(function () {
        Route::get('/antrean-perubahan', [ChangeRequestController::class, 'queue'])->name('perubahan.antrean');
        Route::patch('/antrean-perubahan/{change}', [ChangeRequestController::class, 'decide'])->whereNumber('change')->name('perubahan.decide');
    });

    // ---------- TU Fakultas: aktivasi Company Supervisor ----------
    Route::middleware(EnsureRole::class.':tu')->prefix('tu')->group(function () {
        Route::get('/aktivasi', [SupervisorActivationController::class, 'index'])->name('tu.aktivasi');
        Route::post('/aktivasi/undang', [SupervisorActivationController::class, 'invite'])->middleware('throttle:30,1')->name('tu.aktivasi.undang');
        Route::post('/aktivasi/tautkan', [SupervisorActivationController::class, 'link'])->name('tu.aktivasi.tautkan');
        Route::patch('/aktivasi/email', [SupervisorActivationController::class, 'updateEmail'])->name('tu.aktivasi.email');
        Route::patch('/aktivasi/{internship}/ulang', [SupervisorConfirmationController::class, 'askAgain'])->whereNumber('internship')->name('tu.aktivasi.ulang');
        Route::patch('/aktivasi/{internship}/lepas', [SupervisorConfirmationController::class, 'release'])->whereNumber('internship')->name('tu.aktivasi.lepas');

        Route::get('/surat', [DocumentQueueController::class, 'letters'])->name('tu.surat');
        Route::patch('/surat/{document}', [DocumentQueueController::class, 'issue'])->whereNumber('document')->name('tu.surat.terbit');
    });

    // ---------- Data master ----------
    Route::middleware(EnsureRole::class.':tu,kaprodi')->group(function () {
        // Rekap KP tingkat prodi + unduh Excel
        Route::get('/rekap', [RecapController::class, 'index'])->name('rekap.index');
        Route::get('/rekap/unduh', [RecapController::class, 'export'])->middleware('throttle:20,1')->name('rekap.unduh');
        Route::get('/perusahaan', [CompanyController::class, 'index'])->name('perusahaan.index');
        Route::get('/perusahaan/{company}', [CompanyController::class, 'show'])->whereNumber('company')->name('perusahaan.show');
        Route::get('/penempatan', [PlacementController::class, 'index'])->name('penempatan.index');
    });

    // TU Fakultas juga memverifikasi perusahaan KP mandiri, memeriksa dokumen, dan mengelola data perusahaan
    Route::middleware(EnsureRole::class.':tu')->group(function () {
        Route::get('/tu/verifikasi', [VerificationController::class, 'index'])->name('tu.verifikasi');
        Route::patch('/tu/verifikasi/{internship}', [VerificationController::class, 'update'])->whereNumber('internship')->name('tu.verifikasi.update');
        Route::get('/tu/dokumen', [DocumentQueueController::class, 'uploads'])->name('tu.dokumen');
        Route::patch('/tu/dokumen/{document}', [DocumentQueueController::class, 'review'])->whereNumber('document')->name('tu.dokumen.review');

        Route::get('/perusahaan/tambah', [CompanyController::class, 'create'])->name('perusahaan.create');
        Route::post('/perusahaan', [CompanyController::class, 'store'])->name('perusahaan.store');
        Route::get('/perusahaan/{company}/ubah', [CompanyController::class, 'edit'])->whereNumber('company')->name('perusahaan.edit');
        Route::patch('/perusahaan/{company}', [CompanyController::class, 'update'])->whereNumber('company')->name('perusahaan.update');
    });

    // Kaprodi
    Route::middleware(EnsureRole::class.':kaprodi')->group(function () {
        Route::get('/kaprodi/persetujuan', [KaprodiApprovalController::class, 'index'])->name('kaprodi.persetujuan');
        Route::patch('/kaprodi/persetujuan/{internship}', [KaprodiApprovalController::class, 'update'])->whereNumber('internship')->name('kaprodi.persetujuan.update');
        Route::get('/kaprodi/bobot', [AssessmentWeightController::class, 'edit'])->name('kaprodi.bobot');
        Route::put('/kaprodi/bobot', [AssessmentWeightController::class, 'update'])->name('kaprodi.bobot.update');
        // Pengesahan Nilai KP: nilai sidang (dicatat terpisah dari nilai akhir); jadwal sidang diatur dosen pembimbing
        Route::get('/kaprodi/sidang', [DefenseController::class, 'index'])->name('kaprodi.sidang');
        // Nama, jabatan, dan tanda tangan kaprodi untuk sertifikat KP
        Route::get('/kaprodi/tanda-tangan', [KaprodiSignatureController::class, 'edit'])->name('kaprodi.ttd');
        Route::put('/kaprodi/tanda-tangan', [KaprodiSignatureController::class, 'update'])->middleware('throttle:20,1')->name('kaprodi.ttd.update');
        // Logo kampus dan logo fakultas di sertifikat KP
        Route::put('/kaprodi/tanda-tangan/logo/{kind}', [KaprodiSignatureController::class, 'logo'])->whereIn('kind', ['kampus', 'fakultas'])->middleware('throttle:20,1')->name('kaprodi.logo');
        Route::delete('/kaprodi/tanda-tangan/logo/{kind}', [KaprodiSignatureController::class, 'destroyLogo'])->whereIn('kind', ['kampus', 'fakultas'])->name('kaprodi.logo.destroy');
        Route::put('/kaprodi/sidang/{internship}', [DefenseController::class, 'update'])->whereNumber('internship')->middleware('throttle:30,1')->name('kaprodi.sidang.simpan');

        Route::get('/mahasiswa', [StudentController::class, 'index'])->name('mahasiswa.index');
        Route::post('/mahasiswa', [StudentController::class, 'store'])->name('mahasiswa.store');

        Route::get('/penempatan/tambah', [PlacementController::class, 'create'])->name('penempatan.create');
        Route::post('/penempatan', [PlacementController::class, 'store'])->name('penempatan.store');
    });

    // ---------------------------------------------------------------- Admin
    Route::middleware(EnsureRole::class.':admin')->prefix('admin')->group(function () {
        Route::get('/akun', [AdminUserController::class, 'index'])->name('admin.akun');
        Route::patch('/akun/{account}', [AdminUserController::class, 'update'])->whereNumber('account')->name('admin.akun.update');
        Route::post('/akun/{account}/sandi', [AdminUserController::class, 'resetPassword'])
            ->whereNumber('account')->middleware('throttle:20,1')->name('admin.akun.sandi');
        Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->name('log.index');

        // Identitas kampus dan daftar program studi (beserta kaprodinya)
        Route::get('/kampus', [AdminCampusController::class, 'edit'])->name('admin.kampus');
        Route::put('/kampus', [AdminCampusController::class, 'update'])->name('admin.kampus.update');
        Route::post('/kampus/prodi', [AdminCampusController::class, 'storeProgram'])->name('admin.prodi.store');
        Route::put('/kampus/prodi/{program}', [AdminCampusController::class, 'updateProgram'])->whereNumber('program')->name('admin.prodi.update');

        // Standar penilaian kampus: pertanyaan per kategori, skala nilai, dan huruf mutu
        // Identitas kampus: logo kampus untuk sertifikat
        Route::get('/penilaian', [AssessmentStandardController::class, 'index'])->name('admin.penilaian');
        Route::post('/penilaian/pertanyaan', [AssessmentStandardController::class, 'storeQuestion'])->name('admin.penilaian.pertanyaan.store');
        Route::patch('/penilaian/pertanyaan/{aspect}', [AssessmentStandardController::class, 'updateQuestion'])->whereNumber('aspect')->name('admin.penilaian.pertanyaan.update');
        Route::delete('/penilaian/pertanyaan/{aspect}', [AssessmentStandardController::class, 'destroyQuestion'])->whereNumber('aspect')->name('admin.penilaian.pertanyaan.destroy');
        Route::put('/penilaian/skala', [AssessmentStandardController::class, 'updateScale'])->name('admin.penilaian.skala');
        Route::put('/penilaian/huruf', [AssessmentStandardController::class, 'updateGrades'])->name('admin.penilaian.huruf');
    });
});
