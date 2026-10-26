<?php

namespace Database\Seeders;

use App\Models\AcademicPeriod;
use App\Models\ActivityLog;
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
use App\Models\InternshipDocument;
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
use Database\Seeders\Concerns\SeedsStageTwo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Data bawaan aplikasi: orang, perusahaan, dan aktivitas yang tampak seperti data sungguhan
 * (semuanya fiktif). Tanggal dihitung dari hari ini, jadi data selalu terlihat "sedang berjalan".
 *
 * v3: satu jenis KP. Mahasiswa menyusun rencana kegiatan (timesheet / Gantt), goals, task, dan catatan harian;
 * Company Supervisor memantau dan menilai di akhir periode KP dengan standar penilaian kampus.
 *
 * Daftar akun ada di docs/AKUN.md. Semua akun memakai kata sandi self::PASSWORD.
 * Data untuk tes otomatis ada di DemoSeeder (dipanggil lewat TestingSeeder), bukan di sini.
 */
class DataSeeder extends Seeder
{
    use SeedsActivities;
    use SeedsStageTwo;

    public const PASSWORD = 'Simagti2026';

    /** Domain email kampus (fiktif). Ganti di sini jika ingin memakai domain kampus Anda. */
    private const CAMPUS = 'kampus.ac.id';

    private const STUDENT = 'mhs.kampus.ac.id';

    /** Proyek dan task per bidang (dibuat mahasiswa sendiri di board kegiatan). */
    private const PROJECTS = [
        'backend' => [
            ['name' => 'Modul Pemesanan', 'key' => 'ORD', 'color' => 'green', 'tasks' => [
                ['Rancang skema tabel modul pemesanan', 'Buat ERD dan migrasi untuk tabel pesanan, item, dan pembayaran.'],
                ['Buat endpoint daftar pesanan', 'REST API dengan filter status dan paginasi.'],
                ['Tulis unit test service pembayaran', 'Minimal cakupan 80% untuk PaymentService.'],
                ['Integrasi webhook payment gateway', 'Tangani notifikasi sukses, gagal, dan kedaluwarsa.'],
                ['Optimasi query laporan penjualan', 'Kurangi waktu muat laporan bulanan di bawah 2 detik.'],
                ['Perbaiki bug validasi alamat pengiriman', 'Tiket #482 dari tim QA.'],
                ['Endpoint ekspor pesanan ke CSV', null],
            ]],
            ['name' => 'Dokumentasi & Review', 'key' => 'DOC', 'color' => 'blue', 'tasks' => [
                ['Code review bersama supervisor', 'Diskusikan masukan pada pull request.'],
                ['Tulis panduan setup proyek', 'README untuk anggota tim baru.'],
                ['Rangkuman pekerjaan bulanan', 'Kendala, hasil, dan rencana bulan berikutnya.'],
            ]],
        ],
        'frontend' => [
            ['name' => 'Toko Online', 'key' => 'WEB', 'color' => 'green', 'tasks' => [
                ['Implementasi halaman katalog produk', 'Sesuai desain Figma versi 3, responsif sampai 360px.'],
                ['Komponen keranjang belanja', 'Tambah, ubah jumlah, dan hapus item dengan state global.'],
                ['Integrasi API autentikasi', 'Login, refresh token, dan logout.'],
                ['Perbaiki aksesibilitas form checkout', 'Label, urutan fokus, dan pesan galat yang jelas.'],
                ['Optimasi ukuran bundle', 'Terapkan lazy loading untuk halaman admin.'],
                ['Halaman riwayat pesanan', null],
            ]],
            ['name' => 'QA Perangkat', 'key' => 'QA', 'color' => 'amber', 'tasks' => [
                ['Uji tampilan di berbagai perangkat', 'Catat temuan di lembar QA.'],
                ['Rangkuman pekerjaan bulanan', null],
            ]],
        ],
        'data' => [
            ['name' => 'Analitik Pelanggan', 'key' => 'ANL', 'color' => 'amber', 'tasks' => [
                ['Bersihkan data transaksi kuartal III', 'Tangani nilai kosong, duplikat, dan format tanggal.'],
                ['Analisis retensi pelanggan', 'Hitung cohort retention per bulan pendaftaran.'],
                ['Segmentasi pelanggan dengan RFM', 'Kelompokkan pelanggan dan beri rekomendasi tindak lanjut.'],
                ['Validasi hasil analisis dengan tim bisnis', 'Presentasi singkat temuan utama.'],
            ]],
            ['name' => 'Dashboard Penjualan', 'key' => 'DSB', 'color' => 'blue', 'tasks' => [
                ['Buat dashboard penjualan regional', 'Power BI: omzet, margin, dan tren per provinsi.'],
                ['Tulis query SQL laporan mingguan', 'Otomatiskan laporan yang selama ini dibuat manual.'],
                ['Rangkuman pekerjaan bulanan', null],
            ]],
        ],
        'network' => [
            ['name' => 'Jaringan Cabang', 'key' => 'NET', 'color' => 'blue', 'tasks' => [
                ['Inventarisasi perangkat jaringan kantor cabang', 'Catat tipe, IP, dan versi firmware.'],
                ['Konfigurasi VLAN untuk lantai 3', 'Pisahkan jaringan tamu, staf, dan server.'],
                ['Uji failover koneksi ISP', 'Simulasikan putusnya link utama dan catat waktu pulih.'],
                ['Dokumentasikan prosedur backup konfigurasi', 'SOP backup harian router dan switch.'],
            ]],
            ['name' => 'Monitoring', 'key' => 'MON', 'color' => 'purple', 'tasks' => [
                ['Pantau trafik dengan Zabbix', 'Siapkan alert untuk penggunaan bandwidth di atas 80%.'],
                ['Containerisasi aplikasi monitoring internal', 'Buat Dockerfile dan docker-compose.'],
                ['Rangkuman pekerjaan bulanan', null],
            ]],
        ],
        'design' => [
            ['name' => 'Aplikasi Kasir', 'key' => 'POS', 'color' => 'pink', 'tasks' => [
                ['Riset pengguna aplikasi kasir', 'Wawancara 5 pemilik toko dan rangkum temuan.'],
                ['Buat user flow pemesanan', 'Dari pilih produk sampai pembayaran berhasil.'],
                ['Wireframe halaman dashboard toko', 'Versi low-fidelity untuk desktop dan ponsel.'],
                ['Prototipe interaktif alur checkout', 'Siap diuji dengan pengguna.'],
                ['Uji kegunaan prototipe', 'Catat waktu penyelesaian dan kendala tiap peserta.'],
            ]],
            ['name' => 'Design System', 'key' => 'DS', 'color' => 'purple', 'tasks' => [
                ['Desain sistem komponen tombol dan form', 'Variasi ukuran, status, dan mode gelap.'],
                ['Rangkuman pekerjaan bulanan', null],
            ]],
        ],
    ];

    /** Goals mahasiswa per bidang (halaman Kegiatan). */
    private const GOALS = [
        'backend' => ['Membangun REST API dengan Laravel', 'Menulis pengujian otomatis (unit dan feature test)', 'Menerima dan menindaklanjuti code review', 'Disiplin mengisi catatan kegiatan setiap hari'],
        'frontend' => ['Membangun antarmuka React yang responsif', 'Menerapkan standar aksesibilitas WCAG', 'Tepat waktu menyelesaikan tiket sprint'],
        'data' => ['Mengolah data dengan Python dan SQL', 'Membuat dashboard Power BI', 'Mempresentasikan temuan kepada tim bisnis'],
        'network' => ['Mengonfigurasi VLAN dan routing', 'Memantau jaringan dengan Zabbix', 'Menulis SOP yang mudah diikuti'],
        'design' => ['Melakukan riset pengguna', 'Membuat prototipe interaktif di Figma', 'Terbuka terhadap masukan desain'],
    ];

    private ?AcademicPeriod $period = null;

    public function run(): void
    {
        DB::transaction(fn () => $this->fill());
    }

    private function fill(): void
    {
        $this->period = AcademicPeriod::current();
        $tif = StudyProgram::where('code', 'TIF')->firstOrFail();
        $si = StudyProgram::where('code', 'SI')->firstOrFail();

        // ---------- Kampus ----------
        $kaprodi = $this->person('Dr. Arief Rahman Hakim, S.Kom., M.T.', 'arief.hakim@'.self::CAMPUS, 'kaprodi', phone: '081220456781');
        $dewi = $this->person('Dewi Lestari Wulandari, S.Kom., M.Kom.', 'dewi.lestari@'.self::CAMPUS, 'dospem', phone: '081321779054');
        $bambang = $this->person('Ir. Bambang Sutrisno, M.T.', 'bambang.sutrisno@'.self::CAMPUS, 'dospem', phone: '081394112730');
        $rizky = $this->person('Rizky Aditya Pratama, S.T., M.Cs.', 'rizky.pratama@'.self::CAMPUS, 'dospem', phone: '085722318846');
        $admin = $this->person('Wulan Sari Anggraini, S.E.', 'wulan.anggraini@'.self::CAMPUS, 'admin', phone: '082118804512');
        $tu = $this->person('Retno Kusumaningrum, A.Md.', 'retno.kusumaningrum@'.self::CAMPUS, 'tu', phone: '081903372215');

        foreach ([[$kaprodi, '0415078201'], [$dewi, '0422118702'], [$bambang, '0003066804'], [$rizky, '0419099003']] as [$lecturer, $nidn]) {
            LecturerProfile::create(['user_id' => $lecturer->id, 'study_program_id' => $tif->id, 'nidn' => $nidn]);
        }

        // ---------- Perusahaan dan Company Supervisor ----------
        $cakrawala = $this->company('PT Cakrawala Solusi Digital', 'Teknologi dan IT', 'Jl. Asia Afrika No. 112', 'Bandung', 'cakrawaladigital.co.id', '0224231178', $tu);
        $arunika = $this->company('PT Arunika Data Analitika', 'Teknologi dan IT', 'Jl. TB Simatupang Kav. 18', 'Jakarta Selatan', 'arunikadata.co.id', '0217806624', $tu);
        $sinergi = $this->company('PT Sinergi Telematika Nusantara', 'Teknologi dan IT', 'Jl. Raya Darmo No. 45', 'Surabaya', 'sinergitelematika.co.id', '0315678210', $tu);
        $kreasi = $this->company('CV Kreasi Visual Mandala', 'Desain dan multimedia', 'Jl. Kaliurang Km 5,6', 'Sleman', 'kreasivisual.id', '0274889132', $tu);

        $divCakrawala = CompanyDivision::create(['company_id' => $cakrawala->id, 'name' => 'Product Engineering']);
        $divArunika = CompanyDivision::create(['company_id' => $arunika->id, 'name' => 'Data & Insight']);
        $divSinergi = CompanyDivision::create(['company_id' => $sinergi->id, 'name' => 'Network Operations']);
        $divKreasi = CompanyDivision::create(['company_id' => $kreasi->id, 'name' => 'Creative']);

        $andika = $this->staff('Andika Saputra', $cakrawala, $divCakrawala, 'Engineering Manager');
        $maya = $this->staff('Maya Kartika Sari', $cakrawala, $divCakrawala, 'Frontend Lead');
        $fitri = $this->staff('Fitri Handayani', $arunika, $divArunika, 'Lead Data Analyst');
        $agus = $this->staff('Agus Setiawan', $sinergi, $divSinergi, 'NOC Manager');
        $galih = $this->staff('Galih Permana', $kreasi, $divKreasi, 'Creative Director');

        // ---------- Mahasiswa (angkatan 2023: semester 7, memenuhi syarat minimal semester V) ----------
        $s = fn (string $name, string $nim, StudyProgram $program, float $gpa, array $skills) => $this->student($name, $nim, $program, $gpa, $skills);
        $nabila = $s('Nabila Azzahra Putri', '2310511021', $tif, 3.71, ['PHP', 'Laravel', 'MySQL', 'Git']);
        $rafi = $s('Muhammad Rafi Alfarizi', '2310511034', $tif, 3.48, ['JavaScript', 'React', 'Git']);
        $aulia = $s('Aulia Rahmawati', '2310512007', $si, 3.22, ['PHP', 'Laravel', 'UI/UX']);
        $dimas = $s('Dimas Arya Wicaksono', '2310511052', $tif, 3.35, ['Python', 'SQL', 'Analisis Data']);
        $salsabila = $s('Salsabila Khairunnisa', '2310512015', $si, 3.82, ['Python', 'SQL', 'Power BI']);
        $fajar = $s('Fajar Nugraha Ramadhan', '2310512029', $si, 3.40, ['SQL', 'Power BI', 'Analisis Data']);
        $bagas = $s('Bagas Satria Putra', '2310511066', $tif, 3.28, ['Java', 'Docker', 'Git']);
        $annisa = $s('Annisa Fitriani', '2310511073', $tif, 3.57, ['Python', 'Docker']);
        $raka = $s('Raka Mahendra', '2310511018', $tif, 3.44, ['Java', 'SQL']);
        $putri = $s('Putri Ayu Lestari', '2310512041', $si, 3.63, ['UI/UX', 'JavaScript']);
        $kevin = $s('Kevin Pratama Halim', '2310511087', $tif, 3.31, ['UI/UX', 'Vue.js']);
        $zahra = $s('Zahra Amalia', '2310512053', $si, 3.52, ['Flutter', 'JavaScript']);
        $ilham = $s('Ilham Maulana Yusuf', '2310511095', $tif, 3.39, ['PHP', 'Laravel', 'MySQL']);
        $yudha = $s('Yudha Pradana', '2310511044', $tif, 3.25, ['JavaScript']);
        $laras = $s('Larasati Wening', '2310512068', $si, 3.58, ['UI/UX']);
        $farhan = $s('Farhan Akbar Siregar', '2310511102', $tif, 3.36, ['Java', 'SQL']);
        $tiara = $s('Tiara Maharani', '2310512077', $si, 3.61, ['SQL', 'Analisis Data']);
        $s('Citra Kirana Dewi', '2310512062', $si, 3.66, ['Analisis Data', 'Power BI']); // belum KP

        // ---------- KP yang berjalan dan selesai ----------
        // [mahasiswa, perusahaan, divisi, supervisor, dospem, posisi, mulai (hari lalu), durasi (bulan), bidang, kerajinan %, goals tercapai]
        // Penilaian akhir dibuka 14 hari sebelum KP berakhir: Rafi, Kevin, dan Fajar sedang dalam masa penilaian.
        $p = fn (...$a) => $this->placement(...$a);
        $kpNabila = $p($nabila, $cakrawala, $divCakrawala, $andika, $dewi, 'Backend Developer Intern', 95, 5, 'backend', 94);
        $kpAulia = $p($aulia, $cakrawala, $divCakrawala, $andika, $dewi, 'Backend Developer Intern', 95, 5, 'backend', 52);
        $kpRafi = $p($rafi, $cakrawala, $divCakrawala, $maya, $dewi, 'Frontend Developer Intern', 140, 5, 'frontend', 88);
        $kpDimas = $p($dimas, $arunika, $divArunika, $fitri, $bambang, 'Data Analyst Intern', 70, 5, 'data', 80, idleDays: 11);
        $kpSalsabila = $p($salsabila, $arunika, $divArunika, $fitri, $bambang, 'Data Analyst Intern', 190, 5, 'data', 97, finished: true);
        $kpFajar = $p($fajar, $arunika, $divArunika, $fitri, $bambang, 'Business Intelligence Intern', 112, 4, 'data', 85);
        $kpBagas = $p($bagas, $sinergi, $divSinergi, $agus, $rizky, 'Network Engineer Intern', 88, 5, 'network', 78);
        $kpAnnisa = $p($annisa, $sinergi, $divSinergi, $agus, $rizky, 'DevOps Intern', 33, 5, 'network', 90);
        $kpRaka = $p($raka, $sinergi, $divSinergi, $agus, $rizky, 'Network Engineer Intern', 215, 5, 'network', 91, finished: true);
        $kpPutri = $p($putri, $kreasi, $divKreasi, $galih, $dewi, 'UI/UX Designer Intern', 58, 4, 'design', 92);
        $kpKevin = $p($kevin, $kreasi, $divKreasi, $galih, $dewi, 'UI/UX Designer Intern', 145, 5, 'design', 74);
        $kpYudha = $p($yudha, $cakrawala, $divCakrawala, $maya, $rizky, 'Frontend Developer Intern', 40, 4, 'frontend', 82);
        // Laras baru mulai: belum menyusun rencana kegiatan, jadi belum bisa membuat task
        $kpLaras = $p($laras, $kreasi, $divKreasi, $galih, $bambang, 'Graphic Designer Intern', 5, 4, 'design', 0, activities: false);

        // ---------- Pengajuan mandiri ----------
        // Zahra: baru diajukan, menunggu verifikasi TU Fakultas
        $lumbung = Company::create([
            'name' => 'PT Lumbung Tani Teknologi', 'industry' => 'Teknologi dan IT', 'address' => 'Jl. Soekarno-Hatta No. 9', 'city' => 'Malang',
            'email' => 'karier@lumbungtani.id', 'status' => 'mandiri_menunggu', 'created_by' => $zahra->id,
        ]);
        $this->independent($zahra, $lumbung, null, 'Mobile Developer Intern', 'Rahmat Hidayat', 'Head of Mobile', 'rahmat.hidayat@lumbungtani.id', verifier: null, kaprodi: null);
        LetterRequest::create(['student_id' => $zahra->id, 'company_id' => $lumbung->id, 'purpose' => 'Surat pengantar untuk pengajuan KP di PT Lumbung Tani Teknologi.', 'status' => 'diajukan']);

        // Ilham: sudah diverifikasi TU Fakultas, menunggu persetujuan kaprodi
        $medika = Company::create([
            'name' => 'PT Medika Integrasi Sistem', 'industry' => 'Kesehatan', 'address' => 'Jl. Pemuda No. 150', 'city' => 'Semarang',
            'email' => 'hrd@medikaintegrasi.co.id', 'phone' => '0248315527', 'status' => 'mandiri_terverifikasi',
            'created_by' => $ilham->id, 'verified_by' => $tu->id, 'verified_at' => now()->subDays(2),
        ]);
        $this->independent($ilham, $medika, null, 'Software Engineer Intern', 'Sri Wahyuni', 'IT Manager', 'sri.wahyuni@medikaintegrasi.co.id', verifier: $tu, kaprodi: null);

        // Farhan dan Tiara: satu surat pengajuan, sudah disetujui; TU Fakultas belum mengaktifkan Company Supervisor-nya
        $garuda = Company::create([
            'name' => 'PT Garuda Logistik Nusantara', 'industry' => 'Perdagangan', 'address' => 'Jl. Raya Bekasi Km 21', 'city' => 'Jakarta Timur',
            'email' => 'people@garudalogistik.co.id', 'status' => 'mandiri_terverifikasi',
            'created_by' => $farhan->id, 'verified_by' => $tu->id, 'verified_at' => now()->subDays(9),
        ]);
        $divGaruda = CompanyDivision::create(['company_id' => $garuda->id, 'name' => 'IT Solutions']);
        foreach ([[$farhan, 'Backend Developer Intern'], [$tiara, 'Data Analyst Intern']] as [$student, $position]) {
            $this->independent($student, $garuda, $divGaruda, $position, 'Bayu Prakoso', 'IT Solutions Manager', 'bayu.prakoso@garudalogistik.co.id',
                verifier: $tu, kaprodi: $kaprodi, lecturer: $rizky);
        }

        // ---------- Penilaian akhir (skala standar kampus: 95 sangat baik, 85 baik, 75 cukup) ----------
        $this->finish($kpSalsabila, $fitri, $bambang, $kaprodi, [95, 95, 85, 95, 85], 88, 'Analisis rapi dan inisiatif tinggi.', ['ditawari', 'Junior Data Analyst']);
        $this->finish($kpRaka, $agus, $rizky, $kaprodi, [85, 85, 75, 95, 85], 81, 'Teliti dalam dokumentasi jaringan.', ['direkomendasikan', null]);
        $this->seedAssessment($kpRafi, $maya, [85, 95, 85, 85, 75], submit: true, comment: 'Kode rapi dan cepat beradaptasi dengan tim.');
        $this->seedAssessment($kpKevin, $galih, [85, 75, 85, 95], submit: true, comment: 'Riset pengguna dikerjakan dengan baik.');
        $this->seedAssessment($kpFajar, $fitri, [85, 95], submit: false, answered: 5);

        // ---------- Meeting minggu ini ----------
        $this->meeting([$kpNabila, $kpAulia], $andika, 'rabu', '10:00', 'https://meet.google.com/qyt-hmsk-pwa');
        $this->meeting([$kpRafi], $maya, 'kamis', '14:00', 'https://meet.google.com/bnr-xkze-rto');
        $this->meeting([$kpDimas, $kpFajar], $fitri, 'selasa', '09:30', 'https://us06web.zoom.us/j/84123657790');
        $this->meeting([$kpBagas, $kpAnnisa], $agus, 'senin', '13:00', 'https://meet.google.com/ovd-tjqa-mke');
        $this->meeting([$kpPutri, $kpKevin], $galih, 'jumat', '10:00', 'https://meet.google.com/zfe-pwkn-hyc');

        // ---------- Pendukung: lowongan, dokumen ----------
        $skill = fn (string $name) => Skill::where('name', $name)->value('id');
        $posting = function (Company $company, string $title, string $city, int $quota, string $status, array $skills) use ($tu, $skill) {
            $job = JobPosting::create([
                'company_id' => $company->id, 'posted_by' => $tu->id, 'title' => $title,
                'description' => 'Magang 4 sampai 6 bulan bersama tim, dibimbing Company Supervisor dan mengerjakan proyek nyata.',
                'requirements' => 'Mahasiswa aktif minimal semester V, IPK minimal 3,00, bersedia magang penuh waktu.',
                'location' => $city, 'quota' => $quota, 'deadline' => Carbon::today()->addWeeks(5),
                'status' => $status, 'published_at' => $status === 'published' ? now()->subDays(6) : null,
            ]);
            $job->skills()->sync(array_filter(array_map($skill, $skills)));
        };
        $posting($cakrawala, 'Magang Backend Engineer (Laravel)', 'Bandung', 3, 'published', ['PHP', 'Laravel', 'MySQL']);
        $posting($cakrawala, 'Magang Frontend Engineer (React)', 'Bandung', 2, 'published', ['JavaScript', 'React']);
        $posting($arunika, 'Magang Data Analyst', 'Jakarta Selatan', 2, 'published', ['SQL', 'Python', 'Power BI']);
        $posting($sinergi, 'Magang Network Engineer', 'Surabaya', 2, 'draft', ['Docker']);

        $folderSurat = DocumentFolder::create(['name' => 'Template Surat', 'created_by' => $kaprodi->id]);
        $folderLaporan = DocumentFolder::create(['name' => 'Template Laporan', 'created_by' => $kaprodi->id]);
        foreach ([[$folderSurat, 'Format surat pengantar KP', 'template_surat', 'format-surat-pengantar-kp.docx'],
            [$folderLaporan, 'Template laporan akhir KP', 'template_laporan', 'template-laporan-akhir-kp.docx'],
            [$folderLaporan, 'Pedoman penulisan laporan KP', 'pendukung', 'pedoman-penulisan-laporan-kp.pdf']] as [$folder, $title, $type, $name]) {
            $file = StoredFile::create([
                'disk' => 'local', 'path' => 'dokumen/'.$name, 'original_name' => $name,
                'mime_type' => str_ends_with($name, '.pdf') ? 'application/pdf' : 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'size' => str_ends_with($name, '.pdf') ? 412 * 1024 : 58 * 1024, 'uploaded_by' => $kaprodi->id,
            ]);
            Document::create(['folder_id' => $folder->id, 'file_id' => $file->id, 'title' => $title, 'type' => $type, 'uploaded_by' => $kaprodi->id]);
        }

        // ---------- Konfirmasi, bimbingan, dokumen, hasil, perubahan ----------
        $kpOf = fn (User $student) => Internship::where('student_id', $student->id)->latest('id')->firstOrFail();

        // Konfirmasi penempatan: Annisa dan Laras belum dikonfirmasi; data Bagas dipertanyakan supervisornya
        foreach ([$kpNabila, $kpAulia, $kpRafi, $kpDimas, $kpSalsabila, $kpFajar, $kpRaka, $kpPutri, $kpKevin, $kpYudha] as $kp) {
            $this->confirmPlacement($kp);
        }
        $this->confirmPlacement($kpBagas, 'Posisi seharusnya NOC Engineer Intern, bukan Network Engineer Intern.');

        // Bimbingan dosen
        foreach ([[$kpNabila, $dewi, 4, 1], [$kpAulia, $dewi, 2, 0], [$kpRafi, $dewi, 5, 0], [$kpDimas, $bambang, 2, 1], [$kpSalsabila, $bambang, 6, 0],
            [$kpFajar, $bambang, 4, 1], [$kpBagas, $rizky, 3, 0], [$kpAnnisa, $rizky, 0, 1], [$kpRaka, $rizky, 5, 0], [$kpPutri, $dewi, 2, 0],
            [$kpKevin, $dewi, 5, 0], [$kpYudha, $rizky, 2, 1]] as [$kp, $lecturer, $confirmed, $pending]) {
            $this->seedGuidance($kp, $lecturer, $confirmed, $pending);
        }

        // Hasil KP yang sudah selesai
        $this->seedResult($kpSalsabila, $bambang, note: 'Laporan lengkap, presentasi jelas.');
        $this->seedResult($kpRaka, $rizky, note: 'Dokumentasi jaringan rapi.');

        // Dokumen KP mandiri: Farhan sudah mengunggah surat balasan, Tiara belum; Zahra dan Ilham meminta surat pengantar
        $this->seedDocuments($kpOf($farhan), $tu, reply: 'diunggah');
        $this->seedDocuments($kpOf($tiara), $tu, reply: 'menunggu');
        foreach ([$zahra, $ilham] as $k => $student) {
            $doc = InternshipDocument::create(['internship_id' => $kpOf($student)->id, 'type' => InternshipDocument::LETTER, 'status' => 'diminta', 'requested_by' => $student->id]);
            $doc->forceFill(['created_at' => now()->subDays(1 + $k * 3)])->saveQuietly();
        }

        // Pengajuan perubahan
        $this->seedChange($kpKevin, 'extend', 'supervisor',
            ['new_end_date' => $kpKevin->end_date->copy()->addMonth()->toDateString(), 'old_end_date' => $kpKevin->end_date->toDateString()],
            'Tim meminta saya menyelesaikan desain ulang aplikasi pelanggan sampai tahap uji pengguna.', daysAgo: 2);
        $this->seedChange($kpAulia, 'transfer', 'kaprodi', ['company_name' => 'PT Medika Integrasi Sistem'],
            'Proyek di divisi saya dihentikan perusahaan, sehingga tidak ada pekerjaan yang sesuai rencana kegiatan.', daysAgo: 4);

        // Kabar di lonceng notifikasi
        $this->seedNotices($nabila, [
            ['Komentar baru di task', 'Andika Saputra: Hasilnya rapi.', 'kegiatan.index', 'chat', 'blue'],
            ['Bimbingan dikonfirmasi', 'Progres bab 1 dan bab 2', 'bimbingan.index', 'chat', 'green'],
            ['Penempatan dikonfirmasi', 'Company Supervisor mengonfirmasi data penempatan Anda.', 'kp.status', 'verify', 'green'],
        ], read: 2);

        // ---------- Riwayat aktivitas terbaru ----------
        $this->logs([
            [$nabila, 'task.buat', 'Menambah task ORD-7 "Endpoint ekspor pesanan ke CSV"', 0, '08:42'],
            [$nabila, 'timesheet.catat', 'Mencatat kegiatan: Endpoint ekspor pesanan ke CSV', 0, '17:05'],
            [$maya, 'penilaian.kirim', 'Mengirim penilaian akhir Muhammad Rafi Alfarizi untuk divalidasi', 1, '16:20'],
            [$yudha, 'rencana.buat', 'Menyusun rencana "Toko Online"', 1, '11:05'],
            [$tu, 'pengajuan.verifikasi', 'Memverifikasi PT Medika Integrasi Sistem (pengajuan Ilham Maulana Yusuf)', 2, '13:30'],
            [$admin, 'akun.aktifkan', 'Mengaktifkan akun Galih Permana', 2, '15:47'],
            [$zahra, 'kp.ajukan', 'Zahra Amalia mengajukan KP di PT Lumbung Tani Teknologi', 3, '09:12'],
            [$bambang, 'penilaian.validasi', 'Memvalidasi penilaian akhir Salsabila Khairunnisa: A (89)', 18, '10:00'],
        ]);
    }

    // =====================================================================

    private function person(string $name, string $email, string $role, ?string $nim = null, ?string $phone = null): User
    {
        return User::create([
            'name' => $name,
            'email' => $email,
            'password' => self::PASSWORD,
            'role' => $role,
            'nim' => $nim,
            'phone' => $phone,
            'is_active' => true,
        ]);
    }

    private function company(string $name, string $industry, string $address, string $city, string $domain, string $phone, User $tu): Company
    {
        return Company::create([
            'name' => $name, 'industry' => $industry, 'address' => $address, 'city' => $city,
            'email' => 'hrd@'.$domain, 'phone' => $phone, 'website' => 'https://www.'.$domain,
            'status' => 'mitra', 'created_by' => $tu->id, 'verified_by' => $tu->id, 'verified_at' => now()->subMonths(8),
        ]);
    }

    private function staff(string $name, Company $company, CompanyDivision $division, string $title): User
    {
        $domain = parse_url($company->website, PHP_URL_HOST);
        $domain = preg_replace('/^www\./', '', (string) $domain);
        $parts = preg_split('/\s+/', strtolower($name));
        $email = $parts[0].'.'.end($parts).'@'.$domain;

        $user = $this->person($name, $email, 'supervisor', phone: '08'.substr((string) crc32($email), 0, 10));
        CompanyStaffProfile::create(['user_id' => $user->id, 'company_id' => $company->id, 'division_id' => $division->id, 'job_title' => $title]);

        return $user;
    }

    private function student(string $name, string $nim, StudyProgram $program, float $gpa, array $skills): User
    {
        $parts = preg_split('/\s+/', strtolower($name));
        $email = $parts[0].'.'.$parts[1].'@'.self::STUDENT;

        $user = $this->person($name, $email, 'mahasiswa', nim: $nim, phone: '08'.substr($nim, -2).substr((string) crc32($nim), 0, 8));
        StudentProfile::create(['user_id' => $user->id, 'study_program_id' => $program->id, 'entry_year' => 2023, 'gpa' => $gpa]);

        foreach (Skill::whereIn('name', $skills)->get() as $skill) {
            $user->skills()->attach($skill->id, ['level' => $gpa >= 3.6 ? 'mahir' : 'menengah']);
        }

        return $user;
    }

    private function placement(User $student, Company $company, CompanyDivision $division, User $supervisor, User $lecturer, string $position,
        int $startedDaysAgo, int $months, string $field, int $diligence, int $idleDays = 0, bool $finished = false, bool $activities = true): Internship
    {
        $start = Carbon::today()->subDays($startedDaysAgo);
        $end = $start->copy()->addMonthsNoOverflow($months);

        // Dibuat "berjalan" dulu supaya kegiatan bisa diisi; penempatan selesai dikunci setelahnya.
        $internship = Internship::create([
            'student_id' => $student->id,
            'company_id' => $company->id,
            'division_id' => $division->id,
            'lecturer_id' => $lecturer->id,
            'academic_period_id' => $this->period?->id,
            'track' => 'lowongan',
            'position' => $position,
            'supervisor_name' => $supervisor->name,
            'supervisor_title' => $supervisor->staffProfile?->job_title,
            'supervisor_email' => $supervisor->email,
            'cadence' => $field === 'data' ? 'monthly' : 'weekly',
            'status' => 'berjalan',
            'start_date' => $start,
            'end_date' => $end,
        ]);
        $internship->assignSupervisor($supervisor)->forceFill(['created_at' => $start->copy()->subDays(3)])->saveQuietly();
        $internship->forceFill(['created_at' => $start->copy()->subDays(12)])->saveQuietly();

        if ($activities) {
            $this->seedGoals($internship, self::GOALS[$field]);
            $this->seedActivities($internship, self::PROJECTS[$field], $supervisor, $diligence, $idleDays, $finished ? $end : null);
        }

        if ($finished) {
            $internship->update(['status' => 'selesai']);
        }

        return $internship;
    }

    /** Pengajuan KP mandiri oleh mahasiswa, beserta data Company Supervisor dari formulir. */
    private function independent(User $student, Company $company, ?CompanyDivision $division, string $position, string $supervisorName, string $supervisorTitle,
        string $supervisorEmail, ?User $verifier, ?User $kaprodi, ?User $lecturer = null): Internship
    {
        $approved = $verifier && $kaprodi;
        $start = $approved ? Carbon::today()->subDays(3) : Carbon::today()->addDays(14);

        $internship = Internship::create([
            'student_id' => $student->id,
            'company_id' => $company->id,
            'division_id' => $division?->id,
            'lecturer_id' => $approved ? $lecturer?->id : null,
            'academic_period_id' => $this->period?->id,
            'track' => 'mandiri',
            'position' => $position,
            'supervisor_name' => $supervisorName,
            'supervisor_title' => $supervisorTitle,
            'supervisor_email' => $supervisorEmail,
            'supervisor_phone' => '0812'.substr((string) crc32($supervisorEmail), 0, 8),
            'status' => $approved ? 'berjalan' : 'diajukan',
            'start_date' => $start,
            'end_date' => $start->copy()->addMonthsNoOverflow(4),
        ]);
        $internship->forceFill(['created_at' => now()->subDays($approved ? 12 : 3)])->saveQuietly();

        InternshipApproval::create([
            'internship_id' => $internship->id,
            'step' => InternshipApproval::STEP_VERIFY,
            'status' => $verifier ? 'approved' : 'pending',
            'reviewed_by' => $verifier?->id,
            'reviewed_at' => $verifier ? now()->subDays($approved ? 9 : 2) : null,
            'note' => $verifier ? 'Perusahaan terdaftar resmi dan bersedia menerima mahasiswa magang.' : null,
        ]);
        InternshipApproval::create([
            'internship_id' => $internship->id,
            'step' => InternshipApproval::STEP_KAPRODI,
            'status' => $kaprodi ? 'approved' : 'pending',
            'reviewed_by' => $kaprodi?->id,
            'reviewed_at' => $kaprodi ? now()->subDays(6) : null,
        ]);

        return $internship;
    }

    /** Penilaian tervalidasi, nilai sidang, sertifikat, dan status konversi untuk KP yang sudah selesai. */
    private function finish(Internship $internship, User $supervisor, User $lecturer, User $kaprodi, array $scores, int $defense, string $note, array $conversion): void
    {
        $this->seedAssessment($internship, $supervisor, $scores, submit: true, validator: $lecturer, note: $note);

        DefenseScore::create([
            'internship_id' => $internship->id,
            'score' => $defense,
            'defended_at' => $internship->end_date->copy()->addDays(10),
            'entered_by' => $kaprodi->id,
        ]);

        $template = CertificateTemplate::firstOrCreate(
            ['scope' => 'kampus', 'name' => 'Sertifikat KP kampus'],
            ['signer_name' => $kaprodi->name, 'signer_title' => 'Ketua Program Studi', 'created_by' => $kaprodi->id]
        );
        Certificate::create([
            'internship_id' => $internship->id,
            'template_id' => $template->id,
            'number' => Certificate::nextNumber(),
            'status' => 'terbit',
            'issued_by' => $kaprodi->id,
            'issued_at' => $internship->end_date->copy()->addDays(14),
        ]);

        [$status, $position] = $conversion;
        $candidate = ConversionCandidate::create([
            'internship_id' => $internship->id,
            'status' => 'direkomendasikan',
            'recommended_by' => $supervisor->id,
            'note' => $note,
        ]);
        $candidate->histories()->create(['to_status' => 'direkomendasikan', 'changed_by' => $supervisor->id]);
        if ($status !== 'direkomendasikan') {
            $candidate->moveTo($status, $supervisor->id, "Ditawari posisi {$position}.");
            $candidate->update(['offered_position' => $position]);
        }
    }

    /** @param  list<Internship>  $internships */
    private function meeting(array $internships, User $supervisor, string $day, string $time, string $url): void
    {
        foreach ($internships as $internship) {
            WeeklyMeeting::create([
                'internship_id' => $internship->id,
                'week_number' => $internship->currentWeek(),
                'day' => $day,
                'time' => $time,
                'url' => $url,
                'created_by' => $supervisor->id,
            ]);
        }
    }

    /** @param  list<array{0: User, 1: string, 2: string, 3: int, 4: string}>  $rows  [pelaku, aksi, deskripsi, hari lalu, jam] */
    private function logs(array $rows): void
    {
        foreach ($rows as [$actor, $action, $description, $daysAgo, $time]) {
            [$h, $m] = explode(':', $time);
            $at = Carbon::today()->subDays($daysAgo)->setTime((int) $h, (int) $m);
            $at = $at->greaterThan(now()) ? now()->subMinutes(20) : $at;

            $log = ActivityLog::create([
                'user_id' => $actor->id,
                'action' => $action,
                'description' => $description,
                'ip_address' => '10.12.'.mt_rand(1, 40).'.'.mt_rand(2, 250),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/130.0',
            ]);
            $log->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
        }
    }
}
