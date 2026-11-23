# Deploy SIMAGTI ke shared hosting (cPanel)

Panduan langkah demi langkah memasang SIMAGTI di shared hosting ber-cPanel (mis. Hostinger, Niagahoster,
Rumahweb, IDCloudHost). Target pengumpulan karya: **11–17 Desember 2026**.

## 1. Syarat paket hosting

Pastikan paket yang dipilih punya semua ini (tanyakan ke CS bila tidak tercantum):

| Kebutuhan | Keterangan |
|---|---|
| PHP **8.3** atau lebih baru | Diatur di cPanel > *Select PHP Version* / *MultiPHP Manager* |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `fileinfo`, `zip`, `intl`, `openssl`, `curl`, `xml`, `ctype`, `bcmath` |
| MySQL / MariaDB | Satu database cukup |
| **Akses SSH (Terminal)** dan Composer | Untuk `composer install` dan `php artisan` |
| Cron Jobs | Untuk pengingat otomatis |
| SSL gratis (Let's Encrypt / AutoSSL) | Wajib: PWA dan cookie aman butuh HTTPS |
| Email domain | Untuk SMTP `noreply@domain` |

Tidak perlu Node.js: SIMAGTI tidak memakai langkah build (CSS dan JS sudah jadi di `public/`).

## 2. Siapkan di cPanel

1. **Domain**: arahkan domain/subdomain ke hosting, lalu aktifkan SSL (cPanel > *SSL/TLS Status* > *Run AutoSSL*).
2. **Database**: cPanel > *MySQL Databases* > buat database dan user, beri user itu *ALL PRIVILEGES*. Catat nama database, user, dan kata sandinya.
3. **Email**: cPanel > *Email Accounts* > buat `noreply@<domain>`. Buka *Connect Devices* untuk melihat host SMTP (biasanya `mail.<domain>`, port 465 SSL).
4. **PHP**: pilih versi 8.3 dan centang ekstensi di tabel atas.

## 3. Unggah kode

Struktur yang dianjurkan: kode Laravel **di luar** `public_html`, hanya isi folder `public/` yang terbuka ke internet.

```
/home/<akun>/
├── simagti/          ← seluruh repo (app, config, storage, vendor, ...)
└── public_html/      ← diarahkan ke simagti/public (lihat langkah 4)
```

Lewat SSH (Terminal di cPanel):

```bash
cd ~
git clone https://github.com/Febriyyy/SIMAGTI.git simagti
cd simagti
composer install --no-dev --optimize-autoloader
```

Bila hosting tidak punya git, unggah ZIP repo lewat *File Manager* lalu ekstrak ke `~/simagti`.

## 4. Arahkan domain ke folder `public`

Pilih salah satu:

- **Paling rapi**: cPanel > *Domains* > ubah *Document Root* domain menjadi `simagti/public`.
- **Bila document root tidak bisa diubah**: ganti `public_html` dengan symlink:
  ```bash
  cd ~ && mv public_html public_html_lama && ln -s ~/simagti/public public_html
  ```

## 5. Konfigurasi `.env`

```bash
cd ~/simagti
cp .env.production.example .env
nano .env          # isi APP_URL, DB_*, MAIL_* sesuai langkah 2
php artisan key:generate
```

Wajib di server: `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://...`.

## 6. Database dan izin folder

```bash
php artisan migrate --force
php artisan db:seed --force     # data master + data demo (akun contoh, kata sandi Simagti2026)
chmod -R 775 storage bootstrap/cache
```

`db:seed` mengisi data contoh untuk presentasi. Untuk pemakaian sungguhan, lewati perintah ini dan buat akun admin lewat `php artisan tinker`.

Tidak perlu `php artisan storage:link`: berkas unggahan disimpan privat dan disajikan lewat aplikasi setelah dicek hak aksesnya.

## 7. Percepat aplikasi

```bash
php artisan optimize        # cache config, route, view, event
```

Ulangi setiap kali `.env` atau kode diperbarui. Bila setelah mengubah `.env` tidak ada efek, jalankan `php artisan optimize:clear` lalu `php artisan optimize`.

## 8. Cron untuk pengingat otomatis

cPanel > *Cron Jobs* > tambahkan, setiap menit (`* * * * *`):

```
cd /home/<akun>/simagti && php artisan schedule:run >> /dev/null 2>&1
```

Jadwal yang berjalan: pengingat harian 07.00 (`kp:pengingat`) dan pengingat jadwal H-1 / 1 jam sebelumnya tiap 15 menit (`kp:pengingat-jadwal`). Bila `php` di cron bukan versi 8.3, pakai jalur lengkap, mis. `/usr/local/bin/ea-php83` (lihat di *MultiPHP Manager*).

## 9. Uji setelah deploy

- [ ] Buka `https://<domain>` → halaman depan tampil, gembok HTTPS aktif
- [ ] Masuk sebagai kaprodi/dosen/mahasiswa demo, buka beberapa menu
- [ ] Unggah satu berkas (mis. logo di Data Sertifikat) lalu buka lagi → tampil
- [ ] Picu satu notifikasi email (mis. jadwalkan sidang) → email masuk (cek juga folder spam)
- [ ] `https://<domain>/halaman-tidak-ada` → halaman 404 SIMAGTI (bukan pesan galat Laravel)
- [ ] Di HP: menu *Tambahkan ke layar utama* muncul (PWA)
- [ ] Besoknya: cek `storage/logs/` tidak berisi galat cron

## 10. Memperbarui setelah ada perubahan

```bash
cd ~/simagti
php artisan down
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan up
```

## Masalah yang sering muncul

| Gejala | Penyebab dan solusi |
|---|---|
| Halaman putih / galat 500 | Izin `storage` dan `bootstrap/cache` (langkah 6) atau `APP_KEY` kosong. Lihat `storage/logs/laravel-*.log` |
| Tampilan tanpa gaya (CSS tidak termuat) | Domain belum mengarah ke folder `public` (langkah 4) atau `APP_URL` salah |
| `could not find driver` | Ekstensi `pdo_mysql` belum aktif |
| Ekspor Excel gagal | Ekstensi `zip` belum aktif |
| Email tidak terkirim | Cek `MAIL_*`; pengiriman yang gagal dicatat di log tanpa menggagalkan aksi |
| Tautan masih `http://` | `APP_ENV` belum `production` atau cache lama: `php artisan optimize:clear && php artisan optimize` |
