# DESIGN: Teknologi dan Penerapan

Dokumen ini menjelaskan bahasa dan teknologi yang dipakai di tiap bagian proyek, dan bagaimana penerapannya.

## 1. Ringkasan teknologi

| Bagian | Teknologi | Alasan |
|---|---|---|
| Bahasa backend | PHP 8.3 | Laravel 13 membutuhkan PHP 8.3+ |
| Framework | Laravel 13 | Struktur baku industri, autentikasi, validasi, ORM, tes bawaan |
| Database | MySQL (Laragon) | Relasional, mendukung foreign key dan unique constraint |
| Tes | PHPUnit, SQLite in-memory | Cepat, tidak menyentuh data asli |
| Frontend | Blade (template server) + CSS biasa, warna logo SIMAGTI | Tanpa build step, cocok untuk pemula dan hosting sederhana |
| JavaScript | Hampir tidak ada (hanya `select()` pada link salin) | Sederhana, minim bug |
| API | JSON, REST, Laravel Sanctum (token Bearer) | Standar, mudah dijelaskan |
| Font | Plus Jakarta Sans (Google Fonts) | Terbaca, tidak kaku |
| Ikon | Heroicons outline (paket `blade-ui-kit/blade-heroicons`) | Set ikon dari pembuat Tailwind CSS; satu gaya garis yang konsisten |
| Dokumentasi | Markdown, Mermaid untuk ERD | Bisa dibuka di VS Code |

## 2. Arsitektur backend

Satu permintaan melewati lapisan berikut, dan tiap lapisan hanya mengurus satu hal:

```
Rute + middleware EnsureRole      → siapa boleh masuk ke area ini (peran)
  → Form Request                  → hak akses per data (lewat Policy) lalu validasi isian
    → Controller (tipis)          → menerima hasil, memanggil Action, memilih tampilan
      → Action                    → aturan bisnis, satu kelas satu tugas, memakai transaksi
        → Model + constraint DB   → penjaga terakhir: aturan yang tidak boleh dilanggar
```

Urutan di Form Request sengaja: hak akses dicek sebelum validasi, sehingga orang yang tidak berhak langsung mendapat 403 dan tidak sempat mengetahui isi aturan validasinya.

### Di mana setiap aturan ditulis

| Aturan | Lokasi |
|---|---|
| Peran mana boleh membuka area apa | `routes/web.php`, `EnsureRole` |
| Mentor hanya mengelola bimbingannya sendiri | `InternshipPolicy::manageTasks` |
| Mahasiswa hanya mengirim bukti untuk task miliknya | `WeeklyTaskPolicy::submitEvidence` |
| KP yang selesai menjadi hanya-baca | `InternshipPolicy` dan `WeeklyTask` (model menolak simpan) |
| Satu mahasiswa hanya satu penempatan aktif | kolom unik `internships.active_student_id` + policy dan aturan validasi |
| Kaprodi baru bisa setuju setelah biro memverifikasi | `ReviewKaprodiApproval` |
| Pengajuan tidak diproses dua kali | `ReviewBiroVerification`, `ReviewKaprodiApproval` |
| Undangan sekali pakai dan kedaluwarsa 14 hari | `AcceptInvitation`, `Invitation::isUsable` |
| Mentor harus staf dari perusahaan yang dipilih | `StorePlacementRequest` |
| Siapa melihat penempatan apa | `Internship::visibleTo` (dipakai web dan API) |
| Mentor hanya menilai bimbingannya, selama belum dikirim | `InternshipPolicy::assess` |
| Ketua divisi hanya memvalidasi penilaian divisinya yang sudah dikirim | `InternshipPolicy::validateAssessment` |
| Semua aspek wajib dinilai sebelum dikirim, nilai 0 sampai 100 | `SaveAssessmentRequest` |
| Total bobot rubrik harus 100%, nilai akhir dihitung dari bobot | `SaveAssessment`, `Assessment::computeFinalScore` |
| Dikembalikan ke mentor wajib dengan catatan | `ReviewAssessmentRequest`, `ReviewAssessment` |
| Lampiran task hanya PDF, PPT/PPTX, JPG/PNG/WEBP, maks. 10 MB, maks. 5 per task; isi berkas dicek, bukan hanya namanya | `TaskAttachment::rules`, `AttachTaskFiles` |
| Lampiran hanya bisa diunduh mahasiswa pemilik task dan mentornya; berkas disimpan di disk privat | `WeeklyTaskPolicy::view`, `TaskAttachmentController`, rute dengan `scopeBindings` |
| Hanya mentor (selama KP belum dikunci) yang menambah atau menghapus lampiran | `WeeklyTaskPolicy::update` |
| Penilaian yang sudah divalidasi tidak bisa diubah | model `Assessment` |
| Mahasiswa melihat nilai hanya setelah divalidasi | dashboard mahasiswa, `BuildCompanyStats` |
| Siapa "perlu perhatian" (tanpa bukti 7 hari, progres < 60%, hampir selesai belum dinilai) | konstanta di `BuildMonitoringDashboard` |
| KP mandiri yang menunggu kaprodi | scope `Internship::awaitingKaprodi` |
| Huruf mutu menyimpan versi skala nilainya | `grade_scale_version`, `Grading` |
| Nomor sertifikat dan token verifikasi unik | constraint tabel `certificates` |

### Struktur folder penting

```
app/Actions/          aturan bisnis (Tasks, Meetings, Applications, Invitations, Companies, Students, Placements, Assessments, Dashboard, Auth)
app/Policies/         hak akses per data
app/Http/Requests/    validasi + otorisasi per aksi
app/Http/Controllers/ pengarah tipis (Api/V1 untuk API)
app/Http/Resources/   bentuk JSON API
app/Models/           37 model dan relasinya
app/Support/          Grading (skala nilai), Menu (sidebar per peran), Num (format angka), Relative (hari lalu)
database/migrations/  21 berkas (+ tabel token Sanctum), 40 tabel
database/seeders/     MasterSeeder (aman untuk produksi), DataSeeder (data realistis fiktif, bawaan),
                      DemoSeeder + TestingSeeder (khusus tes otomatis)
resources/views/      Blade
public/css/kp.css     seluruh gaya
tests/Feature/        118 tes
```

## 3. Database

MySQL, 40 tabel, relasi lewat foreign key. Rancangan lengkap dan ERD ada di `docs/DATABASE.md`. Prinsipnya: aturan yang tidak boleh dilanggar dijaga oleh database, bukan hanya oleh kode.

- `internships` adalah pusat. Mentor dan ketua divisi disimpan per penempatan di `internship_staff`.
- `internships.active_student_id` unik dan hanya terisi saat status `diajukan` atau `berjalan`.
- `weekly_meetings(internship_id, week_number)` unik, `internship_staff(internship_id, user_id, role)` unik.
- `locked_at` mengunci penempatan yang selesai.
- `grade_scales` berversi, huruf mutu disimpan bersama versinya.
- Bukti (`task_evidences`), komentar (`task_comments`), dan materi dari mentor (`task_attachments` → `files`) adalah tabel terpisah.
- Soft delete di tabel penting, riwayat status disimpan di tabel tersendiri.

## 4. Frontend

Blade merender HTML di server, CSS ditulis manual di satu berkas.

- **Gaya**: perpaduan dua acuan, yaitu struktur rapi ala "Mondays" (topbar, pita angka, tabel bersih) dan kartu lembut ala "Coursue" (banner sapaan berwarna, ikon berlatar lembut). Warna mengikuti **logo SIMAGTI**: hijau terang `#02A86F`, teal `#028D80`, dan hijau tua `#03423E`. Aksen utama `--primary: #037A68` (perpaduan teal dan hijau, kontras teks putih 5,3:1 sehingga lolos standar keterbacaan), gradasi banner dari hijau tua ke hijau terang seperti logo. Latar abu kehijauan, kartu putih bersudut 20px, font Plus Jakarta Sans 400 sampai 800.
- **Satu gaya untuk semua halaman**: semua halaman memakai layout `layouts/app.blade.php`, satu berkas `public/css/kp.css`, dan komponen yang sama. Tidak ada CSS khusus per halaman.
- **Token desain** di `:root` pada `kp.css` (warna, radius, bayangan). Semua warna merek ada di token (`--brand-*`, `--primary*`, `--hero-gradient`, `--bar-gradient`); tidak ada kode warna merek yang ditulis langsung di aturan lain.
- **Status** memakai chip berbulatan kecil: To Do abu-abu, In Progress biru, Completed hijau, menunggu kuning, ditolak merah.
- **Kuning = perlu tindakan, hijau = aman, abu-abu = informasi** (berlaku untuk kartu angka dan chip).
- **Tanpa animasi** selain transisi warna 150 ms.
- **Responsif**: di layar kecil sidebar menjadi bilah atas, kartu angka dua kolom, tabel bisa digulir.
- **Logo**: `public/images/logo-simagti.svg` (logo lengkap, latar transparan, dipakai di sidebar, login, dan halaman aktivasi) dan `logo-simagti-mark.svg` (simbol saja, untuk ikon tab browser). Keduanya dibuat dari berkas logo asli tanpa mengubah bentuk dan warnanya, hanya latar putih dibuang dan ruang kosong dipotong.
- **Ikon**: komponen `x-ikon` (bukan `x-icon`, karena nama itu dipakai paket blade-icons) memetakan nama ikon aplikasi (misalnya `tasks`) ke Heroicons (`clipboard-document-check`). Jika paket belum terpasang atau nama ikon tidak ditemukan, komponen memakai ikon cadangan sehingga halaman tetap tampil.
- **Kartu angka** (acuan gaya Donezo): judul dan tombol panah melingkar bila bisa diklik, angka besar, satu baris keterangan, tanpa ikon; kartu pertama di setiap baris otomatis menjadi kartu sorotan berlatar hijau tua. Menu aktif ditandai garis hijau di tepi kiri sidebar; tombol berbentuk kapsul.
- **Komponen Blade**: `hero` (banner sapaan di semua dashboard, tombolnya menuju halaman yang paling perlu dibuka peran itu), `stat` (kartu angka, satu-satunya gaya ringkasan angka), `person` (avatar + nama + NIM), `avatar` (inisial berwarna tetap per orang), `chip`, `progress`, `icon`, `task-table`.
- **Grafik tanpa JavaScript**: batang mendatar (`hbars`) untuk sebaran skill dan performa per aspek, kolom (`grade-bars`) untuk sebaran huruf mutu. Semuanya HTML + CSS biasa.
- **Menu per peran** dibuat di `Menu.php` dan hanya menampilkan halaman yang sudah ada.

## 5. API

REST berformat JSON di `/api/v1`, saat ini **baca-saja**. Memakai Action, Policy, dan scope yang sama dengan web, jadi aturan akses tidak ditulis dua kali.

| Metode | Alamat | Akses |
|---|---|---|
| POST | `/api/v1/auth/token` | publik, menukar email + sandi dengan token |
| DELETE | `/api/v1/auth/token` | mencabut token yang sedang dipakai |
| GET | `/api/v1/me` | pengguna login |
| GET | `/api/v1/internships` | sesuai peran (`?status=`, `?per_page=`) |
| GET | `/api/v1/internships/{id}` | yang berhak (Policy) |
| GET | `/api/v1/internships/{id}/tasks` | yang berhak |
| GET | `/api/v1/companies` | biro, kaprodi |
| GET | `/api/v1/companies/{id}/stats` | biro, kaprodi, staf perusahaan itu (durasi, skill %, performa) |
| GET | `/api/v1/certificates/{token}` | publik, verifikasi QR, tanpa NIM |

Pengamanan: token Bearer (Sanctum), pembatasan laju pada login dan verifikasi, respons selalu JSON, token akun nonaktif ditolak, pesan gagal login sama untuk semua sebab.

Contoh:

```bash
curl -X POST http://localhost:8000/api/v1/auth/token \
  -H "Content-Type: application/json" \
  -d '{"email":"mahasiswa@kp.test","password":"password","device_name":"uji"}'

curl http://localhost:8000/api/v1/internships -H "Authorization: Bearer TOKEN_ANDA"
```

## 6. Keamanan

- Kata sandi di-hash otomatis (cast `hashed`), login dibatasi 10 percobaan per menit.
- Akun nonaktif tidak bisa masuk web maupun API.
- Link bukti dan link meeting hanya menerima `http` dan `https`, sehingga `javascript:` ditolak.
- Berkas lampiran disimpan di `storage/app/private` (tidak bisa diakses langsung lewat URL), diberi nama acak, dicatat checksum SHA-256-nya, dan dikirim dengan header `nosniff`.
- Setiap form memakai token CSRF.
- Aktivitas penting dicatat di `activity_logs` (siapa, kapan, IP, perangkat).
- Semua isian dihindarkan dari injeksi lewat Eloquent dan escaping Blade.

## 7. Pengujian

PHPUnit dengan SQLite in-memory, data demo diisi ulang tiap tes. Tes dikelompokkan menurut risiko:

- `SchemaTest`: aturan yang dijaga database dan model
- `KpFlowTest`: login, peran, task, meeting
- `ApplicationFlowTest`: KP mandiri dan undangan
- `MasterDataTest`: perusahaan, mahasiswa, penempatan
- `ApiTest`: token, akses per peran, statistik, verifikasi sertifikat
- `AssessmentFlowTest`: penilaian mentor, validasi dan pengembalian oleh ketua divisi, kunci nilai
- `DashboardDataTest`: data dashboard per peran, daftar perlu perhatian, statistik perusahaan
- `TaskAttachmentTest`: lampiran lama hanya bisa dibuka yang berhak, tidak bisa ditambah lagi
- `ActivityBoardTest`, `TimesheetTest`, `SyllabusTest`, `MonitorTest`: fitur KP v2 (board, timesheet, silabus, pemantauan per kloter, unduhan)
- `DataSeederTest`: data bawaan termuat, setiap peran bisa masuk, akun tidak tampil di login

## 8. Menjalankan

```powershell
cd D:\phkm\kp-monitor
php artisan install:api          # sekali saja: memasang Sanctum dan tabel token
composer require blade-ui-kit/blade-heroicons   # sekali saja: paket ikon
php artisan migrate:fresh --seed
php artisan test
composer run dev
```

Akun dan kata sandi untuk demo ada di `docs/AKUN.md` (tidak ditampilkan di halaman login).

`install:api` sudah dijalankan; perintah itu hanya perlu sekali.

## 9. Batasan yang sengaja diambil

- Tanpa Vite/Tailwind: tidak ada langkah build, lebih mudah di-hosting.
- Email belum dikirim: link aktivasi undangan ditampilkan agar bisa disalin dan dikirim manual.
- API belum punya operasi tulis: ditambah setelah web stabil.

## Standar UI SIMAGTI (wajib untuk setiap halaman baru)

Disepakati 25 Oktober 2026. Halaman baru harus memakai aturan dan komponen di bawah ini, bukan membuat gaya sendiri.

**0. Design tokens.** Semua nilai dasar ada sebagai CSS variables di `:root` pada `public/css/kp.css`. Gaya baru wajib memakai token, bukan angka mentah.

| Kelompok | Token | Nilai |
|---|---|---|
| Spasi | `--space-1` … `--space-8` | 4, 8, 12, 16, 24, 32, 48, 64px |
| Ruang komponen | `--page-x`, `--card-x`, `--card-y` | 32 / 24 / 24px di desktop; 16px di layar ≤ 900px (`--page-x`) dan ≤ 600px (`--card-x`, `--card-y`) |
| Lebar | `--content-max`, `--measure` | konten maksimal 1440px (rata tengah di layar lebar), paragraf maksimal 72ch |
| Kontrol | `--control-h`, `--control-h-sm`, `--btn-x`, `--btn-x-sm` | 40 / 32px (40px di layar sentuh), padding tombol 20 / 16px |
| Teks | `--text-2xs` … `--text-6xl` | 11, 12, 13, 14, 15, 16, 18, 20, 24, 28, 32, 40px |
| Tinggi baris | `--leading-tight`, `--leading-snug`, `--leading-normal` | 1.2 / 1.4 / 1.5 |
| Radius | `--radius-xs`, `--radius-sm`, `--radius-md`, `--radius`, `--radius-lg`, `--radius-pill` | 8, 10, 16, 16, 24, 999px (sejak redesign November 2026) |

Hierarki teks: judul halaman `--text-4xl` (HP `--text-3xl`), judul banner `--text-5xl`, judul kartu `--text-lg`, isi `--text-base`, teks sekunder `--text-sm`, metadata `--text-xs`, overline kapital `--text-2xs`. Pengecualian yang disengaja: halaman masuk (`.signin-*`) dan ikon di dalam isian (padding 36/44/52px) memakai ukuran khusus.

**1. Jarak: kelipatan 4px, utamakan 8px.** Skala yang dipakai: 4, 8, 12, 16, 24, 32, 48, 64 (token `--space-1` … `--space-8`). Berlaku untuk `padding`, `margin`, dan `gap`. Garis 1px dan radius tidak termasuk. Nilai di luar skala (6, 10, 14, 18, 20, 22, 28) tidak dipakai di komponen aplikasi.

| Pemakaian | Jarak |
|---|---|
| Ikon ke teks, chip ke chip | 4–8px |
| Antar tombol | 12px (8px bila tombol menyatu dengan isian dalam satu baris) |
| Antar baris di dalam kartu | 12–16px |
| Padding kartu | 24px (HP 16px) |
| Antar kartu dan antar bagian halaman | 24px |

**2. Penamaan singkat.**
- Tombol 1–2 kata, kata kerja di depan: *Simpan*, *Kirim*, *Buka*, *Benar*, *Ada yang salah*, *Setujui*, *Tolak*. Tanpa kalimat di dalam tombol.
- Judul kartu 1–3 kata (*Konfirmasi penempatan*, *Mahasiswa magang*). Subjudul satu kalimat pendek atau dihapus bila judul sudah jelas.
- Kepala kolom 1 kata (*Mahasiswa*, *Posisi*, *Task*, *Jam*, *Minggu*, *Silabus*).
- Isi sel ringkas: *5/7 selesai*, *Aktif 2 hari lalu*, *13/22*. Informasi yang sudah ada di judul kelompok (perusahaan, divisi) tidak diulang di setiap baris.
- Status memakai chip 1–2 kata (*Disetujui*, *Menunggu*, *Perlu konfirmasi*).

**2b. Ringkas di tampilan, lengkap di detail.** Daftar, kartu, tabel, dan antrean hanya menampilkan info singkat: nama ringkas (`User::listName()`, mis. "Dewi Lestari W." tanpa gelar), satu baris keterangan pendek, status, dan tombol. Hal yang panjang (nama lengkap dengan gelar, alasan, catatan, daftar mata kuliah, nama berkas, alamat, keterangan langkah) hanya muncul di halaman detail atau saat **Detail** dibuka (`<x-detail>`, tombolnya di kanan). Nama lengkap tetap tersedia lewat tooltip (`title`) di desktop.

**3. Di HP, nama tidak dipotong.** Nama orang, judul task, dan judul baris turun baris (`overflow-wrap: anywhere`), tidak memakai elipsis (…). Elipsis hanya boleh di desktop pada tabel yang lebar. Aturan global ada di `kp.css` (blok "Standar: di HP nama dan judul di kartu tidak dipotong").

**4. Tombol aksi selalu di kanan, tombol utama paling kanan.** Di desktop maupun HP. Urutan: tombol sekunder (*Tolak*, *Ada yang salah*, *Batal*) di kiri, tombol utama (`btn-primary`) paling kanan. Pakai pembungkus `.row-actions`. Formulir sebaris (`.inline-form`) di HP juga rata kanan.

**4b. Tabel di HP menjadi kartu.** Di layar 600px ke bawah setiap baris tabel (`.table`) tampil sebagai kartu: kolom pertama jadi judul, kolom lain jadi pasangan label (kiri) dan nilai (kanan), keterangan kedua (`.cell-sub`) disembunyikan, tombol rata kanan. Label diambil otomatis dari kepala kolom (`public/js/app.js`). Tabel yang tidak boleh berubah diberi kelas `table-keep`. Di 601–1100px (tablet, laptop kecil) isi tabel boleh turun baris dan keterangan kedua disembunyikan, supaya tabel tidak perlu digeser ke samping.

**4c. Tabel data (`.table.dt`).** Dipakai untuk tabel daftar (mahasiswa, perusahaan, penempatan).
- `table-layout: fixed`: lebar kolom ditentukan kelas di `<th>`, jadi posisi kolom tidak bergeser karena panjang teks. Kolom utama (tanpa kelas) berbagi sisa ruang; kolom lain berlebar tetap: `w-person` 232px, `w-name` 192px, `w-meta` 20%, `w-progress` 176px (160 di ≤1440px), `w-num` 128px, `w-grade` 128px, `w-status` 136px, `w-action` 112px.
- Setiap sel maksimal dua baris: `.c-main` (600, `--text-base`) dan `.c-sub` (`--text-sm`, warna muted). Tinggi baris tetap 64px, kepala 44px.
- Teks yang tidak muat dipotong dengan elipsis hanya sebatas lebar kolomnya, dan teks lengkapnya ada di `title`. Jangan memakai `.truncate` (batas 180px) di tabel data.
- Identitas di kolom pertama: `<span class="ident">` + `<x-avatar>` (orang) atau `<x-org>` (perusahaan) + nama ringkas.
- Angka rata kanan dengan angka tabular (`.num`), aksi rata kanan (`.act`) memakai tombol tenang `.btn-quiet`.
- Tablet (601–1100px): kolom `.hide-md` disembunyikan, sel tetap satu baris. HP (≤600px): baris menjadi kartu.

**5. Daftar, bukan tabel lebar, untuk data orang.** Daftar mahasiswa memakai `<x-intern-list>` + `<x-intern-row>`: sejajar dengan kepala kolom di desktop, otomatis tersusun ke bawah di kartu sempit atau HP (container query), tanpa geser horizontal. Tabel hanya untuk data angka yang memang dibandingkan per kolom.

**6. Komponen yang dipakai ulang.**

| Kebutuhan | Komponen / kelas |
|---|---|
| Kartu dan kepala kartu | `.card`, `.card-head`, `.card-title`, `.card-sub`, `.card-body` |
| Status | `<x-chip tone="green|amber|red|blue|gray">` |
| Umur antrean | `<x-waiting :since="...">` |
| Daftar mahasiswa | `<x-intern-list>`, `<x-intern-row>` |
| Daftar item dengan ikon | `.item-list` > `.item-row` |
| Konfirmasi / keputusan dalam kartu | `pantau/_confirm-item` (fakta dalam kotak kecil, tombol di kanan) |
| Baris tombol | `.row-actions` (jarak 8px vertikal, 12px antar tombol) |
| Satu isian form | `.field` (label + kontrol + bantuan, jarak 8px; antar isian 16px lewat `.stack`) |
| Info panjang di daftar | `<x-detail>` (Detail di kanan, isi selebar baris) |
| Nama orang di daftar | `<x-person>` atau `$user->listName()` + `title` nama lengkap |
| Tabel daftar | `.table.dt` + kelas lebar kolom (`w-*`), `.ident`, `.c-main`/`.c-sub`, `.num`, `.act`, `.btn-quiet`, `.hide-md` |
| Tanda perusahaan | `<x-org :name>` (kotak inisial) |
| Kosong / tidak ada antrean | `.empty` atau `.calm` |
| Rencana kegiatan (Gantt) | `timesheet/_gantt` + `App\Support\Gantt` (`.gt`, `.gt-side`, `.gt-row.gt-plan` / `.gt-task`, `.gt-bar`; `gt-compact` untuk dashboard, `gt-readonly` bila hanya baca). Warna rencana: token `--gc-*` |
| Pilihan skala nilai | `.rate-group` > `.rate-row` > `.rate-options` (`.rate-opt` radio berbentuk pil, grid selebar kartu) |
| Linimasa aktivitas | `aktivitas/_feed` (`.feed`, `.feed-item`, `.feed-icon feed-{jenis}`) |
| Langkah yang bisa diklik | `.mini-steps` (dashboard) dan `a.step-link` (Status KP) |
| Bidang usaha | `<x-industry-select :value>` (pilihan + "Lainnya" diisi sendiri) |

**7. Sebelum selesai.** Setiap halaman baru dicek di 1920, 1440, 1280, 768, 390, dan 375px: tidak ada geser horizontal halaman, tidak ada teks terpotong di HP, tombol di kanan, jarak sesuai skala.

## Aturan tata letak (hasil audit UI)

- **Kartu berdampingan** memakai `.split` (dua kolom sama lebar). Setiap kolom (`.col`) menumpuk kartunya sendiri, jadi kartu pendek tidak menyisakan celah kosong. Di layar ≤900px menjadi satu kolom.
- **Kolom tabel yang panjang** (nama mahasiswa, perusahaan) diberi `class="col-fluid"` dan lebar persen pada `<th>`. Kolom ini ikut lebar tabel, dan teks baru dipotong (…) kalau memang tidak muat. Nama lengkap tetap muncul saat kursor diarahkan.
- **Angka di tabel** (bobot, nilai, kontribusi) memakai `col-num`: rata kanan dengan lebar digit yang sama, jadi angka sejajar dengan judul kolomnya.
- **Baris berisi nama + tombol** (`.item-row`): nama dipotong, tombol tidak pernah tertimpa.
- **Area keputusan** (setujui/tolak) memakai `.decision`: dua kolom berlabel, di HP menjadi bertumpuk.

## Kartu angka, menu kiri, dan grafik

- **Kartu angka** (sejak redesign November 2026): semua kartu putih dan kecil; label abu, angka besar, keterangan berupa pil berwarna sesuai makna. Tidak ada lagi kartu pertama berwarna hijau tua.
- **Menu kiri** tetap di tempat saat halaman di-scroll (`position: sticky`). Di layar ≤900px menjadi drawer (lihat bagian redesign di bawah).
- **Grafik** dibuat sendiri dengan SVG dari server, tanpa library dan tanpa CDN. Tooltip ada di `public/js/charts.js`.
  - `<x-donut>`: diagram lingkaran untuk bagian dari keseluruhan (status penilaian, status task minggu ini, status perusahaan). Legenda selalu tampil dengan angka dan persen.
  - `<x-line-chart>`: grafik garis untuk perubahan dari waktu ke waktu (bukti per minggu, progres task kumulatif mahasiswa). Satu sumbu Y saja. Atur `width` mendekati lebar kartu supaya ukuran hurufnya pas.
  - Diagram batang tetap dipakai untuk sebaran huruf mutu, dan batang mendatar untuk skill serta magang aktif per perusahaan.
  - Warna grafik mengikuti makna: hijau = selesai/tervalidasi, biru = berjalan/menunggu, kuning = draft/perlu dicek, abu-abu = belum. Kombinasi warnanya sudah dicek aman untuk buta warna.

## Layar penuh, ikon Material, dan bukti berkas

- **Layar penuh**: bingkai aplikasi menempel ke tepi layar (tanpa jarak atas/bawah dan tanpa sudut melengkung). Menu kiri tetap "sticky" setinggi layar.
- **Ikon**: Google Material Symbols (Rounded) dimuat dari Google Fonts, hanya ikon yang dipakai (peta di `app/Support/Icons.php`). Menu yang aktif memakai versi terisi (`<x-ikon name="..." fill />`). Kalau font gagal dimuat (misalnya tanpa internet), otomatis tampil ikon cadangan, jadi tidak pernah muncul tulisan nama ikon.
- **Bukti pengerjaan**: mahasiswa bisa mengirim link, berkas, atau keduanya. Berkas yang diterima: foto (JPG/PNG/WEBP/GIF), PDF, Word, Excel/CSV, PowerPoint, TXT/MD, ZIP/RAR/7Z, dan MP4. Maksimal 20 MB per berkas, 5 berkas sekali kirim, 15 berkas per task. Program, skrip, HTML, dan SVG ditolak; isi berkas ikut dicek, bukan hanya namanya. Berkas disimpan privat dan hanya bisa dibuka mahasiswa pemilik, Company Supervisor, dosen pembimbing, dan kaprodi. Mahasiswa bisa menghapus bukti yang salah kirim selama KP belum selesai.

## KP v2 (Tahap 1, Oktober 2026)

**Peran.** Mentor dan ketua divisi digabung menjadi satu peran perusahaan: **Company Supervisor** (`supervisor`). Supervisor hanya memantau, berkomentar, menyetujui silabus, mengatur meeting, mengoreksi posisi, dan menilai; ia tidak membuat task. Peran baru **TU Fakultas** (`tu`) mengaktifkan Company Supervisor dari data yang diisi mahasiswa. "Campus Supervisor" hanya label untuk dosen pembimbing di mata supervisor (`User::roleLabelFor`); di tempat lain tetap "Dosen pembimbing".

**Jalur KP.** `internships.program` = `magang` (sub-jenis KP Industri / MBKM Mandiri) atau `seminar` (Pelaksana / Peserta). Seminar tidak punya perusahaan dan ditinjau dosen pembimbing (`Internship::isReviewedBy`). Syarat semester (minimal V, MBKM 5 sampai 7), jam logbook MBKM (450), dan identitas kampus disimpan di `config/simagti.php` dan bisa ditimpa lewat tabel `settings` (`App\Support\Campus`).

**Kegiatan ala Jira/ClickUp.** Mahasiswa membuat proyek berkode (ORD, DOC), task dengan nomor urut (ORD-12), kolom board bebas (arti kolom menentukan status), prioritas, tenggat, label, checklist, bukti, dan komentar. Tampilan board (tarik-lepas, `public/js/app.js`, PATCH + CSRF) dan list dengan filter periode (ini / lalu / rentang), proyek, status, prioritas, kategori silabus, dan pencarian (`App\Support\TaskFilter`). Ritme kerja harian / mingguan / bulanan menentukan arti "periode ini" (`App\Support\Period`).

**Timesheet.** Satu catatan = tanggal, jam (maks 16, total per hari maks 24), aktivitas, task opsional, foto opsional (disk privat `timesheet/{id}`). Menjadi Form Absensi dan Aktivitas KP / logbook MBKM.

**Status KP.** Menu "Pengajuan KP" menjadi "Status KP" di bawah Dashboard, berisi langkah demi langkah (`App\Support\KpProgress`): pengajuan, biro, kaprodi, aktivasi supervisor, silabus, pelaksanaan, lalu laporan, presentasi, dan hasil (tahap berikutnya).

**Pemantauan per kloter.** `/pantau` mengelompokkan mahasiswa per perusahaan + divisi + periode (`Internship::cohortKey`), diberi nomor kloter bila satu divisi punya lebih dari satu periode. Detail mahasiswa menampilkan universitas, fakultas, prodi, angkatan, dan semester (`<x-academic>`).

**Unduhan.** Kegiatan dan timesheet per periode atau proyek bisa diunduh sebagai Excel (`App\Support\SimpleXlsx`, tanpa paket tambahan) dan PDF lewat halaman cetak berformat Form Absensi dan Aktivitas KP. Unduhan mengikuti filter yang aktif.

**Privasi.** Semua perubahan memakai POST / PATCH / DELETE dengan CSRF; URL hanya berisi id, tidak berisi data pribadi; berkas dan foto disimpan privat dan dibuka lewat pengecekan akses.

## KP v2 (Tahap 2, Oktober 2026)

**Beban perusahaan seminimal mungkin.** Company Supervisor hanya: konfirmasi penempatan satu klik, nilai bulanan 1–4 untuk tiga aspek (opsional catatan, tidak pernah menghambat kegiatan mahasiswa), menyatakan setuju pada perpanjangan, dan penilaian akhir sekali. Timesheet tidak perlu disetujui.

**Notifikasi.** Lonceng (`<x-bell />`) di topbar. Dua sumber: "Perlu tindakan" dihitung saat halaman dibuka (`App\Support\ActionItems::for($user)`), jadi otomatis hilang setelah ditindaklanjuti; "Kabar" disimpan di tabel `notifications` lewat `App\Support\Notify::send()` (pelaku tidak diberi kabar atas tindakannya sendiri). Membuka kabar menandainya dibaca dan hanya mengarahkan ke alamat di dalam aplikasi.

**Bulan KP.** Bulan ke-n dihitung dari tanggal mulai (`Internship::monthRange`), bulan terakhir sampai tanggal selesai. Refleksi bisa diisi mulai 7 hari sebelum bulan berakhir; penilai boleh menilai setelah refleksi masuk atau bulan berakhir; setelah dinilai refleksi terkunci, setelah catatan dosen nilai terkunci (`App\Actions\Monthly\MonthlyCycle`).

**Dokumen.** Surat pengantar dicetak dari aplikasi (`dokumen/surat-pengantar.blade.php`), nomor `001/KODE/bulan romawi/tahun` urut per tahun (`ManageDocuments::nextLetterNumber`, kode di `config/simagti.php` `campus.letter_code`). Surat balasan dan pakta integritas dibuat otomatis saat kaprodi menyetujui KP mandiri, batas `rules.reply_letter_days` (14) hari; berkas disimpan privat `dokumen/{id}`. Badge umur antrean `<x-waiting>` kuning setelah 3 hari, merah setelah 7 hari.

**Hasil KP.** `App\Actions\Results\FinalizeResult`: syarat penilaian akhir divalidasi dan bimbingan terkonfirmasi minimal `rules.min_guidance` (5). MBKM: `(nilai mitra × 60 + nilai dosen × 40) / 100`, bobot di `rules.mbkm_partner_weight` / `mbkm_lecturer_weight`. Setelah disimpan status menjadi `selesai` dan penempatan terkunci.

**Kuesioner.** Rubrik dipilih berurutan: rubrik yang sudah dipakai penilaian → kuesioner yang dipilih dosen untuk mahasiswa → rubrik aktif kampus (`Assessment::rubricFor`). Kuesioner yang sudah dipakai menilai tidak bisa diubah (buat salinan).

**Perubahan.** `App\Actions\Changes\HandleChangeRequest`. Satu pengajuan aktif per penempatan. Perpanjangan memperbarui `end_date` dan periode bulan yang sudah tercatat; ganti supervisor melepas akun lama dan mengembalikan penempatan ke antrean aktivasi TU.

## v8 (revisi dosen, 27 Oktober 2026)

Bagian KP v2 di atas menjelaskan riwayat; yang berlaku sekarang:

- **Peran**: mahasiswa, Company Supervisor, dosen pembimbing, kaprodi, TU Fakultas, admin. Biro Kerjasama dihapus; verifikasi KP mandiri, surat pengantar, surat balasan, dan data perusahaan dikerjakan TU Fakultas. Admin mengelola akun, standar penilaian, dan log aktivitas.
- **Satu jenis KP**: tidak ada jalur MBKM / seminar, silabus, evaluasi bulanan, kuesioner dosen, dan rekognisi SKS.
- **Timesheet**: kartu "Rencana kegiatan" berupa Gantt sepanjang periode KP, mengikuti pola aplikasi manajemen proyek. Kiri tabel Kegiatan / Durasi / Status (bar progres + %), menempel saat sumbu digeser; rencana bisa dilipat untuk menyembunyikan task-nya. Kanan sumbu bulan + hari (hari ini = pil merah dan garis merah, akhir pekan diarsir). Batang rencana berbentuk pil dengan warna pilihan mahasiswa (7 warna, dipilih di form rencana); bagian gelap = persen task selesai, pil muda = belum ada task, cincin merah = lewat waktu dengan task belum selesai. Task tampil sebagai pil kecil di bawah rencananya (muda = belum selesai, penuh + centang = selesai). Kontrol: Gantt / Daftar, zoom Hari (40px/hari) / Minggu (16px) / Penuh (muat selebar kartu), geser seminggu, Hari ini; pilihan disimpan di localStorage. Klik batang rencana membuka form ubah; klik task membuka detail task. Dashboard memakai versi ringkas (skala Minggu, bisa digeser ke kiri-kanan, dibuka di sekitar hari ini, tanpa toolbar dan task). Di HP kolom Durasi/Status disembunyikan pada tampilan Gantt, tetap ada di tampilan Daftar. Di bawahnya "Catatan kegiatan" harian tanpa jam.
- **Kegiatan**: kartu Goals di samping kiri board (di bawah 1100px pindah ke atas). Goal tercapai otomatis saat semua task-nya Done, bar ikut berubah saat task digeser; tombol "Task baru" diganti "Isi rencana" bila belum ada rencana. Board bawaan To Do, In Progress, Done.
- **Penilaian akhir**: satu kartu per kategori dengan rata-rata langsung terhitung (JS), setiap pertanyaan memakai pilihan skala; di kartu sempit pilihan turun ke bawah pertanyaan dalam grid sama lebar. Perkiraan nilai akhir baru tampil setelah semua pertanyaan dinilai.

## Redesign UI (November 2026)

Arah: dasar tenang ala alat produktivitas (acuan referensi Dribbble di brief), hijau-teal SIMAGTI dipakai hemat untuk aksi dan tiga elemen khas. Tanpa build step, tanpa CDN baru; markup komponen lama (`.card`, `.btn`, `.table.dt`, `.segmented`, `<x-chip>`, `<x-progress>`, `<x-stat>`, `<x-ikon>`) tetap, tampilannya diubah lewat CSS.

**Token yang berubah** (`:root` di `public/css/kp.css`):

| Token | Nilai | Catatan kontras (WCAG 2.1) |
|---|---|---|
| `--page`, `--bg` | `#f4f6f5` | latar halaman netral sangat terang |
| `--surface`, `--surface-2`, `--sidebar-bg` | `#ffffff`, `#f8faf9`, `#fafbfb` | kartu, isian, sidebar |
| `--line`, `--line-strong` | `#e6eae8`, `#d6dedb` | bingkai tipis kartu dan isian |
| `--text` | `#12201e` | 15,5:1 di atas latar |
| `--muted` | `#5b6b68` | 5,2:1 di atas latar, 5,6:1 di atas putih |
| `--muted-2` | `#647471` | placeholder, ≥ 4,5:1 di atas isian |
| `--primary` | `#037a68` (tetap) | teks putih di atasnya 5,3:1 |
| `--radius`, `--radius-sm`, `--radius-lg` | 16, 10, 24px | kartu, tombol/isian, kartu khas |
| `--shadow`, `--shadow-hover`, `--shadow-pop` | bayangan sangat tipis; `pop` untuk drawer | |
| `--focus-ring` | cincin hijau 3px | fokus keyboard pada tombol |
| `--sidebar-w`, `--appbar-h` | 240px, 56px | |

**Kerangka.**
- Desktop: sidebar abu terang, menu aktif berupa kotak putih kecil berbingkai dengan ikon hijau. Akun (Ganti kata sandi, Keluar) di bawah, dipisah garis.
- HP (≤900px): bar atas menempel berisi tombol ☰ (`.menu-btn`), logo, lonceng, dan avatar. ☰ membuka sidebar sebagai drawer dari kiri (`body.drawer-open`); tutup lewat tombol ×, klik latar gelap, atau Esc, lalu fokus kembali ke ☰ (`public/js/app.js`). Tautan "Lewati ke konten" (`.skip-link`) muncul saat ditekan Tab.
- Sapaan dashboard (`<x-hero>`): banner gradasi hijau merek dengan bintang hiasan, label pil transparan (`.hero-tag`), dan tombol kapsul gelap berikon panah (pilihan pemilik proyek, November 2026).
- Tombol berbentuk kotak membulat (10px), bukan kapsul. `btn-dark` lama tampil sebagai tombol utama.
- Kepala tabel tidak lagi huruf kapital semua.

**Tiga elemen khas.**
1. **Halaman masuk**: panel kanan hijau tua merek dengan kisi halus dan cuplikan aplikasi yang digambar dengan HTML (kartu Kemajuan KP, Gantt mini, kartu "Penilaian divalidasi"), bukan gambar. Di HP formulir berada di kartu putih di atas pita hijau tua, dengan tagline di bawahnya.
2. **Kartu Kemajuan KP** (`.kpp`, dashboard mahasiswa): kartu putih tepat di bawah banner hijau (supaya tidak ada dua blok hijau bertumpuk). Isinya minggu berjalan, sisa hari, cincin persen task (`.kpp-ring`, tautan ke Kegiatan), dan langkah KP mendatar (`.mini-steps.kpp-steps`, setiap langkah tetap bisa diklik). Di HP hanya titik langkah yang tampil; nama langkah yang sedang berjalan ada di baris bawah. Menggantikan kartu "Minggu berjalan" dan kartu langkah di kolom kanan.
3. **Gantt**: batang membulat 8px (task 6px), garis warna rencana di tepi kiri kolom nama, titik merah di ujung garis hari ini. Di HP kolom nama 140px agar timeline tetap terlihat.

**Pola baru.**
- **Filter dilipat di HP** (`kegiatan/_filters`): di ≤600px hanya kotak cari dan tombol **Filter** (dengan jumlah filter aktif) yang tampil; pilihan lain ada di `.filter-panel` yang dibuka tombol itu. Tanpa JavaScript semua pilihan tetap tampil (kelas `.js` di `<html>`).
- **Kegiatan di layar ≤1100px**: board tampil dulu, Goals sesudahnya.
- **Perlu perhatian** (dashboard dosen dan kaprodi, `.attention-card`): pindah ke atas daftar mahasiswa, berupa kisi kartu kecil dengan alasan berwarna kuning; seluruh kartu bisa diklik ke halaman pantau mahasiswa.
- **Kartu angka ganjil di HP**: kartu terakhir melebar penuh agar tidak ada celah.
- **Tombol aksi halaman di HP** (`.page-head`): tetap rata kanan di bawah judul.
- **Isian di HP** berukuran huruf 16px supaya Safari iOS tidak memperbesar halaman.

**Pengecekan.** Login, dashboard mahasiswa, Timesheet, Kegiatan, dashboard dosen, dan dashboard supervisor dicek di 1280px dan 390px. Halaman menu semua peran (56 halaman, 6 peran) dipindai di 390px: tidak ada geser horizontal dan tidak ada galat.

### Halaman Pemantauan dan Bimbingan (November 2026)

- **/pantau** (dosen, supervisor, kaprodi): mahasiswa dikelompokkan per perusahaan + divisi (judul kelompok di luar kartu, garis tipis di bawahnya). Setiap mahasiswa satu kartu `<x-intern-card>` dalam grid (`.icard-grid`, 3 per baris di 1280px, 1 di HP): nama ringkas, posisi, label kloter + periode, progres task, kotak fakta Minggu / Rencana / Aktif (kuning bila tidak aktif lebih dari 7 hari), status penilaian, tombol Buka. Seluruh kartu bisa diklik. `<x-intern-row>` tetap dipakai dashboard supervisor.
- **/bimbingan** (mahasiswa): pelacak syarat di atas (`.gtrack`, satu kotak per sesi yang dibutuhkan: hijau = dikonfirmasi, kuning = menunggu, garis putus = belum) dengan kalimat "n sesi lagi untuk syarat hasil KP". Riwayat berupa linimasa (`bimbingan/_timeline`, `.gtl`): tanggal besar di kiri, titik status di garis, topik dan status di kanan, catatan dosen tampil langsung, catatan mahasiswa di Detail. Formulir catat (`bimbingan/_form`) di kolom kanan pada desktop; di ≤1100px dibuka lewat tombol **Catat bimbingan** sebagai dialog. Antrean dosen (`bimbingan/_list`) tidak berubah.

### Ceria dan bersemangat, sedikit kata (November 2026)

- **Aksen warna** di samping teal: kuning `--acc-yellow`, biru `--acc-blue`, koral `--acc-coral`, ungu `--acc-violet` (masing-masing punya `-soft` untuk latar dan `-ink` untuk teks, semua ≥ 4,5:1 di atas latarnya).
- **Kartu angka**: ikon dalam kotak berwarna; ikon dipilih otomatis dari label di `<x-stat>` (bisa ditimpa lewat `icon`). Warna bergiliran teal, kuning, biru, ungu; tone amber memakai koral. Keterangan netral (tone biru/abu) hanya muncul sebagai tooltip.
- **Sedikit kata**: `app.js` memindahkan `.page-sub`, `.card-sub`, `p.card-foot`, dan `.goal-note` yang berupa penjelasan ke ikon ⓘ di samping judul (tooltip saat diarahkan, diklik, atau difokus; Esc menutup). Keterangan sangat pendek atau data singkat berangka tetap tampil. Tanpa JavaScript teks tampil seperti biasa. Teks banner dan label tombol dashboard dipendekkan.
- **Gerak halus**: bar progres terisi saat halaman dibuka, cincin Kemajuan KP berputar mengisi (bila browser mendukung `@property`), kartu sedikit terangkat saat diarahkan. Semua mati bila pengguna memilih kurangi gerak.
- **Perayaan**: lencana kuning "Tercapai" pada goal yang selesai; konfeti sekali saat goal tercapai di board dan saat syarat bimbingan terpenuhi (`[data-celebrate]`, diingat per perangkat lewat localStorage).
- **Keadaan kosong**: ilustrasi vektor kecil di atas setiap `.empty` (sementara, sebelum maskot final).

### Gaya papan proyek pastel (November 2026)

Acuan: tiga referensi papan proyek dan roadmap dari pemilik proyek.

- **Kartu task** (`kegiatan/_card`): latar pastel mengikuti warna proyek (`projects.color`: green, blue, amber, pink, purple, gray; tanpa proyek = putih), judul berwarna tinta proyek, pil prioritas berteks (Urgent, Tinggi, Sedang, Rendah), progres checklist berupa deretan titik (maks. 10), label proyek putih, meta di bawah garis putus-putus. Token `--pastel-*` (latar, garis, tinta; tinta ≥ 5,8:1 di atas latarnya).
- **Kolom board**: latar lembut bersudut 18px, judul kolom huruf biasa, jumlah dalam bulatan.
- **Kepala halaman**: `.page-head` dan `.pbanner` menjadi banner gradasi pastel (teal → biru → ungu muda). Kegiatan memakai `.pbanner` berisi judul, pil ringkas (tempat, periode, ritme, task selesai, lewat tenggat), dan tombol Task baru.
- **Tab**: `.tabs` bergaris bawah tipis, tab aktif bergaris teal dan tebal; Kegiatan punya tab Board, List, dan Timeline (menuju Timesheet), aksi Proyek/Kolom/Unduh di kanan (`.ptabs`). Halaman pantau mahasiswa memakai gaya tab yang sama.
- **Gantt**: pil progres `selesai/total` di ujung batang rencana (`.gt-frac`), pil merah "Hari ini" di garis hari ini.

### Board Kegiatan selebar halaman (November 2026)

- Tampilan Board tidak lagi memakai panel Goals di samping. **Goals menjadi kolom pertama board** (`kegiatan/_goals-col`, `.kcol-goals`), di kiri To Do, dengan kepala yang sama seperti kolom lain (judul, jumlah tercapai, tombol +). Setiap goal berupa kartu (`.gcard`): label Goal, lencana Tercapai, judul (klik untuk menyaring task goal itu), progres. Atribut `data-goal*` tetap, jadi bar dan lencana ikut berubah saat task digeser.
- Board memenuhi lebar halaman (`.container:has(.board.has-goals)` tanpa batas lebar): kolom Goals 240–280px, kolom task berbagi sisa ruang (min. 240px).
- Kartu task menampilkan maksimal 4 item checklist dengan kotak centang (hanya baca) dan "+n lainnya"; item dimuat bersama data board (`BoardData`, relasi `checklist`).
- Tampilan List tetap memakai kartu Goals di samping. Dialog Goal baru dipisah ke `kegiatan/_goal-dialog`.
- Kolom task berwarna per kategori seperti kolom Goals (gradasi lembut di atas, bingkai, ikon dalam kotak): To Do biru (`.kcol-cat-todo`), In Progress ungu (`.kcol-cat-in_progress`), Done hijau-teal (`.kcol-cat-done`). Kolom buatan mahasiswa ikut warna kategorinya.

### Rencana kegiatan di dashboard mahasiswa: kalender minggu (November 2026)

Gantt ringkas di dashboard (terlalu lebar, harus digeser) diganti tampilan mingguan (acuan: kalender mingguan berwarna):

- **Kartu rencana berwarna** (`.plan-card`): satu kartu per rencana kegiatan dengan warna rencananya, judul (maks. 2 baris), rentang tanggal, bar progres putih, dan jumlah task selesai.
- **Kalender minggu** (`App\Support\WeekPlanner`, `dashboard/_week`, `.week-cal`): kolom Senin–Minggu tanpa jam (SIMAGTI tidak mencatat jam). Task tampil sebagai blok berwarna rencananya, membentang dari tanggal mulai sampai tenggat dan disusun ke lajur agar tidak bertumpuk. Hari ini disorot, akhir pekan diarsir, task selesai berwarna lembut dan dicoret, task lewat tenggat bercincin merah, ujung blok datar bila task berlanjut ke minggu lain.
- Navigasi minggu sebelumnya/berikutnya lewat `?minggu=-1` / `?minggu=1`; tombol Timesheet membuka Gantt lengkap (tidak berubah).

### Halaman Timesheet (November 2026)

- **Banner** (`.pbanner`) berisi judul, pil ringkas (periode KP, jumlah rencana dan yang berjalan, task selesai, hari tercatat), Unduh, dan Rencana baru. Strip angka dihapus.
- **Dua tab** (`[data-tabs]` / `[data-tab-panel]` di `app.js`): Rencana dan Catatan harian, masing-masing dengan jumlahnya. Kedua isi tetap ada di HTML (tanpa JavaScript tampil bertumpuk). Tab Catatan terbuka otomatis bila alamat berisi `#catatan`, `?p=` atau `?periode=`, atau setelah catatan gagal disimpan. Panah kiri/kanan berpindah tab.
- **Gantt gaya roadmap** (pembungkus `.ts-roadmap`, khusus Timesheet): kolom kiri cukup nama rencana/task (Durasi dan Status hanya di tampilan Daftar), baris rencana berpita warna lembut sepanjang timeline, batang rencana lebih besar dan membulat dengan pil progres, batang task berbentuk kapsul, garis dan pil "Hari ini" biru.
- **Jurnal catatan harian**: kotak cepat di atas ("apa yang kamu kerjakan hari ini?", tanggal, task, tombol Foto, Simpan), lalu kartu per hari (`.jday`) dengan lencana tanggal berwarna per hari dalam minggu (hari ini warna penuh), setiap catatan berpinggir warna hari itu, label task, dan foto kecil.

### Rencana kegiatan: satu Gantt (November 2026)

Timeline lajur melayang sempat dicoba lalu dihapus atas permintaan pemilik proyek; rencana kegiatan di Timesheet dan halaman pantau kembali memakai satu Gantt tabel (`timesheet/_gantt`, gaya roadmap di Timesheet lewat `.ts-roadmap`). Kartu rencana dan kalender minggu di dashboard memakai partial bersama `timesheet/_plan-cards` dan `timesheet/_week-cal`.

- **Gaya Gantt** (referensi timeline proyek): batang rencana berupa kartu putih dengan garis warna di kiri, progres berupa garis tipis di bawah, dan pil "✓ selesai/total" di luar batang. Batang task berupa blok pastel (selesai = warna penuh bercentang, terlambat = merah muda berbingkai merah). Hari ini = pil biru di kepala tanggal dan garis biru putus-putus. Kolom kiri polos dengan titik warna rencana. Hanya CSS; struktur dan fitur Gantt tetap.

### Gantt rapi (November 2026)

Gantt rencana kegiatan memakai baris ringkas (rencana 44px, task 34px). Batang rencana berbentuk pil warna muda yang terisi warna lebih pekat sesuai persentase task selesai; judul dan angka x/y berada di dalam pil. Pil penuh pekat berarti semua task selesai, putus-putus berarti belum ada task, dan bingkai merah berarti terlambat. Batang task berupa garis pastel tipis. Kolom kiri hanya menampilkan judul satu baris; tanggal dan kode task tetap ada di tampilan Daftar. Task terlipat secara bawaan, dan rencana yang dibuka pengguna diingat di localStorage (`gt.expanded`). Warna toska, hijau, dan kuning dibuat lebih berjauhan agar rencana yang berdampingan tidak terlihat sama.

### Rencana kegiatan di dashboard, lebih mudah dibaca (November 2026)

Kartu rencana kini terang dengan titik warna rencana dan label status: Berjalan (disorot warna rencana), Selesai, Terlambat, Sudah lewat, atau Akan datang; rencana yang sudah lewat atau selesai diredam. Progres ditulis "x/y task", atau "Belum ada task". Kalender minggu diberi judul "Task minggu ini" dengan jumlahnya; tiap blok task menyebut nama rencananya, judul task, dan keadaannya (Selesai, Terlambat, atau tanggal tenggat), dengan garis warna rencana di kiri.

Kartu rencana kemudian dipindah dari dashboard ke tab Rencana di Timesheet, di atas Gantt (sekaligus keterangan warna batang Gantt; pemilik bisa klik kartu untuk mengubah rencana). Dashboard tinggal menampilkan kalender "Jadwal task" dengan jumlah task minggu yang dipilih.

Atas permintaan pemilik proyek, kartu rencana di Timesheet kembali ke gaya awal (blok warna pekat, teks putih, lingkaran dekoratif); label status tetap ada sebagai pil putih transparan (putih pekat untuk Berjalan dan Terlambat).

### Dashboard mahasiswa bento (November 2026)

Acuan: referensi Dribbble di brief (kartu angka terpisah, kisi kartu rapi). Jadwal task mingguan (`WeekPlanner`, `dashboard/_week`, `timesheet/_week-cal`) ditiadakan. Urutan: banner sapaan, kartu Kemajuan KP, tiga kartu angka terpisah (`.stat-grid.dash-stats`: ikon + judul, angka dan keterangan berbentuk pil dalam satu kotak dalam abu), lalu dua baris bento (`.dash-row`, kiri 2/3 dan kanan 1/3, kartu satu baris sama tinggi): **Rencana kegiatan** (kartu berwarna per rencana, dipindah dari Timesheet, tautan Timesheet di kepala) + **Tempat magang**, lalu **Perlu dikerjakan** + **Meeting minggu ini** (kotak waktu dan tombol Gabung selebar kartu). Di ≤1100px baris bento menjadi satu kolom. Timesheet kembali langsung menampilkan Gantt.

### Gaya bento untuk semua halaman (November 2026)

- **Kartu angka** (`.stat-grid` / `<x-stat>`) di semua halaman memakai gaya dashboard mahasiswa: kartu terpisah, ikon + judul, angka dan keterangan berbentuk pil dalam kotak dalam abu. Bila satu baris berisi 4 kartu atau lebih, angka dan keterangan bertumpuk agar tidak terpotong.
- **Dua kolom** (`.layout`): kolom sama tinggi dan kartu terakhir tiap kolom mengisi sisa ruang, jadi tepi bawah sejajar (kecuali kolom formulir yang menempel saat digulir).
- **Kepala halaman** (`.page-head`): kotak ikon menu yang sedang dibuka di samping judul (ditambahkan `app.js` dari ikon remah roti).
- **Kartu rencana** di dashboard mahasiswa bisa diklik: membuka Kegiatan yang tersaring ke rencana itu, dengan panah di pojok kartu.
- **Halaman galat** 403, 404, 419, 429, 500, 503 (`errors/_page`): logo, kode besar di kartu banner, kalimat singkat, tombol Ke dashboard dan Kembali.
- Kolom aksi tabel Akun dilebarkan agar tombol tidak menempel ke tepi kartu; riwayat Pengajuan Perubahan yang kosong memakai keadaan kosong bergambar.

- Isian di dalam `.field` tidak boleh melewati kolomnya (`min-width: 0; max-width: 100%`). Di formulir Catat bimbingan (kolom samping) kolom Tanggal dibuat lebih lebar dari kolom Cara agar tanggal dan ikon kalender muat tanpa menempel.

### Pemeriksaan jarak menyeluruh (November 2026)

Semua halaman menu dan halaman detail untuk enam peran (ditambah mahasiswa tanpa KP, menunggu verifikasi, dan sudah selesai, halaman masuk, serta halaman galat) dipindai otomatis di 1280px, 768px, dan 390px, termasuk isi dialog dan bagian lipat yang dibuka. Yang diperiksa: kotak yang bersebelahan dengan celah kurang dari 6px, kotak yang menempel ke teks, teks yang menempel ke teks, teks yang terlalu dekat dengan tepi kotaknya, label yang menempel ke isian, isi yang terpotong atau keluar dari kartu, geser horizontal, dan tooltip ⓘ yang terpotong. Perbaikan (gaya tidak berubah):

- Baris huruf mutu yang berlaku (`.kv-tight > div.is-current`): ruang dalam sama di semua sisi, juga saat menjadi baris pertama.
- Kartu angka: satuan selalu berjarak dari angkanya (di HP sempat menempel, mis. "13mahasiswa"); keterangan berbentuk pil boleh dua baris, tidak dipotong "…"; "Belum ada nilai" tidak terpotong di HP.
- Lencana jumlah notifikasi: angka tepat di tengah.
- Pilihan nilai di formulir penilaian (`.rate-options`) dan tombol ubah/hapus pertanyaan di Standar Penilaian berjarak 8px.
- Tooltip ⓘ di judul kartu dan judul halaman ditambatkan ke kiri judul agar tidak terpotong tepi kartu atau layar.
- HP: pilihan Peran di tabel Akun tidak melewati kartu; judul kepala halaman 22px dan kotak ikon 38px agar ikon ⓘ tetap sebaris dengan judul.

### Kartu mahasiswa di /pantau dan kartu Tempat magang (November 2026)

- **Kartu mahasiswa** (`<x-intern-card>`): avatar dilingkari cincin progres task (`.icard-ring`, kuning bila tidak aktif lebih dari 7 hari), nama dan posisi, label kloter + periode, kotak dalam abu berisi task selesai (angka, persen, batang tipis) dan fakta Minggu / Rencana / Aktif dipisah garis putus-putus, lalu kaki kartu dengan status penilaian dan tautan Buka berpanah. Latar atas bergradasi hijau lembut (kuning untuk yang perlu konfirmasi).
- **Tempat magang** (dashboard mahasiswa, `.place`): tanda inisial perusahaan + nama + kota, pil Divisi dan Posisi, kotak dalam berisi Company Supervisor dan Dosen pembimbing dengan avatar, lalu periode dengan batang bulan berjalan.

### Kartu kolom samping di semua halaman (November 2026)

Kartu di kolom kanan `.layout` / `.split` memakai pola kartu Tempat magang. Daftar label-nilai (`.kv`, kecuali `.kv-tight` huruf mutu) menjadi kotak dalam abu: label kecil di atas nilainya dan pemisah putus-putus, jadi nilai panjang tidak lagi rata kanan dan terpecah. Daftar baris (`.item-list`) berada dalam kotak dalam yang sama, dan jumlahnya berbentuk pil. Langkah "Cara aktivasi" (`.howto`) bernomor dalam lingkaran. Berlaku di antaranya untuk Ringkasan (Status KP), Detail dan Mahasiswa (task), Informasi dan Nilai akhir (penilaian), Contoh perhitungan dan Skala nilai (bobot), Ringkasan aktivitas, Rekap bimbingan, Baru diaktifkan (TU), dan Akun per peran (admin).

### Gerak halus (November 2026)

Satu ritme untuk semua halaman: kurva `--ease-premium` (cubic-bezier(.22,1,.36,1)), durasi 160 / 260 / 560 ms, hanya transform dan opacity.

- **Perpindahan halaman**: View Transitions bawaan browser; isi memudar 220 ms, sidebar dan bar atas tetap di tempat.
- **Masuk halaman**: blok isi `.container` naik 8 px bergiliran (50 ms, maks. 7 blok); kartu angka, kartu rencana, dan kartu mahasiswa ikut bergiliran tipis (45 ms). Hanya bila halaman terlihat saat dimuat (kelas `.motion` di `<html>`); animasi yang tertahan dituntaskan setelah 1,2 detik.
- **Dialog** muncul dengan skala 0,97 ke 1 dan latar memudar; **menu dan lonceng** turun pelan; **tooltip** sedikit bergeser saat muncul; **tab** memudar masuk.
- **Angka kartu angka** berhitung naik sekali (±700 ms); teks akhir sama dengan dari server.
- **Tombol** mengecil 2 persen saat ditekan; warna, bingkai, dan bayangan berganti lembut.
- Semua mati bila pengguna memilih kurangi gerak, dan saat dicetak.

- **Rekap per mahasiswa** (antrean Bimbingan dosen): cukup nama satu baris + deret sesi (kotak terisi = sesi terkonfirmasi) dan jumlah "4/5" di kanan (centang hijau bila syarat terpenuhi). Tempat dan tanggal selesai dipindah ke tooltip baris; detailnya ada di halaman pantau.

- **Kartu profil mahasiswa** (halaman detail pantau), tiga lapis rapi pilihan pemilik proyek; kepala halaman digabung ke kartu ini (tautan "Kembali ke daftar mahasiswa" di atas kartu). Lapis 1: avatar kotak membulat, nama sebagai judul halaman (h1) + pil status, satu baris NIM · prodi · angkatan (kampus, fakultas, semester di tooltip), tombol Unduh di kanan. Lapis 2: empat kolom info berlabel kecil (Tempat + divisi, Posisi, Periode, Dosen; supervisor melihat Divisi saja). Lapis 3: empat kotak angka abu (Task selesai + batang tipis, Minggu, Rencana, Hari tercatat). Lapis dipisah garis tipis, kolom info sejajar dengan teks kotak angka. 2 kolom di tablet dan HP. Versi eksklusif: lapis nama dan info berada di bagian atas bergradasi hijau merek (sama dengan banner dashboard) dengan teks putih, kilau cahaya, dan dua bintang tipis; pil status dan tombol Unduh bergaya kaca; kotak angka di dasar putih.

### Halaman verifikasi sertifikat (hasil pindai QR), November 2026

Tampilan potret untuk HP (lebar maks. 480px di desktop), acuan dua referensi sertifikat potret dari pemilik proyek: pita status (sah / tidak berlaku / tidak ditemukan), dua tombol lompat (Sertifikat, Keterangan nilai), lalu **sertifikat potret** (`.vx-sheet`, ukuran memakai satuan container agar proporsinya sama di semua layar): pita motif seperempat lingkaran hijau-teal dengan tahun tegak, logo dan kampus, tanggal terbit, judul "Sertifikat Kerja Praktik", nama bergaris bawah, keterangan posisi/tempat/periode, penanda tangan, stempel bulat "Terverifikasi", nomor. Di bawahnya **kartu keterangan nilai** terpisah: huruf mutu dalam lingkaran, nilai akhir /100, tanggal validasi, rata-rata Hard skill / Soft skill / Attitude dengan bobot dan batang. Nilai hanya tampil untuk sertifikat yang sah dan penilaian yang sudah divalidasi; NIM tetap tidak ditampilkan.

### Peta kontribusi (November 2026)

`<x-contrib>` dari `App\Support\Contribution`: kotak per hari ala GitHub sepanjang periode KP (kolom = minggu, baris = Senin-Minggu), warna 5 tingkat dari catatan + task selesai per hari, kotak hari mendatang bergaris tipis. Kepala kartu berisi pil hari aktif, rentetan terpanjang, dan rentetan sekarang (akhir pekan tidak memutus rentetan). Tampil di dashboard mahasiswa (di bawah kartu angka) dan tab Aktivitas halaman pantau. Kisi bisa digeser mendatar di layar sempit.

### Draf laporan KP otomatis (November 2026)

`/laporan` (mahasiswa, menu Unduh di Timesheet → Draf laporan KP): halaman A4 tegak berdiri sendiri (`laporan/draf`), disusun dari data KP. Sampul (posisi, perusahaan, periode, nama, NIM, kampus), Bab I Pendahuluan (tujuan dari goals, tabel waktu dan tempat), Bab II Profil tempat KP, Bab III Rencana dan pelaksanaan (angka ringkas + tabel task per rencana), Bab IV Uraian kegiatan mingguan (catatan harian dikelompokkan per minggu KP), Bab V Bimbingan (sesi yang dikonfirmasi), Bab VI Penutup. Bagian yang harus ditulis sendiri berupa kotak bergaris putus. Tombol Salin teks (menyalin seluruh isi untuk ditempel ke Word) dan Cetak / Simpan PDF; tanpa pustaka tambahan.

### KP Wrapped (November 2026)

`/wrapped` (mahasiswa, hanya setelah KP selesai; ajakan berupa kartu ungu di dashboard): cerita layar penuh ala Spotify Wrapped (`App\Support\Wrapped`, `wrapped/show`). Slide: sapaan, hari tercatat, task selesai (cincin), minggu paling produktif, hari paling rajin + rentetan terpanjang, goal tercapai, bimbingan, nilai akhir, rangkuman. Tiap slide bergradasi sendiri, angka berhitung naik, batang progres di atas, ganti otomatis tiap 6 detik (berhenti bila kurangi gerak atau tab disembunyikan), bisa diketuk kiri/kanan, panah keyboard, Spasi (jeda), Esc (tutup). Slide rangkuman: tombol Simpan gambar (PNG 1080x1350 digambar di canvas, tanpa pustaka) dan Putar ulang.

### Portofolio KP publik (November 2026)

Pengaturan di menu **Portofolio** (mahasiswa, `/portofolio`): sakelar tautan publik dan tampilkan nilai (bawaan mati), judul singkat, tentang, keahlian (dipisah koma), pilih maks. 6 hasil kerja dari task selesai. Bila publik: tautan, tombol Salin, dan kode QR. Halaman publik `/p/{token}` (tanpa login, tanpa NIM, noindex): kepala bergradasi hijau merek dengan pil "Terverifikasi SIMAGTI", avatar inisial, nama, judul, prodi/kampus, posisi/tempat/periode/status; kartu angka (minggu, hari tercatat, task selesai, nilai akhir bila diizinkan); Tentang; Keahlian; Hasil kerja pilihan; Goal tercapai; peta kontribusi; tautan verifikasi sertifikat bila ada.

- Pembaruan: halaman verifikasi memakai tab **Sertifikat / Keterangan nilai** (bawaan sertifikat). Tab nilai berisi rincian lengkap: huruf mutu + nilai akhir, penilai (Company Supervisor) dan validator (dosen pembimbing), tiap kategori dengan bobot, kontribusi, rata-rata, dan nilai setiap pertanyaan, serta baris rumus nilai akhir. Tanpa JavaScript kedua bagian tampil berurutan; `#nilai` di alamat langsung membuka tab nilai.
- KP Wrapped: semua isi rata tengah, kicker berupa pil kaca, cincin cahaya halus di latar setiap slide.
- KP Wrapped (animasi): latar slide masuk dengan zoom halus, isi muncul dari kabur ke tajam bertahap, angka besar "fokus" dari blur, cincin latar bernapas dan cahaya melayang, kilau menyapu kicker dan kartu rangkuman, cincin task terisi dari 0 (`@property --pv`), lencana nilai memantul lalu berpendar dengan halo, konfeti di slide nilai dan rangkuman. Semua mati bila pengguna memilih kurangi gerak.
- Keterangan nilai di halaman verifikasi: huruf mutu dalam lingkaran, nilai akhir dengan batang tipis, status hasil sebagai satu baris tenang bertanda centang (tanpa pil).
- Peta kontribusi: ukuran kotak dihitung dari lebar kartu (8-40px) sehingga kisi memenuhi kartu di desktop dan muat tanpa geser di HP.

### Rapikan menyeluruh (14 November 2026)

Audit otomatis semua halaman per peran (desktop 1280px dan HP 390px: elemen bertumpuk, meluber, berdempet, teks terpotong) lalu tinjauan visual. Yang dirapikan:
- Notifikasi: ikon kepala halaman (lonceng) seperti halaman lain; kartu setinggi isinya sehingga tidak ada ruang kosong besar. Halaman Ganti sandi ikut berikon gembok.
- Hasil KP: tabel tidak lagi memunculkan scrollbar mendatar di desktop; nama perusahaan boleh turun baris.
- Aktivasi Company Supervisor: tombol Detail dan Undang sebaris dengan nama bila muat.
- Log Aktivitas: kode aksi mentah (login/logout) dihapus dari baris; kolom pengguna lebih lebar agar nama tidak terpotong.
- Kartu mahasiswa di Pantau: "Perlu konfirmasi" jadi baris tenang bertitik di bawah posisi, bukan pil yang menyempitkan nama.
- Angka ringkasan di kartu samping (mis. Aktivitas): angka ringkas, bukan oval lebar.
- Hasil KP detail: tombol Lihat sertifikat selebar kartu di bawah ringkasan keputusan, tidak lagi menempel di tepi kotak.
- Tag pada kartu task: jarak antarbaris 6px.

### Fitur peran lain (14 November 2026)

- **Nilai Sidang** (kaprodi): dua kartu, "Menunggu nilai sidang" (baris berisi nilai + tanggal + Simpan) dan "Sudah dinilai" (huruf mutu besar + angka kecil, tombol Ubah membuka form). Status kosong memakai kotak tenang bercentang.
- **Rekrutmen** (detail mahasiswa): tombol "Rekomendasikan rekrut" di deret alat profil; setelah ada rekomendasi, kartu Rekrutmen berisi tiga langkah (Direkomendasikan, Ditawari kerja, Diterima / Menolak) bertitik dan bergaris, kutipan alasan, lalu aksi supervisor. Dashboard mahasiswa: kartu tawaran kerja bergradasi lembut emas-hijau dengan ikon bintang, tombol Tolak / Terima tawaran.
- **Laporan KP per bab** (Dokumen mahasiswa): daftar bab bernomor dalam lingkaran (hijau bercentang bila disetujui), ikon gedung kecil untuk bab yang ditinjau perusahaan, nama berkas + versi, catatan revisi di kotak merah muda, tombol Unggah/Ganti satu klik. Antrean tinjauan (supervisor, dosen): baris bab + umur antrean + Buka, catatan revisi + Minta revisi + Setujui; kartu Progres laporan dengan titik nomor bab berwarna status.

- 16 November 2026: **Peta kontribusi dihapus** dari dashboard mahasiswa, tab Aktivitas di detail mahasiswa, dan portofolio publik (permintaan pemilik). `App\Support\Contribution` tetap ada karena KP Wrapped memakai rentetan hari terpanjang.
- 16 November 2026: **Huruf mutu diatur bebas oleh admin** (Standar Penilaian) karena standar tiap kampus berbeda: baris Huruf + Batas bawah + Rentang otomatis (mis. B+ 74–79,9), tambah lewat baris kosong, hapus dengan mengosongkan baris (2–12 huruf). Urutan mengikuti batas bawah; nilai di bawah batas terendah mendapat E; nilai yang sudah divalidasi tetap memakai versi skala lamanya.
- 16 November 2026: **Pertanyaan penilaian pindah ke Kaprodi.** Menu kaprodi "Rubrik Penilaian" (`/kaprodi/bobot`) kini berisi Bobot kategori dan kartu Pertanyaan (tambah, ubah, hapus per kategori hard skill, soft skill, attitude; minimal satu per kategori; bila rubrik sudah dipakai, perubahan menjadi versi baru). Menu admin menjadi "Standar Nilai": dua kartu sejajar, Pilihan jawaban (label + nilai) dan Huruf mutu. Label jawaban bawaan: Sangat baik 95, Baik 85, Cukup baik 75, Tidak baik 60, Sangat tidak baik 45; satu skala untuk semua pertanyaan. Tombol jawaban di form Company Supervisor memuat label panjang (turun baris bila perlu).
- 16 November 2026: **Alat di kartu profil mahasiswa** (Koreksi posisi, Ganti supervisor, Rekomendasikan rekrut) menjadi satu deret tombol rata kanan (`.prof-tools`); tiap form dibuka sebagai dialog berlabel, tidak lagi menggantung di bawah tombol. **Tombol jawaban penilaian** selalu satu baris: lebar mengikuti label, tombol turun baris utuh bila tidak muat; di HP dua kolom.
- 16 November 2026: **Laporan per bab** dirapikan: baris berupa kolom tetap (nomor, judul, status, tombol) sehingga label status dan tombol sejajar di setiap bab; label "Belum diunggah" tidak ditampilkan (tombol Unggah sudah menjelaskan); tombol Unggah/Ganti bergaya garis, hanya bab yang perlu revisi memakai tombol hijau. Di HP status turun di bawah judul, tombol sama lebar di kanan.
- 16 November 2026: **Laporan KP sebagai linimasa** (pilihan pemilik): titik per bab dihubungkan garis vertikal (kosong = belum, cincin biru = ditinjau, merah "!" = revisi, hijau bercentang = disetujui; garis ikut hijau setelah bab disetujui), batang progres 4 segmen berwarna status di kepala kartu, status ditulis sebagai teks bertitik (bukan pil), catatan revisi berupa kutipan bergaris merah, tombol: Unggah (garis), ikon ganti berkas untuk bab yang sudah diunggah, Unggah ulang (hijau) untuk revisi.
- 16 November 2026: **Keterangan kartu angka** (`x-stat` di `.stat-grid`) tidak lagi berupa pil yang bisa turun dua baris: satu baris teks kecil bertitik warna, terpotong "…" bila terlalu panjang (teks lengkap di tooltip). Keterangan panjang diringkas (mis. "Penilaian akhir belum dikirim" menjadi "Belum dikirim").
- 16 November 2026 (koreksi): **pertanyaan penilaian kembali dibuat Admin** (menu Standar Penilaian: Pertanyaan, Skala nilai, Huruf mutu); Kaprodi kembali hanya mengatur Bobot Penilaian. Label jawaban baru (Sangat baik, Baik, Cukup baik, Tidak baik, Sangat tidak baik) tetap. Kartu angka tetap gaya semula; kotak abu di dalamnya kini rata (bagian angka tidak lagi turun 4px), dan di bawah 1200px angka dan keterangan bersusun agar satuan tidak tertimpa.
- 17 November 2026: **Pertanyaan penilaian bawaan dirumuskan ulang** (tetap 4 hard skill, 4 soft skill, 6 attitude agar perhitungan tidak berubah), masing-masing dengan keterangan singkat yang tampil di bawah pertanyaan pada form Company Supervisor. Migrasi data menerapkannya hanya bila admin belum mengubah pertanyaan bawaan; rubrik yang sudah dipakai dibuat versi baru. Draf penilaian yang belum dikirim dipindah ke pertanyaan baru beserta jawabannya (per urutan).
- 17 November 2026: **Dialog muncul sekali.** Dialog di dalam `.container` sempat ikut jeda animasi masuk halaman (50 sampai 300 ms), sehingga tampil penuh lalu "muncul lagi"; jeda kini dinolkan untuk `.dialog[open]`.
- 17 November 2026: **Tombol jawaban penilaian tidak berdempet**: lima tombol sama lebar dengan jarak 10px dan jarak pertanyaan ke tombol lebih lega. Susunan mengikuti lebar kartu: 5 sejajar (kartu lebar), 3 + 2 di tengah (sedang, mis. desktop 1280 dengan kolom samping), 2 + 2 + 1 penuh (HP). Label tidak pernah terpotong atau turun baris.
- 17 November 2026: **Tawaran kerja resmi**: supervisor mengirim detail pekerjaan, penempatan, hubungan kerja, kontak HR, surat PDF, dan batas jawaban (bawaan 7 hari) lewat dialog; mahasiswa membuka halaman Tawaran Kerja (surat tampil di halaman), lalu Terima tawaran atau Tolak tawaran dengan alasan wajib. Pengingat H-3 dan H-1; lewat batas menjadi Kedaluwarsa (bukan menolak) dan supervisor bisa memperpanjang. Dosen dan kaprodi hanya melihat status dan posisi.
- 18 November 2026: **Rekap KP** memakai kartu angka gaya A, segmen filter berbentuk pil (status), tabel dengan huruf mutu tebal + angka kecil, status rekrutmen sebagai teks bertitik. **Sidang KP**: tanggal sebagai ubin kecil (angka + bulan) di kiri baris, dialog jadwal dan dialog nilai. **Template Task**: kartu per template dengan pratinjau per minggu (hari + judul, maks. 3 per minggu), formulir baris Minggu/Hari/Task dengan tombol "Tambah baris", dialog terapkan berisi daftar mahasiswa bercentang. **Lowongan**: kartu lowongan (inisial perusahaan, posisi, lokasi/kuota/tutup, ringkasan, keahlian, Detail, tombol "Ajukan KP di sini"); formulir pengajuan menampilkan pita "Diisi dari lowongan".
- 18 November 2026: **Penyempurnaan visual menyeluruh.** Ikon di samping teks diturunkan 7% ukurannya (koreksi optis: tinta huruf Geist berada 1-1,5px di bawah tengah baris). Kolom input putih bersih, tinggi 44px, sudut 12px, fokus berupa cincin halus; label 13px tebal; jarak formulir 18-20px. Tabel: jarak sel seragam, kolom angka di tengah, sorotan baris saat kursor lewat; Rekap KP tiap sel satu baris sehingga tinggi baris seragam. Kartu lowongan didesain ulang: pita gradasi lembut, tanda perusahaan menumpang di garis pita, fakta tiga kolom berlabel (lokasi, kuota, bidang), keahlian sebagai tag halus, kaki kartu dengan Lihat detail (dialog) dan Ajukan KP.
- 18 November 2026: **Halaman masuk bergaya kaca putih** (mengikuti referensi pemilik): latar putih dengan cahaya lembut hijau-biru dan titik halus, wadah kaca jernih (blur 24px). Kiri: logo, judul, label di atas kolom, kolom kaca putih 48px, Ingat saya + Lupa kata sandi, tombol Masuk bergradasi hijau. Kanan: panel hijau bergradasi dengan sudut kanan atas melengkung ke dalam (mask), garis pancar, kutipan pengguna yang bergeser otomatis (7 detik, tombol panah terang/gelap, berhenti bila kurangi gerak), kartu kaca putih di bawah dengan ikon bintang bercincin kaca dan tumpukan inisial. Di bawah 920px panel turun ke bawah formulir.

## Halaman masuk (Nov 2026)

Kartu kaca terbelah: kiri formulir, kanan panel hijau berlatar cairan seperti kain (`.gl-silk`) dengan alur magang empat tahap (`.gl-flow`) dan kartu kaca "Satu akun untuk setiap peran". Gaya `gl-*` di `public/css/kp.css`.

## Landing page (Nov 2026)

Halaman depan tamu di `/` (`LandingController`, `resources/views/landing.blade.php`, `public/css/landing.css`). Pengguna yang sudah masuk diarahkan ke dasbor. Hijau SIMAGTI + aksen lime, kartu gelap sebagai pembanding. Bagian: hero dengan tiga kartu angka nyata, kartu per peran dengan cuplikan layar mini, pita lime berisi alur empat tahap, tiga lowongan terbaru yang dibuka, penutup gelap. Animasi hanya urutan pembuka di hero.

### Portofolio mahasiswa (18 November 2026)

Menggantikan portofolio KP berbasis task. Diisi mahasiswa sejak sebelum magang di menu **Portofolio**: Tentang saya (identitas kampus otomatis, judul singkat, deskripsi, IPK + sakelar tampilkan IPK), Minat (pil, maks. 5), Skill (baris nama + tingkat Pemula/Menengah/Mahir), Bagikan (tautan publik + QR), Pengalaman magang (otomatis: tempat magang terakhir dan Company Supervisor), Proyek (dialog tambah/ubah, gambar pratinjau, teknologi, tautan), Sertifikat pelatihan (dialog, berkas PDF/gambar, tautan kredensial).

Halaman tampil `portofolio/publik.blade.php` (prefiks `pv-`) dipakai untuk tautan publik `/p/{token}` dan untuk pihak terkait `/portofolio/mahasiswa/{id}`: kepala hijau dengan avatar lime, minat, kotak IPK gelap; kolom kiri Tentang, Proyek, Sertifikat; kolom kanan Skill per tingkat dan kartu gelap Pengalaman magang. Yang boleh melihat (`Portfolio::canBeViewedBy`): pemilik, kaprodi/TU/admin, dosen pembimbing mahasiswa itu, Company Supervisor yang membimbingnya atau perusahaan tempat mahasiswa mengajukan. Tautan masuk: tombol Portofolio di detail Pantau, kolom di daftar Mahasiswa dan Rekap KP, dan bagian "Pengajuan masuk" di Lowongan Magang.
