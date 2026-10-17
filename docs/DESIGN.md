# DESIGN: Teknologi dan Penerapan

Dokumen ini menjelaskan bahasa dan teknologi yang dipakai di tiap bagian proyek, dan bagaimana penerapannya.

## 1. Ringkasan teknologi

| Bagian | Teknologi | Alasan |
|---|---|---|
| Bahasa backend | PHP 8.3 | Laravel 13 membutuhkan PHP 8.3+ |
| Framework | Laravel 13 | Struktur baku industri, autentikasi, validasi, ORM, tes bawaan |
| Database | MySQL (Laragon) | Relasional, mendukung foreign key dan unique constraint |
| Tes | PHPUnit, SQLite in-memory | Cepat, tidak menyentuh data asli |
| Frontend | Blade (template server) + CSS biasa | Tanpa build step, cocok untuk pemula dan hosting sederhana |
| JavaScript | Hampir tidak ada (hanya `select()` pada link salin) | Sederhana, minim bug |
| API | JSON, REST, Laravel Sanctum (token Bearer) | Standar, mudah dijelaskan |
| Font | Plus Jakarta Sans (Google Fonts) | Terbaca, tidak kaku |
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
| Penilaian yang sudah divalidasi tidak bisa diubah | model `Assessment` |
| Huruf mutu menyimpan versi skala nilainya | `grade_scale_version`, `Grading` |
| Nomor sertifikat dan token verifikasi unik | constraint tabel `certificates` |

### Struktur folder penting

```
app/Actions/          aturan bisnis (Tasks, Meetings, Applications, Invitations, Companies, Students, Placements, Auth)
app/Policies/         hak akses per data
app/Http/Requests/    validasi + otorisasi per aksi
app/Http/Controllers/ pengarah tipis (Api/V1 untuk API)
app/Http/Resources/   bentuk JSON API
app/Models/           37 model dan relasinya
app/Support/          Grading (skala nilai), Menu (sidebar per peran)
database/migrations/  20 berkas, 39 tabel
database/seeders/     MasterSeeder (aman untuk produksi), DemoSeeder
resources/views/      Blade
public/css/kp.css     seluruh gaya
tests/Feature/        85 tes
```

## 3. Database

MySQL, 39 tabel, relasi lewat foreign key. Rancangan lengkap dan ERD ada di `docs/DATABASE.md`. Prinsipnya: aturan yang tidak boleh dilanggar dijaga oleh database, bukan hanya oleh kode.

- `internships` adalah pusat. Mentor dan ketua divisi disimpan per penempatan di `internship_staff`.
- `internships.active_student_id` unik dan hanya terisi saat status `diajukan` atau `berjalan`.
- `weekly_meetings(internship_id, week_number)` unik, `internship_staff(internship_id, user_id, role)` unik.
- `locked_at` mengunci penempatan yang selesai.
- `grade_scales` berversi, huruf mutu disimpan bersama versinya.
- Bukti (`task_evidences`) dan komentar (`task_comments`) adalah tabel terpisah.
- Soft delete di tabel penting, riwayat status disimpan di tabel tersendiri.

## 4. Frontend

Blade merender HTML di server, CSS ditulis manual di satu berkas.

- **Gaya**: kartu putih bersudut membulat di atas latar biru keabuan, satu warna aksen biru, sidebar bergrup ("Menu", "Akun"), bilah atas dengan tanggal dan inisial pengguna.
- **Token desain** di `:root` pada `kp.css` (warna, jarak, radius). Mengubah tampilan seluruh situs cukup mengubah token.
- **Status** memakai chip: To Do abu-abu, In Progress biru, Completed hijau, menunggu kuning, ditolak merah.
- **Tanpa animasi** selain transisi warna 150 sampai 200 ms.
- **Responsif**: di layar kecil kolom kanan turun ke bawah, tabel bisa digulir.
- **Komponen Blade**: `icon`, `chip`, `person` (nama selalu bersama NIM), `progress`, `avatar`, `task-table`.
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
| GET | `/api/v1/companies/{id}/stats` | biro, kaprodi, staf perusahaan itu |
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

## 8. Menjalankan

```powershell
cd D:\phkm\kp-monitor
php artisan install:api          # sekali saja: memasang Sanctum dan tabel token
php artisan migrate:fresh --seed
php artisan test
composer run dev
```

`install:api` wajib dijalankan sebelum tes karena model `User` sekarang memakai trait Sanctum.

## 9. Batasan yang sengaja diambil

- Tanpa Vite/Tailwind: tidak ada langkah build, lebih mudah di-hosting.
- Email belum dikirim: link aktivasi undangan ditampilkan agar bisa disalin dan dikirim manual.
- API belum punya operasi tulis: ditambah setelah web stabil.
