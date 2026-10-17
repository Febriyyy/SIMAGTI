# ARSITEKTUR: Alur dan Jawaban untuk Panitia / Dosen

Rincian teknologi ada di `DESIGN.md`, struktur data di `DATABASE.md`. Dokumen ini fokus pada satu hal: bagaimana satu permintaan diproses dan kenapa begitu.

## Alur satu permintaan (contoh: mentor mengubah status task)

1. **Rute** (`PATCH /mentor/tugas/{task}`) dan middleware `EnsureRole:mentor`: hanya peran mentor yang masuk.
2. **Form Request** `UpdateTaskRequest`: `authorize()` memanggil `WeeklyTaskPolicy::update`, yaitu mentor harus pembimbing mahasiswa itu dan KP belum dikunci. Jika tidak, 403. Baru setelah itu isian divalidasi.
3. **Controller** `MentorTaskController::update`: tipis, hanya memanggil Action dan mengarahkan balik.
4. **Action** `UpdateWeeklyTask`: dalam satu transaksi mengubah status, menambah komentar, mencatat log aktivitas.
5. **Model dan database**: `WeeklyTask` menolak simpan jika penempatan terkunci, dan mengisi `completed_at` otomatis.

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

## Alur KP mandiri

```
Mahasiswa mengajukan  → SubmitIndependentInternship (perusahaan baru, penempatan "diajukan", 2 langkah persetujuan, undangan)
Biro memverifikasi    → ReviewBiroVerification   (setuju: perusahaan terverifikasi | tolak: penempatan "ditolak")
Kaprodi memutuskan    → ReviewKaprodiApproval    (hanya setelah biro setuju; setuju: "berjalan" + dosen pembimbing)
Mentor/ketua divisi   → AcceptInvitation         (link sekali pakai, berlaku 14 hari, membuat akun dan memasang ke penempatan)
```

## Alur penilaian akhir

```
Mentor mengisi rubrik   → SaveAssessment    (draft boleh sebagian; nilai akhir = jumlah nilai x bobot)
Mentor mengirim         → SaveAssessment    (semua aspek wajib terisi; status "menunggu validasi", mentor tidak bisa mengubah)
Ketua divisi memutuskan → ReviewAssessment  (validasi: nilai + huruf mutu + versi skala dikunci permanen
                                             | kembalikan: wajib catatan, kembali ke draft, mentor memperbaiki)
Mahasiswa               → dashboard menampilkan nilai hanya setelah tervalidasi
```

Baris penilaian dikunci (`lockForUpdate`) saat diputuskan, jadi dua klik bersamaan tidak memproses dua kali.

## Dashboard pemantauan

Dashboard ketua divisi, dosen pembimbing, dan kaprodi dibangun oleh satu Action `BuildMonitoringDashboard` yang memakai `Internship::visibleTo`, sehingga tiap peran hanya menghitung mahasiswa yang memang boleh dilihatnya. Statistik perusahaan (`BuildCompanyStats`) dipakai bersama oleh halaman web dan API.

## Pertanyaan yang mungkin ditanyakan

**Bagaimana mencegah mahasiswa punya dua penempatan aktif?**
Tiga lapis: policy menolak pengajuan, aturan validasi menolak penempatan, dan kolom unik `active_student_id` di database menolak walau kode terlewat.

**Bagaimana mencegah mentor melihat atau mengubah mahasiswa mentor lain?**
`InternshipPolicy` dan `WeeklyTaskPolicy`, dan daftar data selalu lewat `Internship::visibleTo`. Ada tesnya untuk web dan API.

**Apa yang terjadi pada data setelah KP selesai?**
Penempatan dikunci (`locked_at`), task tidak bisa diubah, penilaian yang divalidasi tidak bisa diubah. Akun mentor dan ketua divisi tetap ada untuk mahasiswa berikutnya.

**Kalau skala nilai kampus berubah, apakah nilai lama ikut berubah?**
Tidak. Skala berversi dan setiap huruf mutu disimpan bersama versinya.

**Bagaimana kalau mentor salah memberi nilai?**
Selama belum divalidasi, ketua divisi bisa mengembalikannya dengan catatan, lalu mentor memperbaiki. Setelah divalidasi, nilai terkunci di level model, jadi tidak ada jalan pintas untuk mengubahnya.

**Dari mana angka "perlu perhatian"?**
Dihitung dari data nyata: tanggal bukti terakhir, persentase task selesai, dan sisa hari KP dibanding status penilaian. Batasnya ditulis sebagai konstanta di satu tempat.

**Bagaimana keaslian sertifikat diperiksa?**
Setiap sertifikat punya nomor unik dan token acak. Endpoint publik `/api/v1/certificates/{token}` menjawab valid atau tidak tanpa membuka NIM.

**Bagaimana API diamankan?**
Token Bearer (Sanctum), pembatasan laju, respons JSON, token akun nonaktif ditolak, dan aturan akses sama dengan web.

**Bagaimana kalau email undangan tidak sampai?**
Link aktivasi tampil di halaman pengajuan mahasiswa dan bisa disalin lalu dikirim manual.

## Yang belum ada

Lihat bagian "Belum dikerjakan" di `RIWAYAT-PROYEK.md`.
