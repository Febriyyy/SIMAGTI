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
use App\Models\JobPosting;
use App\Models\LecturerProfile;
use App\Models\LetterRequest;
use App\Models\Skill;
use App\Models\StoredFile;
use App\Models\StudentProfile;
use App\Models\StudyProgram;
use App\Models\User;
use App\Models\WeeklyMeeting;
use Database\Seeders\Concerns\SeedsActivities;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Data contoh untuk tes otomatis (dan demo cepat). Semua akun memakai kata sandi: password
 *
 * Isi (KP v2): Company Supervisor memantau, mahasiswa membuat silabus, task, dan timesheet sendiri.
 * - Rina dan Fajar: KP Industri di PT Nusantara Digital, divisi sama tetapi periode berbeda (dua kloter).
 * - Dimas: MBKM Mandiri di PT Data Prima, silabus menunggu persetujuan, 10 hari tanpa catatan.
 * - Salsa: KP selesai, nilai tervalidasi.
 * - Nadia: pengajuan KP Industri menunggu biro dan kaprodi.
 * - Tari: pengajuan disetujui, Company Supervisor belum diaktifkan TU Fakultas.
 * - Gilang: Seminar (pelaksana), dipantau dosen pembimbing.
 * - Citra: belum punya KP.
 */
class DemoSeeder extends Seeder
{
    use SeedsActivities;

    private const PROJECTS_SOFTWARE = [
        ['name' => 'Modul Pemesanan', 'key' => 'PMS', 'color' => 'green', 'tasks' => [
            ['Rancang skema database modul pesanan', 'Buat ERD dan tentukan relasi antar tabel.'],
            ['Buat halaman daftar pesanan', 'Tabel pesanan dengan pencarian dan filter status.'],
            ['Integrasi API pembayaran', 'Hubungkan modul pesanan dengan layanan pembayaran uji.'],
            ['Uji fungsi laporan', 'Uji laporan penjualan harian dan bulanan.'],
            ['Perbaiki bug filter tanggal', null],
            ['Optimasi query daftar pesanan', null],
        ]],
        ['name' => 'Dokumentasi Tim', 'key' => 'DOC', 'color' => 'blue', 'tasks' => [
            ['Tulis panduan instalasi proyek', null],
            ['Rangkuman pekerjaan bulanan', null],
            ['Dokumentasi endpoint API pesanan', null],
        ]],
    ];

    private const PROJECTS_DATA = [
        ['name' => 'Analisis Penjualan', 'key' => 'ANS', 'color' => 'amber', 'tasks' => [
            ['Bersihkan data penjualan kuartal ini', 'Hapus duplikat dan tangani nilai kosong.'],
            ['Buat visualisasi tren penjualan', 'Grafik bulanan per kategori produk.'],
            ['Susun query laporan pelanggan', 'Ringkasan pelanggan aktif dan tidak aktif.'],
            ['Validasi hasil analisis dengan tim', null],
            ['Dashboard KPI penjualan', null],
            ['Segmentasi pelanggan', null],
        ]],
    ];

    private const SYLLABUS_SOFTWARE = [
        'hard' => [['Membangun REST API dengan Laravel', 'Endpoint pesanan, autentikasi token, validasi.'], 'Menulis pengujian otomatis untuk fitur baru'],
        'soft' => ['Menyampaikan progres di daily stand-up', 'Bekerja sama dalam code review'],
        'attitude' => ['Hadir tepat waktu dan mengisi timesheet setiap hari', 'Menjaga kerahasiaan data perusahaan'],
    ];

    private const SYLLABUS_DATA = [
        'hard' => ['Mengolah data dengan Python dan SQL', 'Membuat dashboard dengan Power BI'],
        'soft' => ['Mempresentasikan temuan analisis kepada tim'],
        'attitude' => ['Teliti dan bertanggung jawab atas akurasi data'],
    ];

    public function run(): void
    {
        DB::transaction(fn () => $this->fill());
    }

    private function fill(): void
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
        $tu = $user('Wahyu Hidayat, A.Md.', 'tu@kp.test', 'tu');
        $supA = $user('Anita Wulandari', 'supervisor@kp.test', 'supervisor');
        $supB = $user('Ratna Kusumawati', 'supervisor2@kp.test', 'supervisor');

        // Mahasiswa
        $rina = $user('Rina Putri', 'mahasiswa@kp.test', 'mahasiswa', '2310511012');
        $dimas = $user('Dimas Aditya', 'dimas@kp.test', 'mahasiswa', '2310511033');
        $fajar = $user('Fajar Pratama', 'fajar@kp.test', 'mahasiswa', '2310511078');
        $salsa = $user('Salsa Nabila', 'salsa@kp.test', 'mahasiswa', '2310511045');
        $nadia = $user('Nadia Kirana', 'nadia@kp.test', 'mahasiswa', '2310511091');
        $tari = $user('Tari Anjani', 'tari@kp.test', 'mahasiswa', '2310511057');
        $gilang = $user('Gilang Ramadhan', 'gilang@kp.test', 'mahasiswa', '2310511064');
        $citra = $user('Citra Lestari', 'citra@kp.test', 'mahasiswa', '2310511101');

        $tif = StudyProgram::where('code', 'TIF')->firstOrFail();
        $si = StudyProgram::where('code', 'SI')->firstOrFail();
        $period = AcademicPeriod::current();
        $entryYear = (int) now()->subYears(3)->year; // semester 6 atau 7: memenuhi syarat minimal semester V

        // Profil kampus
        foreach ([[$kaprodi, '0412058001'], [$dospem, '0415067502']] as [$lecturer, $nidn]) {
            LecturerProfile::create(['user_id' => $lecturer->id, 'study_program_id' => $tif->id, 'nidn' => $nidn]);
        }
        foreach ([[$rina, $tif, 3.62], [$dimas, $si, 3.41], [$fajar, $tif, 3.20], [$salsa, $si, 3.75], [$nadia, $tif, 3.55],
                  [$tari, $si, 3.48], [$gilang, $tif, 3.30], [$citra, $tif, 3.66]] as [$student, $program, $gpa]) {
            StudentProfile::create(['user_id' => $student->id, 'study_program_id' => $program->id, 'entry_year' => $entryYear, 'gpa' => $gpa]);
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
        Company::create([
            'name' => 'Startup Tanikita',
            'industry' => 'Agritech',
            'address' => 'Jl. Ir. H. Juanda No. 90, Bandung',
            'status' => 'mandiri_menunggu',
        ]);

        $divNusantara = CompanyDivision::create(['company_id' => $nusantara->id, 'name' => 'Engineering']);
        $divData = CompanyDivision::create(['company_id' => $dataPrima->id, 'name' => 'Data & Analytics']);

        foreach ([[$supA, $nusantara, $divNusantara, 'Engineering Manager'], [$supB, $dataPrima, $divData, 'Head of Data']] as [$staff, $company, $division, $title]) {
            CompanyStaffProfile::create(['user_id' => $staff->id, 'company_id' => $company->id, 'division_id' => $division->id, 'job_title' => $title]);
        }

        // Penempatan magang
        $today = Carbon::today();
        $place = function (User $student, Company $company, CompanyDivision $division, User $supervisor, string $position, string $type,
                           int $startMonthsAgo, int $durationMonths, bool $finished, array $projects, array $syllabus, string $syllabusStatus,
                           int $idleDays = 0) use ($dospem, $period, $kaprodi): Internship {
            $start = Carbon::today()->subMonthsNoOverflow($startMonthsAgo);
            $end = $start->copy()->addMonthsNoOverflow($durationMonths);

            // Dibuat "berjalan" dulu supaya kegiatan bisa diisi; penempatan selesai dikunci setelahnya.
            $internship = Internship::create([
                'student_id' => $student->id,
                'company_id' => $company->id,
                'division_id' => $division->id,
                'lecturer_id' => $dospem->id,
                'academic_period_id' => $period?->id,
                'track' => 'lowongan',
                'program' => 'magang',
                'program_type' => $type,
                'position' => $position,
                'supervisor_name' => $supervisor->name,
                'supervisor_title' => $supervisor->staffProfile?->job_title,
                'supervisor_email' => $supervisor->email,
                'cadence' => 'weekly',
                'status' => 'berjalan',
                'start_date' => $start,
                'end_date' => $end,
            ]);

            $internship->assignSupervisor($supervisor);
            $this->seedSyllabus($internship, $syllabus, $syllabusStatus, $supervisor);
            $this->seedActivities($internship, $projects, $supervisor, diligence: 88, idleDays: $idleDays, until: $finished ? $end : null);

            if ($finished) {
                $internship->update(['status' => 'selesai']);
            }

            return $internship;
        };

        $magangRina = $place($rina, $nusantara, $divNusantara, $supA, 'Backend Developer Intern', 'industri', 3, 5, false, self::PROJECTS_SOFTWARE, self::SYLLABUS_SOFTWARE, 'approved');
        $magangFajar = $place($fajar, $nusantara, $divNusantara, $supA, 'Frontend Developer Intern', 'industri', 4, 5, false, self::PROJECTS_SOFTWARE, self::SYLLABUS_SOFTWARE, 'approved');
        $magangDimas = $place($dimas, $dataPrima, $divData, $supB, 'Data Analyst Intern', 'mbkm', 2, 5, false, self::PROJECTS_DATA, self::SYLLABUS_DATA, 'submitted', idleDays: 10);
        $magangSalsa = $place($salsa, $dataPrima, $divData, $supB, 'Data Analyst Intern', 'industri', 7, 5, true, self::PROJECTS_DATA, self::SYLLABUS_DATA, 'approved');

        // Pengajuan KP Industri yang masih menunggu verifikasi biro dan persetujuan kaprodi
        $magangNadia = Internship::create([
            'student_id' => $nadia->id,
            'company_id' => $karya->id,
            'academic_period_id' => $period?->id,
            'track' => 'mandiri',
            'program' => 'magang',
            'program_type' => 'industri',
            'position' => 'UI/UX Designer Intern',
            'supervisor_name' => 'Yusuf Maulana',
            'supervisor_title' => 'Kepala Studio Desain',
            'supervisor_email' => 'yusuf@karyamandiri.test',
            'supervisor_phone' => '081234567890',
            'status' => 'diajukan',
            'start_date' => $today->copy()->addDays(14),
            'end_date' => $today->copy()->addDays(14)->addMonthsNoOverflow(4),
            'notes' => 'Tempat KP dicari sendiri oleh mahasiswa.',
        ]);
        foreach ([InternshipApproval::STEP_BIRO, InternshipApproval::STEP_KAPRODI] as $step) {
            InternshipApproval::create(['internship_id' => $magangNadia->id, 'step' => $step, 'status' => 'pending']);
        }
        LetterRequest::create([
            'student_id' => $nadia->id,
            'company_id' => $karya->id,
            'purpose' => 'Surat pengantar untuk pengajuan KP mandiri.',
            'status' => 'diajukan',
        ]);

        // Pengajuan yang sudah disetujui, menunggu TU Fakultas mengaktifkan Company Supervisor baru
        $magangTari = Internship::create([
            'student_id' => $tari->id,
            'company_id' => $dataPrima->id,
            'division_id' => $divData->id,
            'lecturer_id' => $dospem->id,
            'academic_period_id' => $period?->id,
            'track' => 'mandiri',
            'program' => 'magang',
            'program_type' => 'mbkm',
            'position' => 'Business Intelligence Intern',
            'supervisor_name' => 'Hendra Gunawan',
            'supervisor_title' => 'BI Lead',
            'supervisor_email' => 'hendra.gunawan@dataprima.test',
            'status' => 'berjalan',
            'start_date' => $today->copy()->subDays(5),
            'end_date' => $today->copy()->subDays(5)->addMonthsNoOverflow(5),
        ]);
        foreach ([InternshipApproval::STEP_BIRO, InternshipApproval::STEP_KAPRODI] as $step) {
            InternshipApproval::create([
                'internship_id' => $magangTari->id, 'step' => $step, 'status' => 'approved',
                'reviewed_by' => $step === InternshipApproval::STEP_BIRO ? $biro->id : $kaprodi->id, 'reviewed_at' => now()->subDays(7),
            ]);
        }

        // Seminar (pelaksana): tanpa perusahaan, dipantau dan disetujui dosen pembimbing
        $seminarGilang = Internship::create([
            'student_id' => $gilang->id,
            'lecturer_id' => $dospem->id,
            'academic_period_id' => $period?->id,
            'track' => 'mandiri',
            'program' => 'seminar',
            'program_type' => 'pelaksana',
            'topic' => 'Seminar Nasional Transformasi Digital UMKM',
            'organizer' => 'Himpunan Mahasiswa Teknik Informatika',
            'cadence' => 'weekly',
            'status' => 'berjalan',
            'start_date' => $today->copy()->subWeeks(6),
            'end_date' => $today->copy()->subWeeks(6)->addMonthsNoOverflow(3),
        ]);
        InternshipApproval::create(['internship_id' => $seminarGilang->id, 'step' => InternshipApproval::STEP_KAPRODI, 'status' => 'approved', 'reviewed_by' => $kaprodi->id, 'reviewed_at' => now()->subWeeks(7)]);
        $this->seedSyllabus($seminarGilang, [
            'hard' => ['Menyusun rundown dan anggaran acara'],
            'soft' => ['Memimpin rapat panitia', 'Bernegosiasi dengan narasumber dan sponsor'],
            'attitude' => ['Menepati tenggat persiapan acara'],
        ], 'draft');
        $this->seedActivities($seminarGilang, [
            ['name' => 'Persiapan Seminar', 'key' => 'SEM', 'color' => 'purple', 'tasks' => [
                'Susun proposal kegiatan', 'Hubungi narasumber', 'Ajukan proposal sponsor', 'Buka pendaftaran peserta', 'Gladi bersih', 'Laporan pertanggungjawaban',
            ]],
        ], $dospem, diligence: 80);

        // Penilaian akhir, nilai sidang, sertifikat, dan konversi untuk KP yang sudah selesai
        $this->seedFinished($magangSalsa, $supB, $dospem, $kaprodi);

        // Penilaian akhir yang sedang berjalan:
        // Fajar sudah dikirim supervisor dan menunggu validasi dosen pembimbing; Dimas masih draft; Rina belum dinilai.
        $this->seedAssessment($magangFajar, $supA, ['Disiplin' => 84, 'Komunikasi' => 78, 'Kemampuan teknis' => 86, 'Kerja tim' => 80], submit: true);
        $this->seedAssessment($magangDimas, $supB, ['Disiplin' => 88, 'Komunikasi' => 82], submit: false);

        // Lowongan dari biro kerjasama
        $php = Skill::where('name', 'PHP')->first();
        $laravel = Skill::where('name', 'Laravel')->first();
        $python = Skill::where('name', 'Python')->first();
        $posting = fn (Company $company, string $title, string $status, array $skills) => tap(JobPosting::create([
            'company_id' => $company->id,
            'posted_by' => $biro->id,
            'title' => $title,
            'description' => 'Magang selama 4-6 bulan bersama tim produk.',
            'requirements' => 'Mahasiswa minimal semester V, bersedia magang penuh waktu.',
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
                'created_by' => $supA->id,
            ]);
        }
        WeeklyMeeting::create([
            'internship_id' => $magangDimas->id,
            'week_number' => $magangDimas->currentWeek(),
            'day' => 'kamis',
            'time' => '13:00',
            'url' => 'https://zoom.us/j/1234567890',
            'created_by' => $supB->id,
        ]);

        // Beberapa riwayat aktivitas agar halaman Log Aktivitas tidak kosong
        $logs = [
            [$rina, 'task.buat', 'Menambah task PMS-6 "Optimasi query daftar pesanan"', $today->copy()->subDays(2)],
            [$rina, 'bukti.kirim', 'Mengirim bukti untuk task PMS-2 "Buat halaman daftar pesanan"', $today->copy()->subDays(2)],
            [$supA, 'silabus.setujui', 'Menyetujui silabus Fajar Pratama', $today->copy()->subDay()],
            [$dimas, 'silabus.kirim', 'Mengirim silabus untuk disetujui', $today->copy()->subDay()],
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

    /** @param  array<string, int>  $scores  nama aspek => nilai (aspek yang tidak disebut dibiarkan kosong) */
    private function seedAssessment(Internship $internship, User $supervisor, array $scores, bool $submit): void
    {
        $rubric = AssessmentRubric::active();

        $assessment = Assessment::create([
            'internship_id' => $internship->id,
            'rubric_id' => $rubric->id,
            'assessor_id' => $supervisor->id,
        ]);

        foreach ($rubric->aspects as $aspect) {
            if (isset($scores[$aspect->name])) {
                $assessment->scores()->create(['rubric_aspect_id' => $aspect->id, 'score' => $scores[$aspect->name]]);
            }
        }

        $assessment->update(['final_score' => $assessment->computeFinalScore()]);

        if ($submit) {
            $assessment->submit();
        }
    }

    /** Penilaian tervalidasi, nilai sidang, sertifikat, dan rekomendasi konversi untuk satu KP selesai. */
    private function seedFinished(Internship $internship, User $supervisor, User $lecturer, User $kaprodi): void
    {
        $rubric = AssessmentRubric::active();

        $assessment = Assessment::create([
            'internship_id' => $internship->id,
            'rubric_id' => $rubric->id,
            'assessor_id' => $supervisor->id,
        ]);

        foreach ($rubric->aspects as $aspect) {
            $assessment->scores()->create([
                'rubric_aspect_id' => $aspect->id,
                'score' => ['Disiplin' => 90, 'Komunikasi' => 85, 'Kemampuan teknis' => 92, 'Kerja tim' => 88][$aspect->name] ?? 85,
            ]);
        }
        $assessment->submit();
        $assessment->validateBy($lecturer, 'Kinerja konsisten selama magang.');

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
            'recommended_by' => $supervisor->id,
            'note' => 'Nilai akhir tertinggi di divisi.',
        ]);
        $candidate->histories()->create(['to_status' => 'direkomendasikan', 'changed_by' => $supervisor->id]);
        $candidate->moveTo('ditawari', $supervisor->id, 'Ditawari posisi Junior Data Analyst.');
    }
}
