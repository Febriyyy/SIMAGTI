# Riwayat Proyek: Sistem Monitoring Kerja Praktik (KP Monitor)

Dokumen ini merangkum apa yang sudah kita bangun dari awal sampai sekarang, apa yang sudah terbukti jalan, dan apa yang belum.

## 1. Tujuan proyek

Website untuk memantau kerja praktik (magang) mahasiswa, dibuat untuk **pameran 5 Januari 2027** dan dinilai praktisi industri serta alumni. Prioritasnya:

1. Backend dan database yang rapi dan kokoh dulu, tampilan mengikuti.
2. Setiap aturan bisnis punya satu tempat yang jelas di kode, supaya pertanyaan panitia dan dosen bisa dijawab tanpa ragu.

## 2. Peran pengguna

| Peran | Tugas utama |
|---|---|
| Mahasiswa | Mengerjakan task mingguan, melampirkan bukti, mengajukan KP mandiri |
| Mentor (di perusahaan) | Memberi task, menilai pekerjaan, mengatur meeting mingguan |
| Ketua divisi | Mengonfirmasi dan memvalidasi penilaian |
| Dosen pembimbing | Memantau mahasiswa bimbingan |
| Kaprodi | Menyetujui KP mandiri, mengelola mahasiswa dan penempatan |
| Biro kerjasama | Mengelola perusahaan dan memverifikasi perusahaan KP mandiri |

Alumni sengaja ditunda ke tahap update.

## 3. Jadwal

| Tanggal | Target |
|---|---|
| 2 Nov 2026 | Pengecekan progres dosen: 40% fitur berjalan |
| sekitar 3 Des 2026 | Fitur dibekukan, hanya perbaikan bug |
| sekitar 22 Des 2026 | Hosting (2 minggu sebelum pameran) |
| 5 Jan 2027 | Pameran |

"100%" berarti semua fitur yang dipilih berjalan dari awal sampai akhir pada alur demo tanpa error.

## 4. Perjalanan pengerjaan

### Tahap 0: perencanaan (September sampai awal Oktober)
- Menyusun ide: peran, task mingguan, status, nilai sidang, lowongan, template sertifikat dan laporan, surat pengantar.
- Memilih Laravel + MySQL (Laragon) dan menyepakati fitur nilai tambah untuk industri: dashboard analitik dan ekspor, rubrik penilaian dan rekomendasi konversi jadi karyawan, verifikasi sertifikat lewat QR.
- Aturan nilai huruf (A 88, B+ 82, B 70, C+ 64, C 56, D 40, E 0) disimpan bisa diubah kaprodi dan dikunci saat penilaian divalidasi.
- Alur KP mandiri: mahasiswa mengajukan, biro memverifikasi perusahaan, kaprodi memberi persetujuan akhir, mentor dan ketua divisi diundang lewat link aktivasi.
- Dosen meminta dashboard per perusahaan (durasi, sebaran skill dalam persen, performa) dan menanyakan soal API.
- Membuat wireframe semua peran sebagai patokan.

### Tahap 1: fondasi (v1)
Laravel, login, 6 peran, task mingguan, log aktivitas. Tampilan awal ditolak karena terlalu berantakan.

### Tahap 2: gaya kartu seragam (v2)
UI dibuat ulang dengan gaya kartu putih di atas latar biru keabuan, sidebar bergrup, status berwarna, satu warna aksen. Kartu meeting dipindah ke dashboard, ditambah halaman detail task.

### Tahap 3: database lengkap (v3)
39 tabel dengan aturan yang dijaga langsung oleh struktur database, 37 model, seeder data demo, tes aturan, dan `docs/DATABASE.md` (ERD). Sudah dijalankan di komputer Anda: `migrate:fresh --seed` dan tes lolos.

### Tahap 4: arsitektur berlapis dan modul baru (v4, tahap ini)
Setelah Anda meminta fondasi yang jelas dan tidak ambigu:

- **Refactor** modul task dan meeting menjadi Form Request, Policy, dan Action. Controller menjadi tipis.
- **KP mandiri** lengkap: pengajuan, verifikasi biro, persetujuan kaprodi (menunjuk dosen pembimbing), undangan mentor dan ketua divisi dengan link yang bisa disalin.
- **Data master**: perusahaan (biro), mahasiswa (kaprodi), penempatan langsung (kaprodi), daftar penempatan (biro dan kaprodi).
- **API v1 baca-saja** dengan token Sanctum, plus verifikasi sertifikat publik.
- Tes baru untuk semua di atas.

### Tahap 5: penilaian akhir dan dashboard data nyata (v5, 17 Oktober 2026)
Untuk presentasi progres ke dosen pada 19 Oktober:

- **Penilaian akhir**: mentor mengisi rubrik (draft atau kirim), ketua divisi memvalidasi atau mengembalikan dengan catatan, lalu nilai dan huruf mutu terkunci bersama versi skalanya. Mahasiswa melihat nilainya di dashboard setelah divalidasi.
- **Dashboard dospem, kaprodi, dan ketua divisi**: memakai data nyata, yaitu bukti terakhir, daftar perlu perhatian beserta alasannya, status penilaian, dan sebaran huruf mutu. Kaprodi juga mendapat ringkasan per perusahaan dan antrean persetujuan, sedangkan ketua divisi mendapat antrean validasi.
- **Statistik per perusahaan** (permintaan dosen): durasi rata-rata, sebaran skill dalam persen, dan performa (rata-rata nilai, sebaran huruf, rata-rata per aspek). Tersedia di web (`/perusahaan/{id}`) dan di API.
- Perbaikan kecil: sapaan tidak lagi memakai gelar ("Selamat pagi, Hendra", bukan "Dr."), zona waktu aplikasi diganti ke Asia/Jakarta, dan tanggal bukti di data demo disesuaikan dengan minggu tugasnya.

### Tahap 6: tampilan SIMAGTI, lampiran task, data realistis (17 Oktober 2026)
- Tampilan baru untuk semua halaman (gaya campuran acuan Mondays dan Coursue), warna dan logo SIMAGTI, tabel yang tidak bertumpuk di layar 1280px ke atas.
- **Lampiran task**: mentor melampirkan PDF, PowerPoint, atau gambar (maks. 5 berkas, 10 MB per berkas) saat membuat task atau dari halaman detail. Berkas disimpan privat dan hanya bisa diunduh mahasiswa pemilik task dan mentornya.
- **Data realistis (fiktif)** menggantikan akun contoh: 28 akun bernama lengkap, 6 perusahaan, 14 mahasiswa dengan kondisi beragam. Kotak akun contoh di halaman login dihapus; daftar akun ada di `docs/AKUN.md`. Data lama (`*@kp.test`) dipakai khusus untuk tes otomatis.

## 5. Status jujur

| Bagian | Status |
|---|---|
| Database, model, seeder, tes v3 | Terbukti jalan di komputer Anda |
| v4: refactor, KP mandiri, data master, API v1 | Terbukti jalan: 86 tes lolos (17 Okt) |
| v5: penilaian akhir, dashboard data nyata, statistik perusahaan | Lolos tes dan dicoba di browser (SQLite) |
| v6: tampilan SIMAGTI, lampiran task, data realistis | 118 tes lolos dan alur dicoba di browser (SQLite). Belum dicoba di MySQL Laragon |

Jumlah tes sekarang: 118 (KpFlow 16, Schema 18, ApplicationFlow 18, MasterData 16, Api 16, AssessmentFlow 13, DashboardData 8, TaskAttachment 9, DataSeeder 2, Example 1).

## 6. Belum dikerjakan

- Input nilai sidang, halaman skala nilai kaprodi
- UI konversi jadi karyawan
- Sertifikat PDF + QR
- Dokumen dan template, surat pengantar
- Lowongan magang, skill, template task (UI)
- Ekspor Excel/PDF
- Email notifikasi
- Halaman rubrik (kaprodi mengubah aspek dan bobot)

Urutan yang dikorbankan jika waktu mepet: lowongan, template laporan dan surat, ekspor laporan, status konversi lanjutan.

## 7. Pertanyaan yang masih terbuka

- Skala nilai C, D, E sesuai aturan kampus?
- Maksud dosen dengan "40%": jumlah fitur, jumlah halaman, atau alur utama?
- Maksud pertanyaan dosen soal API: arsitektur, integrasi, atau penyediaan data?
- Apakah mahasiswa boleh melihat status rekomendasi lebih awal (usul saya: hanya setelah "ditawari")?

## 8. Akun demo

Lihat `docs/AKUN.md`. Kata sandi semua akun: `Simagti2026`. (Akun `*@kp.test` hanya ada di database tes otomatis.)
