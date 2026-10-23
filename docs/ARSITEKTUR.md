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

## Alur pengajuan KP (v2)

```
Mahasiswa mengajukan  → SubmitInternshipApplication (pilih jalur: Magang [KP Industri / MBKM Mandiri] atau Seminar [Pelaksana / Peserta];
                                                     cek minimal semester V, MBKM semester 5-7; magang menyimpan data Company Supervisor)
Biro memverifikasi    → ReviewBiroVerification      (hanya magang; perusahaan yang sudah mitra otomatis lolos)
Kaprodi memutuskan    → ReviewKaprodiApproval       (setuju: "berjalan" + dosen pembimbing; seminar langsung ke kaprodi)
TU Fakultas           → ActivateCompanySupervisor   (dikelompokkan per perusahaan + email supervisor: undang, tautkan akun lama, atau koreksi email)
Company Supervisor    → AcceptInvitation            (link sekali pakai 14 hari; satu undangan memasang semua mahasiswa kelompoknya)
```

## Alur kegiatan (v2)

```
Mahasiswa: silabus      → SyllabusWorkflow (hard skill / soft skill / attitude, minimal satu per kelompok, kirim → disetujui atau direvisi)
Mahasiswa: board        → CreateTask, MoveTask (proyek berkode seperti ORD-12, kolom bebas, checklist, bukti, komentar)
Mahasiswa: timesheet    → LogTime (jam per hari maks 24, foto opsional di disk privat)
Supervisor / dospem     → pantau per kloter, komentar, setujui silabus, koreksi posisi, unduh Excel / cetak PDF
```

## Alur penilaian akhir

```
Supervisor mengisi rubrik → SaveAssessment    (draft boleh sebagian; nilai akhir = jumlah nilai x bobot; seminar: dospem)
Supervisor mengirim       → SaveAssessment    (semua aspek wajib terisi; status "menunggu validasi", tidak bisa diubah lagi)
Dosen pembimbing          → ReviewAssessment  (validasi: nilai + huruf mutu + versi skala dikunci permanen
                                               | kembalikan: wajib catatan, kembali ke draft, supervisor memperbaiki)
Mahasiswa               → dashboard menampilkan nilai hanya setelah tervalidasi
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
