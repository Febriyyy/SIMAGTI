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

## 5. Status jujur

| Bagian | Status |
|---|---|
| Database, model, seeder, tes v3 | Terbukti jalan di komputer Anda |
| Refactor Form Request / Policy / Action | Kode selesai, lolos pemeriksaan sintaks, **belum dijalankan** |
| KP mandiri, data master, API v1 | Kode dan tes selesai, **belum dijalankan** |

Lingkungan tempat saya bekerja tidak bisa mengunduh paket Laravel (Packagist diblokir), jadi saya hanya bisa memeriksa sintaks dan konsistensi rute, kelas, dan view. Bukti sebenarnya adalah `php artisan test` di komputer Anda. Kemungkinan ada beberapa tes yang gagal pada putaran pertama; kirim hasilnya dan saya perbaiki.

Jumlah tes sekarang: 85 (KpFlow 16, Schema 18, ApplicationFlow 18, MasterData 16, Api 16, Example 1).

## 6. Belum dikerjakan

- Penilaian akhir mentor dan validasi ketua divisi (UI)
- Input nilai sidang, halaman skala nilai kaprodi
- UI konversi jadi karyawan
- Sertifikat PDF + QR
- Dokumen dan template, surat pengantar
- Lowongan magang, skill, template task (UI)
- Dashboard analitik dan ekspor Excel/PDF
- Email notifikasi
- Dashboard dospem, kaprodi, biro dengan data nyata

Urutan yang dikorbankan jika waktu mepet: lowongan, template laporan dan surat, ekspor laporan, status konversi lanjutan.

## 7. Pertanyaan yang masih terbuka

- Skala nilai C, D, E sesuai aturan kampus?
- Maksud dosen dengan "40%": jumlah fitur, jumlah halaman, atau alur utama?
- Maksud pertanyaan dosen soal API: arsitektur, integrasi, atau penyediaan data?
- Apakah mahasiswa boleh melihat status rekomendasi lebih awal (usul saya: hanya setelah "ditawari")?

## 8. Akun demo

Semua kata sandi `password`: kaprodi@kp.test, dospem@kp.test, biro@kp.test, mentor@kp.test, mentor2@kp.test, divisi@kp.test, divisi2@kp.test, mahasiswa@kp.test (Rina), dimas@kp.test, fajar@kp.test, salsa@kp.test (KP selesai), nadia@kp.test (KP mandiri menunggu).
