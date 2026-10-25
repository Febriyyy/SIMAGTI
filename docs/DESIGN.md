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
| Radius | `--radius-xs`, `--radius-sm`, `--radius-md`, `--radius`, `--radius-lg`, `--radius-pill` | 8, 12, 16, 20, 28, 999px |

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

**7. Sebelum selesai.** Setiap halaman baru dicek di 1920, 1440, 1280, 768, 390, dan 375px: tidak ada geser horizontal halaman, tidak ada teks terpotong di HP, tombol di kanan, jarak sesuai skala.

## Aturan tata letak (hasil audit UI)

- **Kartu berdampingan** memakai `.split` (dua kolom sama lebar). Setiap kolom (`.col`) menumpuk kartunya sendiri, jadi kartu pendek tidak menyisakan celah kosong. Di layar ≤900px menjadi satu kolom.
- **Kolom tabel yang panjang** (nama mahasiswa, perusahaan) diberi `class="col-fluid"` dan lebar persen pada `<th>`. Kolom ini ikut lebar tabel, dan teks baru dipotong (…) kalau memang tidak muat. Nama lengkap tetap muncul saat kursor diarahkan.
- **Angka di tabel** (bobot, nilai, kontribusi) memakai `col-num`: rata kanan dengan lebar digit yang sama, jadi angka sejajar dengan judul kolomnya.
- **Baris berisi nama + tombol** (`.item-row`): nama dipotong, tombol tidak pernah tertimpa.
- **Area keputusan** (setujui/tolak) memakai `.decision`: dua kolom berlabel, di HP menjadi bertumpuk.

## Kartu angka, menu kiri, dan grafik

- **Kartu angka**: kartu pertama di setiap baris berwarna hijau tua. Saat kursor diarahkan ke kartu lain, hijau pindah ke kartu itu (transisi halus), lalu kembali ke kartu pertama saat kursor keluar.
- **Menu kiri** tetap di tempat saat halaman di-scroll (`position: sticky`). Di HP menu kembali berada di atas halaman seperti biasa.
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
