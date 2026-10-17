<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\AssessmentRubric;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\Company;
use App\Models\CompanyDivision;
use App\Models\CompanyStaffProfile;
use App\Models\ConversionCandidate;
use App\Models\DefenseScore;
use App\Models\Document;
use App\Models\DocumentFolder;
use App\Models\Internship;
use App\Models\InternshipApproval;
use App\Models\InternshipStaff;
use App\Models\Invitation;
use App\Models\JobPosting;
use App\Models\LecturerProfile;
use App\Models\LetterRequest;
use App\Models\Skill;
use App\Models\StoredFile;
use App\Models\StudentProfile;
use App\Models\StudyProgram;
use App\Models\TaskComment;
use App\Models\TaskEvidence;
use App\Models\TaskTemplate;
use App\Models\User;
use App\Models\WeeklyMeeting;
use App\Models\WeeklyTask;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Data contoh untuk demo. Semua akun memakai kata sandi: password
 */
class DemoSeeder extends Seeder
{
    private const TASKS_SOFTWARE = [
        ['Rancang skema database modul pesanan', 'Buat ERD dan tentukan relasi antar tabel.'],
        ['Buat halaman daftar pesanan', 'Tabel pesanan dengan pencarian dan filter status.'],
        ['Integrasi API pembayaran', 'Hubungkan modul pesanan dengan layanan pembayaran uji.'],
        ['Uji fungsi laporan', 'Uji laporan penjualan harian dan bulanan.'],
        ['Dokumentasi mingguan', 'Rangkum pekerjaan minggu ini dan kendala yang dihadapi.'],
    ];

    private const TASKS_DATA = [
        ['Bersihkan data penjualan kuartal ini', 'Hapus duplikat dan tangani nilai kosong.'],
        ['Buat visualisasi tren penjualan', 'Grafik bulanan per kategori produk.'],
        ['Susun query laporan pelanggan', 'Ringkasan pelanggan aktif dan tidak aktif.'],
        ['Validasi hasil analisis dengan tim', 'Presentasi singkat temuan kepada mentor.'],
        ['Dokumentasi mingguan', 'Rangkum pekerjaan minggu ini dan kendala yang dihadapi.'],
    ];

    private const COMMENTS = [
        'Sudah baik, lanjutkan.',
        'Hasilnya rapi, pertahankan konsistensinya.',
        'Tambahkan validasi input pada bagian formulir.',
    ];

    public function run(): void
    {
        $user = fn (string $name, string $email, string $role, ?string $nim = null): User => User::create([
            'name' => $name,
            'email' => $email,
            'password' => 'password',
            'role' => $role,
            'nim' => $nim,
            'is_active' => true,
        ]);

        // Staf kampus dan perusahaan
        $kaprodi = $user('Dr. Budi Santoso, M.Kom', 'kaprodi@kp.test', 'kaprodi');
        $dospem = $user('Dr. Hendra Kusuma, M.Kom', 'dospem@kp.test', 'dospem');
        $biro = $user('Siti Rahmawati, S.E.', 'biro@kp.test', 'biro');
        $mentorA = $user('Anita Wulandari', 'mentor@kp.test', 'mentor');
        $mentorB = $user('Rudi Hartono', 'mentor2@kp.test', 'mentor');
        $divisiA = $user('Bambang Prasetyo', 'divisi@kp.test', 'ketua_divisi');
        $divisiB = $user('Ratna Kusumawati', 'divisi2@kp.test', 'ketua_divisi');

        // Mahasiswa
        $rina = $user('Rina Putri', 'mahasiswa@kp.test', 'mahasiswa', '2210511012');
        $dimas = $user('Dimas Aditya', 'dimas@kp.test', 'mahasiswa', '2210511033');
        $fajar = $user('Fajar Pratama', 'fajar@kp.test', 'mahasiswa', '2210511078');
        $salsa = $user('Salsa Nabila', 'salsa@kp.test', 'mahasiswa', '2210511045');
        $nadia = $user('Nadia Kirana', 'nadia@kp.test', 'mahasiswa', '2210511091');

        $tif = StudyProgram::where('code', 'TIF')->firstOrFail();
        $si = StudyProgram::where('code', 'SI')->firstOrFail();
        $period = AcademicPeriod::current();

        // Profil kampus
        foreach ([[$kaprodi, '0412058001'], [$dospem, '0415067502']] as [$lecturer, $nidn]) {
            LecturerProfile::create(['user_id' => $lecturer->id, 'study_program_id' => $tif->id, 'nidn' => $nidn]);
        }
        foreach ([[$rina, $tif, 3.62], [$dimas, $si, 3.41], [$fajar, $tif, 3.20], [$salsa, $si, 3.75], [$nadia, $tif, 3.55]] as [$student, $program, $gpa]) {
            StudentProfile::create(['user_id' => $student->id, 'study_program_id' => $program->id, 'entry_year' => 2022, 'gpa' => $gpa]);
        }

        // Skill mahasiswa
        $skillsOf = [
            $rina->id => ['PHP', 'Laravel', 'MySQL', 'Git'],
            $fajar->id => ['JavaScript', 'React', 'Git'],
            $dimas->id => ['Python', 'SQL', 'Analisis Data'],
            $salsa->id => ['Python', 'SQL', 'Power BI'],
            $nadia->id => ['UI/UX', 'JavaScript'],
        ];
        foreach ($skillsOf as $userId => $names) {
            foreach (Skill::whereIn('name', $names)->get() as $skill) {
                User::find($userId)->skills()->attach($skill->id, ['level' => 'menengah']);
            }
        }

        // Perusahaan
        $nusantara = Company::create([
            'name' => 'PT Nusantara Digital',
            'industry' => 'Perangkat lunak',
            'address' => 'Jl. Soekarno Hatta No. 18, Bandung',
            'city' => 'Bandung',
            'email' => 'hrd@nusantaradigital.test',
            'status' => 'mitra',
            'created_by' => $biro->id,
            'verified_by' => $biro->id,
            'verified_at' => now(),
        ]);
        $dataPrima = Company::create([
            'name' => 'PT Data Prima',
            'industry' => 'Analitik data',
            'address' => 'Jl. Dipatiukur No. 4, Bandung',
            'city' => 'Bandung',
            'email' => 'hrd@dataprima.test',
            'status' => 'mitra',
            'created_by' => $biro->id,
            'verified_by' => $biro->id,
            'verified_at' => now(),
        ]);
        $karya = Company::create([
            'name' => 'CV Karya Mandiri',
            'industry' => 'Percetakan digital',
            'address' => 'Jl. Cihampelas No. 52, Bandung',
            'city' => 'Bandung',
            'status' => 'mandiri_menunggu',
            'created_by' => $nadia->id,
        ]);

        $divNusantara = CompanyDivision::create(['company_id' => $nusantara->id, 'name' => 'Engineering']);
        $divData = CompanyDivision::create(['company_id' => $dataPrima->id, 'name' => 'Data & Analytics']);

        foreach ([[$mentorA, $nusantara, $divNusantara, 'Senior Developer'], [$divisiA, $nusantara, $divNusantara, 'Engineering Manager'],
                  [$mentorB, $dataPrima, $divData, 'Data Analyst Lead'], [$divisiB, $dataPrima, $divData, 'Head of Data']] as [$staff, $company, $division, $title]) {
            CompanyStaffProfile::create(['user_id' => $staff->id, 'company_id' => $company->id, 'division_id' => $division->id, 'job_title' => $title]);
        }
        Company::create([
            'name' => 'Startup Tanikita',
            'industry' => 'Agritech',
            'address' => 'Jl. Ir. H. Juanda No. 90, Bandung',
            'status' => 'mandiri_menunggu',
        ]);

        // Penempatan magang
        $today = Carbon::today();
        $place = function (User $student, Company $company, CompanyDivision $division, User $mentor, User $divisi, int $startMonthsAgo, int $durationMonths, string $finalStatus, array $tasks) use ($dospem, $period): Internship {
            $start = Carbon::today()->subMonthsNoOverflow($startMonthsAgo);
            $end = $start->copy()->addMonthsNoOverflow($durationMonths);

            // Dibuat "berjalan" dulu supaya task bisa diisi; penempatan selesai dikunci setelahnya.
            $internship = Internship::create([
                'student_id' => $student->id,
                'company_id' => $company->id,
                'division_id' => $division->id,
                'lecturer_id' => $dospem->id,
                'academic_period_id' => $period?->id,
                'track' => 'lowongan',
                'status' => 'berjalan',
                'start_date' => $start,
                'end_date' => $end,
            ]);

            $internship->assignStaff($mentor, InternshipStaff::ROLE_MENTOR);
            $internship->assignStaff($divisi, InternshipStaff::ROLE_SUPERVISOR);

            $this->seedTasks($internship, $mentor, $tasks, $finalStatus === 'selesai');

            if ($finalStatus === 'selesai') {
                $internship->update(['status' => 'selesai']);
            }

            return $internship;
        };

        $magangRina = $place($rina, $nusantara, $divNusantara, $mentorA, $divisiA, 3, 5, 'berjalan', self::TASKS_SOFTWARE);
        $magangFajar = $place($fajar, $nusantara, $divNusantara, $mentorA, $divisiA, 4, 5, 'berjalan', self::TASKS_SOFTWARE);
        $magangDimas = $place($dimas, $dataPrima, $divData, $mentorB, $divisiB, 2, 4, 'berjalan', self::TASKS_DATA);
        $magangSalsa = $place($salsa, $dataPrima, $divData, $mentorB, $divisiB, 6, 5, 'selesai', self::TASKS_DATA);

        // KP mandiri yang masih menunggu verifikasi biro dan persetujuan kaprodi
        $magangNadia = Internship::create([
            'student_id' => $nadia->id,
            'company_id' => $karya->id,
            'lecturer_id' => $dospem->id,
            'academic_period_id' => $period?->id,
            'track' => 'mandiri',
            'status' => 'diajukan',
            'start_date' => $today->copy()->addDays(14),
            'end_date' => $today->copy()->addDays(14)->addMonthsNoOverflow(4),
            'notes' => 'Tempat KP dicari sendiri oleh mahasiswa.',
        ]);
        foreach ([InternshipApproval::STEP_BIRO, InternshipApproval::STEP_KAPRODI] as $step) {
            InternshipApproval::create(['internship_id' => $magangNadia->id, 'step' => $step, 'status' => 'pending']);
        }
        Invitation::create([
            'email' => 'mentor@karyamandiri.test',
            'role' => 'mentor',
            'company_id' => $karya->id,
            'internship_id' => $magangNadia->id,
            'invited_by' => $nadia->id,
            'expires_at' => now()->addDays(14),
        ]);
        LetterRequest::create([
            'student_id' => $nadia->id,
            'company_id' => $karya->id,
            'purpose' => 'Surat pengantar untuk pengajuan KP mandiri.',
            'status' => 'diajukan',
        ]);

        // Penilaian akhir, nilai sidang, sertifikat, dan konversi untuk KP yang sudah selesai
        $this->seedFinished($magangSalsa, $mentorB, $divisiB, $kaprodi);

        // Template task milik mentor
        $template = TaskTemplate::create([
            'owner_id' => $mentorA->id,
            'company_id' => $nusantara->id,
            'name' => 'Onboarding developer backend',
            'description' => 'Dua minggu pertama untuk mahasiswa baru di tim engineering.',
            'is_shared' => true,
        ]);
        foreach ([[1, 'senin', 'Siapkan lingkungan kerja', 'Pasang PHP, Composer, dan Git di laptop.'],
                  [1, 'selasa', 'Pelajari struktur proyek', 'Baca dokumentasi internal dan kode modul pesanan.'],
                  [1, 'jumat', 'Dokumentasi mingguan', 'Rangkum hal yang dipelajari minggu ini.'],
                  [2, 'senin', 'Kerjakan perbaikan kecil', 'Ambil satu tiket tingkat mudah dari backlog.']] as $i => [$week, $day, $title, $description]) {
            $template->items()->create(['week_number' => $week, 'day' => $day, 'title' => $title, 'description' => $description, 'sort_order' => $i]);
        }

        // Lowongan dari biro kerjasama
        $php = Skill::where('name', 'PHP')->first();
        $laravel = Skill::where('name', 'Laravel')->first();
        $python = Skill::where('name', 'Python')->first();
        $posting = fn (Company $company, string $title, string $status, array $skills) => tap(JobPosting::create([
            'company_id' => $company->id,
            'posted_by' => $biro->id,
            'title' => $title,
            'description' => 'Magang selama 4-6 bulan bersama tim produk.',
            'requirements' => 'Mahasiswa semester 6 atau lebih, bersedia magang penuh waktu.',
            'location' => 'Bandung',
            'quota' => 3,
            'deadline' => Carbon::today()->addMonth(),
            'status' => $status,
            'published_at' => $status === 'published' ? now() : null,
        ]), fn (JobPosting $job) => $job->skills()->sync(array_filter(array_map(fn ($s) => $s?->id, $skills))));
        $posting($nusantara, 'Magang Backend Developer', 'published', [$php, $laravel]);
        $posting($dataPrima, 'Magang Data Analyst', 'published', [$python]);
        $posting($dataPrima, 'Magang Data Engineer', 'draft', [$python]);

        // Template dokumen dari kaprodi (berkasnya belum diunggah sungguhan; hanya data contoh)
        $folderSurat = DocumentFolder::create(['name' => 'Template Surat', 'created_by' => $kaprodi->id]);
        $folderLaporan = DocumentFolder::create(['name' => 'Template Laporan', 'created_by' => $kaprodi->id]);
        foreach ([[$folderSurat, 'Format surat pengantar KP', 'template_surat', 'surat-pengantar.docx'],
                  [$folderLaporan, 'Template laporan akhir KP', 'template_laporan', 'laporan-akhir-kp.docx']] as [$folder, $title, $type, $name]) {
            $file = StoredFile::create([
                'disk' => 'local',
                'path' => 'contoh/'.$name,
                'original_name' => $name,
                'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'size' => 48 * 1024,
                'uploaded_by' => $kaprodi->id,
            ]);
            Document::create(['folder_id' => $folder->id, 'file_id' => $file->id, 'title' => $title, 'type' => $type, 'uploaded_by' => $kaprodi->id]);
        }

        // Meeting minggu ini: Rina dan Fajar satu jadwal, Dimas jadwal sendiri
        foreach ([$magangRina, $magangFajar] as $magang) {
            WeeklyMeeting::create([
                'internship_id' => $magang->id,
                'week_number' => $magang->currentWeek(),
                'day' => 'rabu',
                'time' => '10:00',
                'url' => 'https://meet.google.com/abc-defg-hij',
                'created_by' => $mentorA->id,
            ]);
        }
        WeeklyMeeting::create([
            'internship_id' => $magangDimas->id,
            'week_number' => $magangDimas->currentWeek(),
            'day' => 'kamis',
            'time' => '13:00',
            'url' => 'https://zoom.us/j/1234567890',
            'created_by' => $mentorB->id,
        ]);

        // Beberapa riwayat aktivitas agar halaman Log Aktivitas tidak kosong
        $logs = [
            [$mentorA, 'task.buat', 'Menambah task "Integrasi API pembayaran" untuk Rina Putri', $today->copy()->subDays(2)],
            [$rina, 'bukti.kirim', 'Mengirim bukti untuk task "Buat halaman daftar pesanan"', $today->copy()->subDays(2)],
            [$mentorA, 'task.ubah', 'Mengubah task "Buat halaman daftar pesanan" menjadi Completed', $today->copy()->subDay()],
            [$mentorB, 'task.ubah', 'Mengubah task "Bersihkan data penjualan kuartal ini" menjadi Completed', $today->copy()->subDay()],
        ];
        foreach ($logs as [$actor, $action, $description, $when]) {
            $log = ActivityLog::create([
                'user_id' => $actor->id,
                'action' => $action,
                'description' => $description,
            ]);
            $log->forceFill(['created_at' => $when->copy()->setTime(9, 30), 'updated_at' => $when->copy()->setTime(9, 30)])->save();
        }
    }

    /** Isi task untuk 4 minggu terakhir (atau sampai minggu berjalan), lengkap dengan bukti dan komentar. */
    private function seedTasks(Internship $internship, User $mentor, array $set, bool $finished): void
    {
        $current = $internship->currentWeek();
        $days = array_keys(WeeklyTask::DAYS);

        for ($week = max(1, $current - 3); $week <= $current; $week++) {
            foreach ($days as $i => $day) {
                [$title, $description] = $set[($i + $week) % count($set)];

                $past = $finished || $week < $current;
                $state = $past
                    ? 'completed'
                    : ($i < 2 ? 'completed' : ($i === 2 ? 'in_progress' : 'todo'));
                $done = $state === 'completed';

                $task = WeeklyTask::create([
                    'internship_id' => $internship->id,
                    'week_number' => $week,
                    'day' => $day,
                    'title' => $title,
                    'description' => $description,
                    'status' => $state,
                    'created_by' => $mentor->id,
                ]);

                if ($done) {
                    TaskEvidence::create([
                        'weekly_task_id' => $task->id,
                        'submitted_by' => $internship->student_id,
                        'type' => 'link',
                        'url' => 'https://github.com/contoh/proyek/pull/'.($week * 10 + $i),
                    ]);
                }

                if ($done && ($past || $i === 0)) {
                    TaskComment::create([
                        'weekly_task_id' => $task->id,
                        'user_id' => $mentor->id,
                        'body' => self::COMMENTS[($week + $i) % count(self::COMMENTS)],
                    ]);
                }
            }
        }
    }

    /** Penilaian tervalidasi, nilai sidang, sertifikat, dan rekomendasi konversi untuk satu KP selesai. */
    private function seedFinished(Internship $internship, User $mentor, User $divisi, User $kaprodi): void
    {
        $rubric = AssessmentRubric::active();

        $assessment = Assessment::create([
            'internship_id' => $internship->id,
            'rubric_id' => $rubric->id,
            'assessor_id' => $mentor->id,
        ]);

        foreach ($rubric->aspects as $aspect) {
            $assessment->scores()->create([
                'rubric_aspect_id' => $aspect->id,
                'score' => ['Disiplin' => 90, 'Komunikasi' => 85, 'Kemampuan teknis' => 92, 'Kerja tim' => 88][$aspect->name] ?? 85,
            ]);
        }
        $assessment->submit();
        $assessment->validateBy($divisi, 'Kinerja konsisten selama magang.');

        DefenseScore::create([
            'internship_id' => $internship->id,
            'score' => 86,
            'defended_at' => Carbon::today()->subWeeks(2),
            'entered_by' => $kaprodi->id,
        ]);

        $template = CertificateTemplate::create([
            'scope' => 'kampus',
            'name' => 'Sertifikat KP kampus',
            'signer_name' => $kaprodi->name,
            'signer_title' => 'Ketua Program Studi',
            'created_by' => $kaprodi->id,
        ]);
        Certificate::create([
            'internship_id' => $internship->id,
            'template_id' => $template->id,
            'number' => Certificate::nextNumber(),
            'status' => 'terbit',
            'issued_by' => $kaprodi->id,
            'issued_at' => now(),
        ]);

        $candidate = ConversionCandidate::create([
            'internship_id' => $internship->id,
            'status' => 'direkomendasikan',
            'recommended_by' => $divisi->id,
            'note' => 'Nilai akhir tertinggi di divisi.',
        ]);
        $candidate->histories()->create(['to_status' => 'direkomendasikan', 'changed_by' => $divisi->id]);
        $candidate->moveTo('ditawari', $divisi->id, 'Ditawari posisi Junior Data Analyst.');
    }
}
