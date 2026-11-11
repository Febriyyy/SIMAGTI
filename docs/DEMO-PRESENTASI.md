# Naskah Demo: Pameran SIMAGTI (Selasa, 5 Januari 2027)

Waktu sekitar 12 menit untuk alur lengkap, atau 5 menit untuk versi singkat (langkah bertanda ★).
Kata sandi semua akun: `Simagti2026` (daftar lengkap dan kondisi tiap mahasiswa di `docs/AKUN.md`). Semua data fiktif.

## Persiapan (sebelum pengunjung datang)

```cmd
cd D:\phkm\kp-monitor
php artisan migrate:fresh --seed
php artisan test
php artisan serve
```

- Buka `http://localhost:8000` (atau alamat hosting).
- Siapkan beberapa jendela penyamaran (incognito) agar bisa masuk sebagai beberapa peran tanpa keluar-masuk.
- Siapkan satu PDF kecil untuk demo lampiran task, dan ponsel untuk memindai QR sertifikat.
- Data demo dihitung dari tanggal hari ini, jadi jalankan `migrate:fresh --seed` di pagi hari pameran agar "berakhir ±10 hari lagi" dan sejenisnya tetap pas.

## Alur

**1. ★ Masuk dan gambaran masalah** · halaman login · 30 detik
Poin bicara: informasi KP biasanya tersebar di formulir, surat, logbook, dan chat. SIMAGTI menyatukannya untuk enam peran: mahasiswa, Company Supervisor, dosen pembimbing, kaprodi, TU Fakultas, dan admin.

**2. ★ Mahasiswa menyusun dan mencatat kegiatan** · `nabila.azzahra@mhs.kampus.ac.id` · 2,5 menit
- **Dashboard**: kartu Kemajuan KP dan Gantt ringkas.
- **Timesheet**: rencana kegiatan dalam Gantt (4 rencana, warna per rencana), coba zoom Hari / Minggu / Penuh, lipat dan buka task di bawah rencana. Catat satu kegiatan harian.
- **Kegiatan**: board task di samping goals (2 dari 4 goals tercapai). Pindahkan satu task ke Done; goal tercapai otomatis bila semua task-nya selesai.
- Poin bicara: task wajib berada di bawah rencana kegiatan, jadi pekerjaan harian selalu terhubung ke rencana.

**3. Mahasiswa yang baru mulai** · `larasati.wening@mhs.kampus.ac.id` · 30 detik
Belum menyusun rencana kegiatan, sehingga task belum bisa dibuat. Tunjukkan keadaan kosong yang menuntun langkah berikutnya.

**4. ★ Company Supervisor memantau** · `galih.permana@kreasivisual.id` · 1,5 menit
- **Mahasiswa Magang**: daftar ringkas (nama + posisi), detail di halaman Buka.
- **Aktivitas**: activity monitor kegiatan terbaru mahasiswa.
- **Pengajuan Perubahan**: Kevin minta perpanjangan, putuskan di sini.
- Poin bicara: supervisor diaktifkan lewat satu link undangan untuk semua mahasiswanya; tidak perlu membuat akun sendiri.

**5. ★ Dosen pembimbing: perlu perhatian dan validasi** · `dewi.lestari@kampus.ac.id` · 2 menit
- **Dashboard**: daftar "Perlu perhatian" beserta alasannya (tanpa catatan 7 hari, progres task < 60%, atau KP hampir berakhir tanpa nilai tervalidasi).
- **Validasi Penilaian**: buka penilaian yang menunggu validasi, validasi atau kembalikan dengan catatan (catatan wajib).
- **Hasil KP**: syarat hasil (penilaian tervalidasi dan minimal 5 bimbingan terkonfirmasi) tampil jelas. Setelah hasil Diterima, KP dikunci dan sertifikat terbit otomatis.

**6. ★ Sertifikat dengan QR** · `salsabila.khairunnisa@mhs.kampus.ac.id` · 1 menit
- **Status KP** → **Lihat sertifikat**: sertifikat A4 siap cetak / simpan PDF berisi nama, NIM, tempat dan periode KP, nilai akhir, serta QR.
- Pindai QR dengan ponsel: halaman verifikasi publik tanpa login menampilkan "Sertifikat sah", tanpa NIM.
- Poin bicara: perusahaan tempat alumni melamar bisa memastikan keaslian sertifikat sendiri.

**7. Kaprodi dan TU Fakultas** · `arief.hakim@kampus.ac.id`, `retno.kusumaningrum@kampus.ac.id` · 2 menit
- Kaprodi, **Persetujuan KP**: pengajuan Ilham menunggu persetujuan; setujui sambil menunjuk dosen pembimbing. **Bobot Penilaian**: total wajib 100%.
- TU, **Verifikasi KP Mandiri**: pengajuan Zahra. **Aktivasi Supervisor**: Farhan dan Tiara memakai satu undangan untuk supervisor yang sama. **Surat Pengantar** dan **Dokumen KP**: surat balasan Farhan menunggu diperiksa.

**8. Admin dan keamanan akun** · `wulan.anggraini@kampus.ac.id` · 1 menit
- **Akun**: ubah peran, nonaktifkan, dan **Atur ulang sandi**. Kata sandi sementara tampil sekali dengan tombol Salin.
- Masuk dengan kata sandi sementara itu: semua halaman diarahkan ke **Ganti kata sandi** sampai diganti. Sesi lain dan token API ikut berakhir.
- **Standar Penilaian** dan **Log Aktivitas**: setiap perubahan penting tercatat.

**9. Penutup: kenapa bisa dipercaya** · 30 detik
- Aturan bisnis ada di satu tempat (Action dan Policy), dijaga juga oleh constraint database, misalnya satu penempatan aktif per mahasiswa.
- KP yang selesai dikunci dan hanya bisa dibaca.
- Tes otomatis (`php artisan test`) memeriksa alur tiap peran, termasuk bahwa peran lain tidak bisa ikut campur.
- Tersedia API v1 (token Bearer) untuk integrasi, misalnya `GET /api/v1/certificates/{token}`.

## Kalau ditanya

- **"Bagaimana kalau lupa kata sandi?"** Admin mengatur ulang dan memberi kata sandi sementara yang wajib diganti saat masuk.
- **"Data mahasiswanya asli?"** Tidak. Semua nama, NIM, perusahaan, dan email fiktif.
- **"Bisa dipakai kampus lain?"** Identitas kampus dan aturan KP ada di `config/simagti.php` dan tabel `settings`, tanpa mengubah kode.
