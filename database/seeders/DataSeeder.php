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
 * Data bawaan aplikasi: orang, perusahaan, dan aktivitas yang tampak seperti data sungguhan
 * (semuanya fiktif). Tanggal dihitung dari hari ini, jadi data selalu terlihat "sedang berjalan".
 *
 * Daftar akun ada di docs/AKUN.md. Semua akun memakai kata sandi self::PASSWORD.
 * Data untuk tes otomatis ada di DemoSeeder (dipanggil lewat TestingSeeder), bukan di sini.
 */
class DataSeeder extends Seeder
{
    public const PASSWORD = 'Simagti2026';

    /** Domain email kampus (fiktif). Ganti di sini jika ingin memakai domain kampus Anda. */
    private const CAMPUS = 'kampus.ac.id';

    private const STUDENT = 'mhs.kampus.ac.id';

    /** Kumpulan task per bidang: [judul, deskripsi, jenis bukti]. */
    private const TASKS = [
        'backend' => [
            ['Rancang skema tabel modul pemesanan', 'Buat ERD dan migrasi untuk tabel pesanan, item, dan pembayaran.', 'github'],
            ['Buat endpoint daftar pesanan', 'REST API dengan filter status dan paginasi.', 'github'],
            ['Tulis unit test service pembayaran', 'Minimal cakupan 80% untuk PaymentService.', 'github'],
            ['Integrasi webhook payment gateway', 'Tangani notifikasi sukses, gagal, dan kedaluwarsa.', 'github'],
            ['Optimasi query laporan penjualan', 'Kurangi waktu muat laporan bulanan di bawah 2 detik.', 'github'],
            ['Perbaiki bug validasi alamat pengiriman', 'Tiket #482 dari tim QA.', 'github'],
            ['Code review bersama mentor', 'Diskusikan masukan pada pull request minggu ini.', 'docs'],
            ['Dokumentasi mingguan', 'Rangkum pekerjaan, kendala, dan rencana minggu depan.', 'docs'],
        ],
        'frontend' => [
            ['Implementasi halaman katalog produk', 'Sesuai desain Figma versi 3, responsif sampai 360px.', 'github'],
            ['Komponen keranjang belanja', 'Tambah, ubah jumlah, dan hapus item dengan state global.', 'github'],
            ['Integrasi API autentikasi', 'Login, refresh token, dan logout.', 'github'],
            ['Perbaiki aksesibilitas form checkout', 'Label, urutan fokus, dan pesan galat yang jelas.', 'github'],
            ['Uji tampilan di berbagai perangkat', 'Catat temuan di lembar QA.', 'docs'],
            ['Optimasi ukuran bundle', 'Terapkan lazy loading untuk halaman admin.', 'github'],
            ['Dokumentasi mingguan', 'Rangkum pekerjaan, kendala, dan rencana minggu depan.', 'docs'],
        ],
        'data' => [
            ['Bersihkan data transaksi kuartal III', 'Tangani nilai kosong, duplikat, dan format tanggal.', 'drive'],
            ['Analisis retensi pelanggan', 'Hitung cohort retention per bulan pendaftaran.', 'drive'],
            ['Buat dashboard penjualan regional', 'Power BI: omzet, margin, dan tren per provinsi.', 'drive'],
            ['Validasi hasil analisis dengan tim bisnis', 'Presentasi singkat temuan utama.', 'docs'],
            ['Tulis query SQL laporan mingguan', 'Otomatiskan laporan yang selama ini dibuat manual.', 'github'],
            ['Segmentasi pelanggan dengan RFM', 'Kelompokkan pelanggan dan beri rekomendasi tindak lanjut.', 'drive'],
            ['Dokumentasi mingguan', 'Rangkum pekerjaan, kendala, dan rencana minggu depan.', 'docs'],
        ],
        'network' => [
            ['Inventarisasi perangkat jaringan kantor cabang', 'Catat tipe, IP, dan versi firmware.', 'drive'],
            ['Konfigurasi VLAN untuk lantai 3', 'Pisahkan jaringan tamu, staf, dan server.', 'docs'],
            ['Pantau trafik dengan Zabbix', 'Siapkan alert untuk penggunaan bandwidth di atas 80%.', 'docs'],
            ['Dokumentasikan prosedur backup konfigurasi', 'SOP backup harian router dan switch.', 'docs'],
            ['Uji failover koneksi ISP', 'Simulasikan putusnya link utama dan catat waktu pulih.', 'drive'],
            ['Containerisasi aplikasi monitoring internal', 'Buat Dockerfile dan docker-compose.', 'github'],
            ['Dokumentasi mingguan', 'Rangkum pekerjaan, kendala, dan rencana minggu depan.', 'docs'],
        ],
        'design' => [
            ['Riset pengguna aplikasi kasir', 'Wawancara 5 pemilik toko dan rangkum temuan.', 'docs'],
            ['Buat user flow pemesanan', 'Dari pilih produk sampai pembayaran berhasil.', 'figma'],
            ['Wireframe halaman dashboard toko', 'Versi low-fidelity untuk desktop dan ponsel.', 'figma'],
            ['Desain sistem komponen tombol dan form', 'Variasi ukuran, status, dan mode gelap.', 'figma'],
            ['Prototipe interaktif alur checkout', 'Siap diuji dengan pengguna.', 'figma'],
            ['Uji kegunaan prototipe', 'Catat waktu penyelesaian dan kendala tiap peserta.', 'docs'],
            ['Dokumentasi mingguan', 'Rangkum pekerjaan, kendala, dan rencana minggu depan.', 'docs'],
        ],
    ];

    private const COMMENTS = [
        'Sudah sesuai, lanjutkan ke tahap berikutnya.',
        'Rapi. Tambahkan penjelasan singkat di README.',
        'Bagus, tapi perhatikan penamaan variabel agar konsisten.',
        'Hasilnya oke. Coba tambahkan pengujian untuk kasus gagal.',
        'Terima kasih, sudah saya cek dan bisa digabung.',
        'Perlu sedikit perbaikan pada bagian validasi, sisanya baik.',
    ];

    private ?AcademicPeriod $period = null;

    public function run(): void
    {
        $this->period = AcademicPeriod::current();
        $tif = StudyProgram::where('code', 'TIF')->firstOrFail();
        $si = StudyProgram::where('code', 'SI')->firstOrFail();

        // ---------- Kampus ----------
        $kaprodi = $this->person('Dr. Arief Rahman Hakim, S.Kom., M.T.', 'arief.hakim@'.self::CAMPUS, 'kaprodi', phone: '081220456781');
        $dewi = $this->person('Dewi Lestari Wulandari, S.Kom., M.Kom.', 'dewi.lestari@'.self::CAMPUS, 'dospem', phone: '081321779054');
        $bambang = $this->person('Ir. Bambang Sutrisno, M.T.', 'bambang.sutrisno@'.self::CAMPUS, 'dospem', phone: '081394112730');
        $rizky = $this->person('Rizky Aditya Pratama, S.T., M.Cs.', 'rizky.pratama@'.self::CAMPUS, 'dospem', phone: '085722318846');
        $biro = $this->person('Wulan Sari Anggraini, S.E.', 'wulan.anggraini@'.self::CAMPUS, 'biro', phone: '082118804512');

        foreach ([[$kaprodi, '0415078201'], [$dewi, '0422118702'], [$bambang, '0003066804'], [$rizky, '0419099003']] as [$lecturer, $nidn]) {
            LecturerProfile::create(['user_id' => $lecturer->id, 'study_program_id' => $tif->id, 'nidn' => $nidn]);
        }

        // ---------- Perusahaan dan stafnya ----------
        $cakrawala = $this->company('PT Cakrawala Solusi Digital', 'Perangkat lunak', 'Jl. Asia Afrika No. 112', 'Bandung', 'cakrawaladigital.co.id', '0224231178', $biro);
        $arunika = $this->company('PT Arunika Data Analitika', 'Analitik data', 'Jl. TB Simatupang Kav. 18', 'Jakarta Selatan', 'arunikadata.co.id', '0217806624', $biro);
        $sinergi = $this->company('PT Sinergi Telematika Nusantara', 'Infrastruktur TI dan jaringan', 'Jl. Raya Darmo No. 45', 'Surabaya', 'sinergitelematika.co.id', '0315678210', $biro);
        $kreasi = $this->company('CV Kreasi Visual Mandala', 'Desain digital dan multimedia', 'Jl. Kaliurang Km 5,6', 'Sleman', 'kreasivisual.id', '0274889132', $biro);

        $divCakrawala = CompanyDivision::create(['company_id' => $cakrawala->id, 'name' => 'Product Engineering']);
        $divArunika = CompanyDivision::create(['company_id' => $arunika->id, 'name' => 'Data & Insight']);
        $divSinergi = CompanyDivision::create(['company_id' => $sinergi->id, 'name' => 'Network Operations']);
        $divKreasi = CompanyDivision::create(['company_id' => $kreasi->id, 'name' => 'Creative']);

        $andika = $this->staff('Andika Saputra', 'mentor', $cakrawala, $divCakrawala, 'Senior Backend Engineer');
        $maya = $this->staff('Maya Kartika Sari', 'mentor', $cakrawala, $divCakrawala, 'Frontend Lead');
        $hendro = $this->staff('Hendro Wibisono', 'ketua_divisi', $cakrawala, $divCakrawala, 'Head of Engineering');
        $fitri = $this->staff('Fitri Handayani', 'mentor', $arunika, $divArunika, 'Lead Data Analyst');
        $yoga = $this->staff('Yoga Prasetya Nugroho', 'ketua_divisi', $arunika, $divArunika, 'Head of Data');
        $agus = $this->staff('Agus Setiawan', 'mentor', $sinergi, $divSinergi, 'Senior Network Engineer');
        $ratna = $this->staff('Ratna Dewi Puspita', 'ketua_divisi', $sinergi, $divSinergi, 'NOC Manager');
        $galih = $this->staff('Galih Permana', 'mentor', $kreasi, $divKreasi, 'Senior UI/UX Designer');
        $intan = $this->staff('Intan Permatasari', 'ketua_divisi', $kreasi, $divKreasi, 'Creative Director');

        // ---------- Mahasiswa ----------
        $s = fn (string $name, string $nim, StudyProgram $program, float $gpa, array $skills) => $this->student($name, $nim, $program, $gpa, $skills);
        $nabila = $s('Nabila Azzahra Putri', '2210511021', $tif, 3.71, ['PHP', 'Laravel', 'MySQL', 'Git']);
        $rafi = $s('Muhammad Rafi Alfarizi', '2210511034', $tif, 3.48, ['JavaScript', 'React', 'Git']);
        $aulia = $s('Aulia Rahmawati', '2210512007', $si, 3.22, ['PHP', 'Laravel', 'UI/UX']);
        $dimas = $s('Dimas Arya Wicaksono', '2210511052', $tif, 3.35, ['Python', 'SQL', 'Analisis Data']);
        $salsabila = $s('Salsabila Khairunnisa', '2210512015', $si, 3.82, ['Python', 'SQL', 'Power BI']);
        $fajar = $s('Fajar Nugraha Ramadhan', '2210512029', $si, 3.40, ['SQL', 'Power BI', 'Analisis Data']);
        $bagas = $s('Bagas Satria Putra', '2210511066', $tif, 3.28, ['Java', 'Docker', 'Git']);
        $annisa = $s('Annisa Fitriani', '2210511073', $tif, 3.57, ['Python', 'Docker']);
        $raka = $s('Raka Mahendra', '2210511018', $tif, 3.44, ['Java', 'SQL']);
        $putri = $s('Putri Ayu Lestari', '2210512041', $si, 3.63, ['UI/UX', 'JavaScript']);
        $kevin = $s('Kevin Pratama Halim', '2210511087', $tif, 3.31, ['UI/UX', 'Vue.js']);
        $zahra = $s('Zahra Amalia', '2210512053', $si, 3.52, ['Flutter', 'JavaScript']);
        $ilham = $s('Ilham Maulana Yusuf', '2210511095', $tif, 3.39, ['PHP', 'Laravel', 'MySQL']);
        $s('Citra Kirana Dewi', '2210512062', $si, 3.66, ['Analisis Data', 'Power BI']); // belum KP

        // ---------- Penempatan yang berjalan dan selesai ----------
        // [mahasiswa, perusahaan, divisi, mentor, ketua divisi, dospem, mulai (hari lalu), durasi (bulan), bidang, kerajinan %, hari tanpa bukti, selesai?]
        $p = fn (...$a) => $this->placement(...$a);
        $kpNabila = $p($nabila, $cakrawala, $divCakrawala, $andika, $hendro, $dewi, 95, 5, 'backend', 94);
        $kpRafi = $p($rafi, $cakrawala, $divCakrawala, $maya, $hendro, $dewi, 122, 5, 'frontend', 88);
        $kpAulia = $p($aulia, $cakrawala, $divCakrawala, $andika, $hendro, $dewi, 64, 6, 'backend', 52);
        $kpDimas = $p($dimas, $arunika, $divArunika, $fitri, $yoga, $bambang, 70, 4, 'data', 80, idleDays: 11);
        $kpSalsabila = $p($salsabila, $arunika, $divArunika, $fitri, $yoga, $bambang, 190, 5, 'data', 97, finished: true);
        $kpFajar = $p($fajar, $arunika, $divArunika, $fitri, $yoga, $bambang, 106, 4, 'data', 85);
        $kpBagas = $p($bagas, $sinergi, $divSinergi, $agus, $ratna, $rizky, 88, 5, 'network', 78);
        $kpAnnisa = $p($annisa, $sinergi, $divSinergi, $agus, $ratna, $rizky, 33, 4, 'network', 90);
        $kpRaka = $p($raka, $sinergi, $divSinergi, $agus, $ratna, $rizky, 215, 5, 'network', 91, finished: true);
        $kpPutri = $p($putri, $kreasi, $divKreasi, $galih, $intan, $dewi, 58, 4, 'design', 92);
        $kpKevin = $p($kevin, $kreasi, $divKreasi, $galih, $intan, $dewi, 84, 5, 'design', 74);

        // ---------- KP mandiri ----------
        // Zahra: baru diajukan, menunggu verifikasi biro
        $lumbung = Company::create([
            'name' => 'PT Lumbung Tani Teknologi', 'industry' => 'Agritech', 'address' => 'Jl. Soekarno-Hatta No. 9', 'city' => 'Malang',
            'email' => 'karier@lumbungtani.id', 'status' => 'mandiri_menunggu', 'created_by' => $zahra->id,
        ]);
        $this->independent($zahra, $lumbung, $bambang, 'Rahmat Hidayat', 'rahmat.hidayat@lumbungtani.id', biroApproved: null);
        LetterRequest::create(['student_id' => $zahra->id, 'company_id' => $lumbung->id, 'purpose' => 'Surat pengantar untuk pengajuan KP mandiri di PT Lumbung Tani Teknologi.', 'status' => 'diajukan']);

        // Ilham: sudah diverifikasi biro, menunggu persetujuan kaprodi
        $medika = Company::create([
            'name' => 'PT Medika Integrasi Sistem', 'industry' => 'Sistem informasi kesehatan', 'address' => 'Jl. Pemuda No. 150', 'city' => 'Semarang',
            'email' => 'hrd@medikaintegrasi.co.id', 'phone' => '0248315527', 'status' => 'mandiri_terverifikasi',
            'created_by' => $ilham->id, 'verified_by' => $biro->id, 'verified_at' => now()->subDays(2),
        ]);
        $this->independent($ilham, $medika, $rizky, 'Sri Wahyuni', 'sri.wahyuni@medikaintegrasi.co.id', biroApproved: $biro);

        // ---------- Penilaian akhir ----------
        $this->finish($kpSalsabila, $fitri, $yoga, $kaprodi, ['Disiplin' => 92, 'Komunikasi' => 86, 'Kemampuan teknis' => 90, 'Kerja tim' => 88], 88, 'Analisis rapi dan inisiatif tinggi.', ['ditawari', 'Junior Data Analyst']);
        $this->finish($kpRaka, $agus, $ratna, $kaprodi, ['Disiplin' => 85, 'Komunikasi' => 79, 'Kemampuan teknis' => 84, 'Kerja tim' => 83], 81, 'Teliti dalam dokumentasi jaringan.', ['direkomendasikan', null]);
        $this->assess($kpRafi, $maya, ['Disiplin' => 86, 'Komunikasi' => 80, 'Kemampuan teknis' => 88, 'Kerja tim' => 82], submit: true);
        $this->assess($kpKevin, $galih, ['Disiplin' => 78, 'Komunikasi' => 84, 'Kemampuan teknis' => 80, 'Kerja tim' => 86], submit: true);
        $this->assess($kpFajar, $fitri, ['Disiplin' => 88, 'Komunikasi' => 84], submit: false);

        // ---------- Meeting minggu ini ----------
        $this->meeting([$kpNabila, $kpAulia], $andika, 'rabu', '10:00', 'https://meet.google.com/qyt-hmsk-pwa');
        $this->meeting([$kpRafi], $maya, 'kamis', '14:00', 'https://meet.google.com/bnr-xkze-rto');
        $this->meeting([$kpDimas, $kpFajar], $fitri, 'selasa', '09:30', 'https://us06web.zoom.us/j/84123657790');
        $this->meeting([$kpBagas, $kpAnnisa], $agus, 'senin', '13:00', 'https://meet.google.com/ovd-tjqa-mke');
        $this->meeting([$kpPutri, $kpKevin], $galih, 'jumat', '10:00', 'https://meet.google.com/zfe-pwkn-hyc');

        // ---------- Pendukung: template task, lowongan, dokumen ----------
        $template = TaskTemplate::create([
            'owner_id' => $andika->id, 'company_id' => $cakrawala->id, 'name' => 'Onboarding backend engineer',
            'description' => 'Dua minggu pertama untuk mahasiswa magang di tim Product Engineering.', 'is_shared' => true,
        ]);
        foreach ([[1, 'senin', 'Siapkan lingkungan kerja', 'Pasang PHP 8.3, Composer, Docker, dan akses repositori.'],
                  [1, 'selasa', 'Pelajari arsitektur layanan', 'Baca dokumen arsitektur dan diagram alur pesanan.'],
                  [1, 'jumat', 'Dokumentasi mingguan', 'Rangkum hal yang dipelajari minggu ini.'],
                  [2, 'senin', 'Kerjakan tiket pertama', 'Ambil satu tiket berlabel good-first-issue.']] as $i => [$week, $day, $title, $description]) {
            $template->items()->create(['week_number' => $week, 'day' => $day, 'title' => $title, 'description' => $description, 'sort_order' => $i]);
        }

        $skill = fn (string $name) => Skill::where('name', $name)->value('id');
        $posting = function (Company $company, string $title, string $city, int $quota, string $status, array $skills) use ($biro, $skill) {
            $job = JobPosting::create([
                'company_id' => $company->id, 'posted_by' => $biro->id, 'title' => $title,
                'description' => 'Magang 4 sampai 6 bulan bersama tim, dengan mentor khusus dan proyek nyata.',
                'requirements' => 'Mahasiswa aktif semester 6 ke atas, IPK minimal 3,00, bersedia magang penuh waktu.',
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

        // ---------- Riwayat aktivitas terbaru ----------
        $this->logs([
            [$andika, 'task.buat', 'Menambah task "Integrasi webhook payment gateway" untuk Nabila Azzahra Putri', 0, '08:42'],
            [$nabila, 'bukti.kirim', 'Mengirim bukti untuk task "Buat endpoint daftar pesanan"', 0, '10:15'],
            [$maya, 'penilaian.kirim', 'Mengirim penilaian akhir Muhammad Rafi Alfarizi untuk divalidasi', 1, '16:20'],
            [$galih, 'penilaian.kirim', 'Mengirim penilaian akhir Kevin Pratama Halim untuk divalidasi', 1, '11:05'],
            [$biro, 'pengajuan.verifikasi', 'Memverifikasi PT Medika Integrasi Sistem (KP mandiri Ilham Maulana Yusuf)', 2, '13:30'],
            [$fitri, 'task.ubah', 'Mengubah task "Analisis retensi pelanggan" menjadi Completed', 2, '15:47'],
            [$zahra, 'pengajuan.kirim', 'Mengajukan KP mandiri di PT Lumbung Tani Teknologi', 3, '09:12'],
            [$yoga, 'penilaian.validasi', 'Memvalidasi penilaian akhir Salsabila Khairunnisa: A (89)', 18, '10:00'],
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

    private function company(string $name, string $industry, string $address, string $city, string $domain, string $phone, User $biro): Company
    {
        return Company::create([
            'name' => $name, 'industry' => $industry, 'address' => $address, 'city' => $city,
            'email' => 'hrd@'.$domain, 'phone' => $phone, 'website' => 'https://www.'.$domain,
            'status' => 'mitra', 'created_by' => $biro->id, 'verified_by' => $biro->id, 'verified_at' => now()->subMonths(8),
        ]);
    }

    private function staff(string $name, string $role, Company $company, CompanyDivision $division, string $title): User
    {
        $domain = parse_url($company->website, PHP_URL_HOST);
        $domain = preg_replace('/^www\./', '', (string) $domain);
        $parts = preg_split('/\s+/', strtolower($name));
        $email = $parts[0].'.'.end($parts).'@'.$domain;

        $user = $this->person($name, $email, $role, phone: '08'.substr((string) crc32($email), 0, 10));
        CompanyStaffProfile::create(['user_id' => $user->id, 'company_id' => $company->id, 'division_id' => $division->id, 'job_title' => $title]);

        return $user;
    }

    private function student(string $name, string $nim, StudyProgram $program, float $gpa, array $skills): User
    {
        $parts = preg_split('/\s+/', strtolower($name));
        $email = $parts[0].'.'.$parts[1].'@'.self::STUDENT;

        $user = $this->person($name, $email, 'mahasiswa', nim: $nim, phone: '08'.substr($nim, -2).substr((string) crc32($nim), 0, 8));
        StudentProfile::create(['user_id' => $user->id, 'study_program_id' => $program->id, 'entry_year' => 2022, 'gpa' => $gpa]);

        foreach (Skill::whereIn('name', $skills)->get() as $skill) {
            $user->skills()->attach($skill->id, ['level' => $gpa >= 3.6 ? 'mahir' : 'menengah']);
        }

        return $user;
    }

    private function placement(User $student, Company $company, CompanyDivision $division, User $mentor, User $supervisor, User $lecturer,
        int $startedDaysAgo, int $months, string $field, int $diligence, int $idleDays = 0, bool $finished = false): Internship
    {
        $start = Carbon::today()->subDays($startedDaysAgo);

        $internship = Internship::create([
            'student_id' => $student->id,
            'company_id' => $company->id,
            'division_id' => $division->id,
            'lecturer_id' => $lecturer->id,
            'academic_period_id' => $this->period?->id,
            'track' => 'lowongan',
            'status' => 'berjalan',
            'start_date' => $start,
            'end_date' => $start->copy()->addMonthsNoOverflow($months),
        ]);
        $internship->assignStaff($mentor, InternshipStaff::ROLE_MENTOR);
        $internship->assignStaff($supervisor, InternshipStaff::ROLE_SUPERVISOR);

        $this->tasks($internship, $mentor, $field, $diligence, $idleDays, $finished);

        if ($finished) {
            $internship->update(['status' => 'selesai']);
        }

        return $internship;
    }

    /**
     * Task 6 minggu terakhir (atau sampai minggu berjalan). Minggu yang lewat diselesaikan sesuai tingkat
     * kerajinan; minggu berjalan diselesaikan sampai hari ini. Bukti dan komentar ikut dibuat dengan tanggal yang wajar.
     */
    private function tasks(Internship $internship, User $mentor, string $field, int $diligence, int $idleDays, bool $finished): void
    {
        mt_srand(crc32($internship->student->nim));
        $set = self::TASKS[$field];
        $days = array_keys(WeeklyTask::DAYS);
        $current = $internship->currentWeek();
        $todayIndex = min(4, max(0, Carbon::today()->dayOfWeekIso - 1));

        for ($week = max(1, $current - 5); $week <= $current; $week++) {
            foreach ($days as $i => $day) {
                // Senin sampai Kamis bergiliran dari daftar (tanpa item terakhir), Jumat selalu dokumentasi mingguan.
                [$title, $description, $kind] = $set[($week * 4 + $i) % (count($set) - 1)];
                if ($i === 4) {
                    [$title, $description, $kind] = end($set); // Jumat selalu dokumentasi mingguan
                }

                $isPast = $finished || $week < $current;
                $date = $internship->start_date->copy()->addDays(($week - 1) * 7 + $i)->setTime(mt_rand(13, 17), mt_rand(0, 59));
                $date = $date->greaterThan(now()) ? now()->subHours(mt_rand(1, 3)) : $date;
                $evidenceAt = $date->copy();
                // Mahasiswa pasif: tidak ada task yang diselesaikan dalam $idleDays hari terakhir.
                $isIdle = $idleDays > 0 && $date->greaterThan(now()->subDays($idleDays));

                if ($isPast) {
                    $done = mt_rand(1, 100) <= $diligence && ! $isIdle;
                    $status = $done ? 'completed' : (mt_rand(0, 1) ? 'in_progress' : 'todo');
                } else {
                    $done = $i < $todayIndex && mt_rand(1, 100) <= max($diligence, 60) && ! $isIdle;
                    $status = $done ? 'completed' : ($i === $todayIndex ? 'in_progress' : 'todo');
                }

                $task = WeeklyTask::create([
                    'internship_id' => $internship->id,
                    'week_number' => $week,
                    'day' => $day,
                    'title' => $title,
                    'description' => $description,
                    'status' => $status,
                    'created_by' => $mentor->id,
                ]);

                if ($status === 'completed') {
                    $evidence = TaskEvidence::create([
                        'weekly_task_id' => $task->id,
                        'submitted_by' => $internship->student_id,
                        'type' => 'link',
                        'title' => $this->evidenceTitle($kind),
                        'url' => $this->evidenceUrl($kind, $internship, $task),
                    ]);
                    $evidence->forceFill(['created_at' => $evidenceAt, 'updated_at' => $evidenceAt])->save();
                    $task->forceFill(['completed_at' => $evidenceAt->copy()->addHours(3)])->saveQuietly();

                    if ($isPast || mt_rand(0, 1)) {
                        $comment = TaskComment::create([
                            'weekly_task_id' => $task->id,
                            'user_id' => $mentor->id,
                            'body' => self::COMMENTS[mt_rand(0, count(self::COMMENTS) - 1)],
                        ]);
                        $at = $evidenceAt->copy()->addHours(mt_rand(2, 20));
                        $at = $at->greaterThan(now()) ? now()->subMinutes(30) : $at;
                        $comment->forceFill(['created_at' => $at, 'updated_at' => $at])->save();
                    }
                }
            }
        }
    }

    private function evidenceTitle(string $kind): string
    {
        return match ($kind) {
            'github' => 'Pull request di GitHub',
            'drive' => 'Berkas hasil di Google Drive',
            'figma' => 'Desain di Figma',
            default => 'Dokumen di Google Docs',
        };
    }

    private function evidenceUrl(string $kind, Internship $internship, WeeklyTask $task): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $internship->company->name));
        $id = substr(hash('crc32b', $task->id.$internship->id), 0, 8);

        return match ($kind) {
            'github' => "https://github.com/{$slug}/internal-app/pull/".(100 + $task->id),
            'drive' => "https://drive.google.com/file/d/1{$id}Qx7Lm2Np9Ka/view",
            'figma' => "https://www.figma.com/file/{$id}KxP2mQ/prototipe",
            default => "https://docs.google.com/document/d/1{$id}Hv3Rk8Tz/edit",
        };
    }

    private function independent(User $student, Company $company, User $lecturer, string $mentorName, string $mentorEmail, ?User $biroApproved): Internship
    {
        $start = Carbon::today()->addDays(14);

        $internship = Internship::create([
            'student_id' => $student->id,
            'company_id' => $company->id,
            'lecturer_id' => $lecturer->id,
            'academic_period_id' => $this->period?->id,
            'track' => 'mandiri',
            'status' => 'diajukan',
            'start_date' => $start,
            'end_date' => $start->copy()->addMonthsNoOverflow(4),
            'notes' => "Calon mentor: {$mentorName}.",
        ]);

        InternshipApproval::create([
            'internship_id' => $internship->id,
            'step' => InternshipApproval::STEP_BIRO,
            'status' => $biroApproved ? 'approved' : 'pending',
            'reviewed_by' => $biroApproved?->id,
            'reviewed_at' => $biroApproved ? now()->subDays(2) : null,
            'note' => $biroApproved ? 'Perusahaan terdaftar resmi dan bersedia menerima mahasiswa magang.' : null,
        ]);
        InternshipApproval::create(['internship_id' => $internship->id, 'step' => InternshipApproval::STEP_KAPRODI, 'status' => 'pending']);

        Invitation::create([
            'email' => $mentorEmail,
            'role' => 'mentor',
            'company_id' => $company->id,
            'internship_id' => $internship->id,
            'invited_by' => $student->id,
            'expires_at' => now()->addDays(12),
        ]);

        return $internship;
    }

    /** @param  array<string, int>  $scores */
    private function assess(Internship $internship, User $mentor, array $scores, bool $submit): Assessment
    {
        $rubric = AssessmentRubric::active();
        $assessment = Assessment::create(['internship_id' => $internship->id, 'rubric_id' => $rubric->id, 'assessor_id' => $mentor->id]);

        foreach ($rubric->aspects as $aspect) {
            if (isset($scores[$aspect->name])) {
                $assessment->scores()->create(['rubric_aspect_id' => $aspect->id, 'score' => $scores[$aspect->name]]);
            }
        }

        $assessment->update(['final_score' => $assessment->computeFinalScore()]);

        if ($submit) {
            $assessment->update(['status' => 'submitted', 'submitted_at' => now()->subDay()]);
        }

        return $assessment;
    }

    /** Penilaian tervalidasi, nilai sidang, sertifikat, dan status konversi untuk KP yang sudah selesai. */
    private function finish(Internship $internship, User $mentor, User $supervisor, User $kaprodi, array $scores, int $defense, string $note, array $conversion): void
    {
        $assessment = $this->assess($internship, $mentor, $scores, submit: true);
        $assessment->validateBy($supervisor, $note);

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
    private function meeting(array $internships, User $mentor, string $day, string $time, string $url): void
    {
        foreach ($internships as $internship) {
            WeeklyMeeting::create([
                'internship_id' => $internship->id,
                'week_number' => $internship->currentWeek(),
                'day' => $day,
                'time' => $time,
                'url' => $url,
                'created_by' => $mentor->id,
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
