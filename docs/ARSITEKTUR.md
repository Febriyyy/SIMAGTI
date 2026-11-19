# ARSITEKTUR: Alur dan Jawaban untuk Panitia / Dosen

Rincian teknologi ada di `DESIGN.md`, struktur data di `DATABASE.md`. Dokumen ini fokus pada satu hal: bagaimana satu permintaan diproses dan kenapa begitu.

## Alur satu permintaan (contoh: mahasiswa memindah task di board)

1. **Rute** (`PATCH /kegiatan/tugas/{task}/pindah`) dan middleware `EnsureRole:mahasiswa`: hanya mahasiswa yang masuk.
2. **Policy** `WeeklyTaskPolicy::update` → `InternshipPolicy::manageActivities`: task harus milik mahasiswa itu, KP berjalan dan belum dikunci. Jika tidak, 403.
3. **Controller** `TaskController::move`: tipis, memvalidasi kolom dan posisi lalu memanggil Action, membalas JSON.
4. **Action** `MoveTask`: kolom harus milik board yang sama, urutan task di kolom disusun ulang dalam satu transaksi, log aktivitas dicatat.
5. **Model dan database**: `WeeklyTask` mengambil status dari arti kolom (To Do / In Progress / Selesai), mengisi `completed_at`, menolak simpan jika KP terkunci; nomor task unik per penempatan.

## Pembagian tugas tiap lapisan

| Lapisan | Satu tugasnya | Tidak boleh berisi |
|---|---|---|
| Rute + middleware | menyaring peran | aturan per data |
| Form Request | otorisasi + validasi isian | logika bisnis |
| Policy | siapa boleh atas data tertentu | validasi isian |
| Controller | menghubungkan | aturan bisnis |
| Action | aturan bisnis, transaksi, log | HTTP, tampilan |
| Model + constraint DB | aturan yang tak boleh dilanggar | alur permintaan |

Manfaat: web dan API memakai Action, Policy, dan scope yang sama, jadi aturan tidak ditulis dua kali.

## Alur pengajuan KP (v3, satu jenis KP)

```
Mahasiswa mengajukan  → SubmitInternshipApplication (cek minimal semester V; bidang usaha dari pilihan atau "Lainnya";
                                                     website opsional; data Company Supervisor disimpan di penempatan)
TU memverifikasi      → ReviewVerification          (perusahaan yang sudah mitra otomatis lolos)
Kaprodi memutuskan    → ReviewKaprodiApproval       (setuju: "berjalan" + dosen pembimbing; surat balasan boleh menyusul)
TU Fakultas           → ActivateCompanySupervisor   (dikelompokkan per perusahaan + email supervisor: undang, tautkan akun lama, atau koreksi email)
Company Supervisor    → AcceptInvitation            (link sekali pakai 14 hari; satu undangan memasang semua mahasiswa kelompoknya)
```

## Alur kegiatan (v3)

```
Mahasiswa: rencana      → ActivityPlanController (timesheet / Gantt: nama kegiatan + rentang tanggal di dalam periode KP)
Mahasiswa: goals        → GoalController (di samping board Kegiatan; tercapai otomatis lewat Goal::syncAchievement saat semua task-nya Done)
Mahasiswa: board        → CreateTask, MoveTask (task wajib di bawah rencana, opsional goal; proyek berkode seperti ORD-12, checklist, bukti)
Mahasiswa: catatan      → LogTime (catatan kegiatan harian tanpa jam, foto opsional di disk privat)
Supervisor / dospem     → pantau per kloter, tab Rencana (Gantt + goals), Activity monitor, komentar, koreksi posisi, unduh Excel / cetak PDF
```

## Alur penilaian akhir (standar kampus)

```
Admin                     → AssessmentStandardController (pertanyaan per kategori, skala nilai, huruf mutu;
                                                          pertanyaan yang sudah dipakai diubah sebagai versi baru)
Kaprodi                   → AssessmentWeightController   (bobot hard skill / soft skill / attitude, total 100%)
Supervisor (akhir KP)     → SaveAssessment    (dibuka 14 hari sebelum KP berakhir; klik skala per pertanyaan;
                                               nilai kategori = rata-rata, nilai akhir = jumlah nilai kategori x bobot)
Supervisor mengirim       → SaveAssessment    (semua pertanyaan wajib dinilai; bobot ikut disimpan; tidak bisa diubah lagi)
Dosen pembimbing          → ReviewAssessment  (validasi: nilai + huruf mutu + versi skala dikunci permanen
                                               | kembalikan: wajib catatan, kembali ke draft, supervisor memperbaiki)
Dosen pembimbing          → FinalizeResult    (Diterima / Tidak; nilai diambil dari penilaian tervalidasi; KP ditutup)
Mahasiswa                 → dashboard menampilkan nilai hanya setelah tervalidasi
```

Baris penilaian dikunci (`lockForUpdate`) saat diputuskan, jadi dua klik bersamaan tidak memproses dua kali.

## Dashboard pemantauan

Dashboard dosen pembimbing dan kaprodi dibangun oleh satu Action `BuildMonitoringDashboard` yang memakai `Internship::visibleTo`, sehingga tiap peran hanya menghitung mahasiswa yang memang boleh dilihatnya. Statistik perusahaan (`BuildCompanyStats`) dipakai bersama oleh halaman web dan API.

## Pertanyaan yang mungkin ditanyakan

**Bagaimana mencegah mahasiswa punya dua penempatan aktif?**
Tiga lapis: policy menolak pengajuan, aturan validasi menolak penempatan, dan kolom unik `active_student_id` di database menolak walau kode terlewat.

**Bagaimana mencegah supervisor melihat atau mengubah mahasiswa lain?**
`InternshipPolicy` dan `WeeklyTaskPolicy`, dan daftar data selalu lewat `Internship::visibleTo`. Supervisor hanya memantau dan berkomentar; task dan timesheet hanya bisa diubah mahasiswa pemiliknya. Ada tesnya untuk web dan API.

**Apa yang terjadi pada data setelah KP selesai?**
Penempatan dikunci (`locked_at`), task tidak bisa diubah, penilaian yang divalidasi tidak bisa diubah. Akun Company Supervisor tetap ada dan bisa ditautkan TU ke mahasiswa berikutnya tanpa akun kedua.

**Kalau skala nilai kampus berubah, apakah nilai lama ikut berubah?**
Tidak. Skala berversi dan setiap huruf mutu disimpan bersama versinya.

**Bagaimana kalau supervisor salah memberi nilai?**
Selama belum divalidasi, dosen pembimbing bisa mengembalikannya dengan catatan, lalu supervisor memperbaiki. Setelah divalidasi, nilai terkunci di level model, jadi tidak ada jalan pintas untuk mengubahnya.

**Dari mana angka "perlu perhatian"?**
Dihitung dari data nyata: tanggal kegiatan terakhir (timesheet atau bukti), persentase task selesai, dan sisa hari KP dibanding status penilaian. Batasnya ditulis sebagai konstanta di satu tempat.

**Bagaimana keaslian sertifikat diperiksa?**
Setiap sertifikat punya nomor unik dan token acak. Endpoint publik `/api/v1/certificates/{token}` menjawab valid atau tidak tanpa membuka NIM.

**Bagaimana API diamankan?**
Token Bearer (Sanctum), pembatasan laju, respons JSON, token akun nonaktif ditolak, dan aturan akses sama dengan web.

**Bagaimana kalau email undangan tidak sampai?**
Link aktivasi tampil di halaman Aktivasi Supervisor milik TU Fakultas dan bisa disalin lalu dikirim manual (email otomatis menyusul setelah SMTP siap).

## Yang belum ada

Lihat bagian "Belum dikerjakan" di `RIWAYAT-PROYEK.md`.

## Pengingat otomatis (November 2026)

Perintah `php artisan kp:pengingat` (aksi `App\Actions\Reminders\SendReminders`, notifikasi `App\Notifications\KpReminder`) dijadwalkan setiap hari pukul 07.00 di `routes/console.php`. Pengingat masuk ke lonceng notifikasi dan email (selama `MAIL_MAILER=log`, email hanya tercatat di `storage/logs`).

| Penerima | Kapan | Frekuensi maksimal |
|---|---|---|
| Mahasiswa | 3 hari tidak mengisi catatan (hanya hari kerja) | sekali per 3 hari |
| Mahasiswa | task belum selesai, tenggat hari ini s.d. 2 hari lagi | sekali per task per tenggat |
| Company Supervisor | penilaian akhir dibuka (H-14) dan belum dikirim | sekali |
| Company Supervisor | sisa KP <= 3 hari dan penilaian belum dikirim | sekali |
| Dosen pembimbing | bimbingan menunggu konfirmasi lebih dari 3 hari (dirangkum per dosen) | sekali per 3 hari |

Pengingat yang sama tidak dikirim ulang: setiap pengingat menyimpan kunci (`data.key`) di tabel `notifications` dan dicek sebelum kirim.

**Saat hosting:** tambahkan cron `* * * * * cd /path/proyek && php artisan schedule:run >> /dev/null 2>&1` dan setel SMTP di `.env` (`MAIL_MAILER=smtp`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`). Uji manual kapan saja dengan `php artisan kp:pengingat`.

## Pengingat jadwal, email kabar penting, dan Google Calendar (19 November 2026)

- **Pengingat jadwal**: `php artisan kp:pengingat-jadwal` (`SendReminders::upcoming`) jalan tiap 15 menit. Bimbingan terjadwal dari dosen dan sidang KP diingatkan sekali dalam 24 jam sebelum mulai dan sekali lagi sekitar 1 jam sebelumnya (lonceng + email). Penerima: mahasiswa; dosen pembimbing (dan penguji untuk sidang) juga mendapat pengingat.
- **Email kabar penting**: `Notify::send(..., email: true)` mengirim salinan email (`App\Notifications\KpEmail`) untuk keputusan pengajuan KP, jadwal bimbingan dari dosen, jadwal sidang, permintaan revisi bab, berkas koreksi dari dosen, dan nilai sidang yang disahkan. Kegagalan SMTP dicatat di log dan tidak menggagalkan aksi pengguna.
- **Google Calendar**: `App\Support\CalendarLink` membuat tautan "Tambah ke Google Calendar" (formulir acara yang sudah terisi; tanpa API, tanpa kunci, tanpa biaya). Muncul di kartu bimbingan berikutnya, detail jadwal bimbingan dosen, dan jadwal sidang di Status KP.
- **Riwayat versi laporan**: setiap unggahan bab disimpan di `report_chapter_versions`; berkas lama tidak lagi dihapus.

**Pilihan SMTP saat hosting** (pilih satu, isi di `.env`): email bawaan hosting cPanel (biasanya sudah termasuk paket), Gmail dengan App Password (cocok untuk uji coba, ada batas kirim harian), atau layanan email transaksional seperti Brevo, Resend, Mailgun, atau Amazon SES (punya paket gratis dengan kuota; cek harga terbaru di situs masing-masing). Setel `MAIL_FROM_ADDRESS` ke alamat domain sendiri agar tidak masuk spam.

## Ulasan tempat magang, berita acara sidang, PWA (19 November 2026)

- **Ulasan tempat magang**: kartu di Status KP setelah magang berakhir (`CompanyReviewController`, `pengajuan/_ulasan`). Rating tampil di Lowongan dan halaman perusahaan.
- **Berita acara sidang**: `/sidang/{internship}/berita-acara` (`DefenseMinutesController`, `sidang/berita-acara`), halaman cetak A4 tegak; dibuka mahasiswa, dosen pembimbingnya, dan kaprodi setelah nilai sidang disahkan. Simpan PDF lewat jendela cetak browser (tanpa pustaka PDF).
- **PWA**: `public/manifest.webmanifest`, `public/sw.js`, `public/offline.html`, ikon di `public/icons/`, disisipkan lewat `partials/pwa`. Service worker hanya menyimpan aset statis; halaman HTML tidak pernah disimpan karena berisi data pribadi. **Wajib HTTPS saat hosting** (service worker hanya aktif di HTTPS atau localhost). Bila daftar aset di `sw.js` berubah, naikkan `VERSION`. Pastikan server menyajikan `.webmanifest` dengan tipe `application/manifest+json`.
