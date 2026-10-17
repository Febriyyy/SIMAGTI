# Naskah Demo: Presentasi Progres (Senin, 19 Oktober 2026)

Waktu sekitar 10 menit. Kata sandi semua akun: `Simagti2026` (daftar lengkap di `docs/AKUN.md`).

## Persiapan (sebelum presentasi)

```powershell
cd D:\phkm\kp-monitor
php artisan migrate:fresh --seed
php artisan test        # harus 118 lolos
composer run dev
```

Buka `http://localhost:8000`. Siapkan beberapa jendela penyamaran (incognito) supaya bisa login sebagai beberapa peran tanpa logout.
Siapkan juga satu berkas PDF atau PPT kecil di laptop untuk demo lampiran.

## Alur

**1. Gambaran: kaprodi** · `arief.hakim@kampus.ac.id` · 1 menit
Dashboard: 9 mahasiswa magang aktif di 4 perusahaan, **Perlu perhatian** (Dimas lebih dari seminggu tanpa bukti, Aulia progres rendah, Fajar hampir selesai tapi belum dinilai), sebaran huruf mutu, dan ringkasan per perusahaan. Kartu "Persetujuan KP" berisi 1 KP mandiri (Ilham).
Poin bicara: semua angka dihitung langsung dari data, dan alasan "perlu perhatian" ditulis jelas.

**2. Statistik perusahaan** · klik "Statistik" pada PT Arunika Data Analitika · 1 menit
Durasi rata-rata, sebaran skill dalam persen, performa per aspek, dan sebaran huruf. Ini permintaan Bapak/Ibu dosen. Data yang sama tersedia di API `GET /api/v1/companies/{id}/stats`.

**3. Mentor memberi task dengan materi** · `andika.saputra@cakrawaladigital.co.id` · 2 menit
Task Mingguan → pilih Nabila → "Tambah task" → isi judul → **lampirkan PDF/PPT** → simpan. Ikon klip muncul di tabel. Buka detail task untuk melihat materinya.
Poin bicara: berkas disimpan privat, hanya Nabila dan mentornya yang bisa mengunduh; jenis dan ukuran berkas dibatasi, dan program yang diganti namanya menjadi .pdf tetap ditolak.

**4. Mentor menilai** · masih sebagai Andika · 1,5 menit
Penilaian Akhir → Nabila → isi 4 aspek → Simpan draft (nilai dan perkiraan huruf muncul) → **Kirim ke ketua divisi**.

**5. Ketua divisi memvalidasi** · `hendro.wibisono@cakrawaladigital.co.id` · 1,5 menit
Dashboard → "Menunggu validasi" berisi Rafi dan Nabila → buka Nabila → **Validasi dan kunci nilai**.
Bisa juga dicoba pada Rafi: "Kembalikan ke mentor" tanpa catatan akan ditolak.

**6. Mahasiswa** · `nabila.azzahra@mhs.kampus.ac.id` · 1 menit
Kartu "Nilai akhir" sudah terisi. Buka task baru dari langkah 3, lalu unduh materi dari mentor.

**7. Dosen pembimbing** · `bambang.sutrisno@kampus.ac.id` · 1 menit
Kolom bukti terakhir, nilai, status, dan daftar perlu perhatian beserta alasannya (Dimas dan Fajar).

**8. Penutup: kenapa bisa dipercaya** · 30 detik
- Setiap aturan ada di satu tempat (tabel di `DESIGN.md`).
- Nilai yang sudah divalidasi dikunci di model, dan huruf mutu disimpan bersama versi skalanya.
- Ada 118 tes otomatis, termasuk tes bahwa mentor, mahasiswa, atau ketua divisi lain tidak bisa ikut campur.

## Kalau ditanya "berapa persen?"

Alur inti yang sudah berjalan dari awal sampai akhir: login per peran, task mingguan beserta lampiran materi dan bukti, meeting, KP mandiri (pengajuan, verifikasi, persetujuan, undangan), data master, penilaian akhir dan validasi, dashboard data nyata, statistik perusahaan, API baca-saja. Yang belum: sertifikat PDF/QR, nilai sidang dan skala nilai (UI), konversi karyawan (UI), dokumen dan surat, lowongan, ekspor, email.
