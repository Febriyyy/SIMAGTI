# Daftar Akun SIMAGTI

Semua orang, perusahaan, dan email di bawah ini **fiktif**. Data dibuat agar terlihat seperti data sungguhan untuk demo dan pameran.
Data ini dimuat oleh `php artisan migrate:fresh --seed` (berkas `database/seeders/DataSeeder.php`).

**Kata sandi semua akun: `Simagti2026`**

> Daftar ini sengaja tidak ditampilkan di halaman login. Simpan berkas ini untuk keperluan demo saja.

## Kampus

| Peran | Nama | Email |
|---|---|---|
| Kaprodi | Dr. Arief Rahman Hakim, S.Kom., M.T. | arief.hakim@kampus.ac.id |
| Dosen Pembimbing | Dewi Lestari Wulandari, S.Kom., M.Kom. | dewi.lestari@kampus.ac.id |
| Dosen Pembimbing | Ir. Bambang Sutrisno, M.T. | bambang.sutrisno@kampus.ac.id |
| Dosen Pembimbing | Rizky Aditya Pratama, S.T., M.Cs. | rizky.pratama@kampus.ac.id |
| Biro Kerjasama | Wulan Sari Anggraini, S.E. | wulan.anggraini@kampus.ac.id |

## Perusahaan

| Perusahaan | Mentor | Ketua Divisi |
|---|---|---|
| PT Cakrawala Solusi Digital (Bandung) | Andika Saputra · andika.saputra@cakrawaladigital.co.id<br>Maya Kartika Sari · maya.sari@cakrawaladigital.co.id | Hendro Wibisono · hendro.wibisono@cakrawaladigital.co.id |
| PT Arunika Data Analitika (Jakarta Selatan) | Fitri Handayani · fitri.handayani@arunikadata.co.id | Yoga Prasetya Nugroho · yoga.nugroho@arunikadata.co.id |
| PT Sinergi Telematika Nusantara (Surabaya) | Agus Setiawan · agus.setiawan@sinergitelematika.co.id | Ratna Dewi Puspita · ratna.puspita@sinergitelematika.co.id |
| CV Kreasi Visual Mandala (Sleman) | Galih Permana · galih.permana@kreasivisual.id | Intan Permatasari · intan.permatasari@kreasivisual.id |

## Mahasiswa

| Nama | NIM | Email | Kondisi untuk demo |
|---|---|---|---|
| Nabila Azzahra Putri | 2210511021 | nabila.azzahra@mhs.kampus.ac.id | Cakrawala, rajin, **belum dinilai** (dinilai langsung saat demo) |
| Muhammad Rafi Alfarizi | 2210511034 | muhammad.rafi@mhs.kampus.ac.id | Cakrawala, penilaian **menunggu validasi** Hendro |
| Aulia Rahmawati | 2210512007 | aulia.rahmawati@mhs.kampus.ac.id | Cakrawala, progres task rendah |
| Dimas Arya Wicaksono | 2210511052 | dimas.arya@mhs.kampus.ac.id | Arunika, **lebih dari seminggu tanpa bukti** |
| Salsabila Khairunnisa | 2210512015 | salsabila.khairunnisa@mhs.kampus.ac.id | Arunika, **selesai**, nilai A, sertifikat, ditawari kerja |
| Fajar Nugraha Ramadhan | 2210512029 | fajar.nugraha@mhs.kampus.ac.id | Arunika, KP hampir selesai, nilai masih draft |
| Bagas Satria Putra | 2210511066 | bagas.satria@mhs.kampus.ac.id | Sinergi, berjalan |
| Annisa Fitriani | 2210511073 | annisa.fitriani@mhs.kampus.ac.id | Sinergi, baru mulai, rajin |
| Raka Mahendra | 2210511018 | raka.mahendra@mhs.kampus.ac.id | Sinergi, **selesai**, nilai B+ |
| Putri Ayu Lestari | 2210512041 | putri.ayu@mhs.kampus.ac.id | Kreasi Visual, berjalan |
| Kevin Pratama Halim | 2210511087 | kevin.pratama@mhs.kampus.ac.id | Kreasi Visual, penilaian **menunggu validasi** Intan |
| Zahra Amalia | 2210512053 | zahra.amalia@mhs.kampus.ac.id | KP mandiri PT Lumbung Tani, **menunggu verifikasi biro** |
| Ilham Maulana Yusuf | 2210511095 | ilham.maulana@mhs.kampus.ac.id | KP mandiri PT Medika, **menunggu persetujuan kaprodi** |
| Citra Kirana Dewi | 2210512062 | citra.kirana@mhs.kampus.ac.id | Belum punya tempat KP |

## Mengganti data

- Domain email kampus ada di konstanta `CAMPUS` dan `STUDENT` di `DataSeeder.php`.
- Kata sandi bersama ada di konstanta `PASSWORD`.
- Untuk tes otomatis dipakai data terpisah (`DemoSeeder`, akun `*@kp.test`), jadi mengubah `DataSeeder` tidak merusak tes.
