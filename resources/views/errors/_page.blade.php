{{--
    Halaman galat bergaya SIMAGTI (403, 404, 419, 429, 500, 503). Berdiri sendiri: tidak butuh sesi atau data pengguna.
    Layar penuh hijau tua (bahasa visual "panggung" studio sertifikat): angka kode besar bergaris tipis,
    judul putih, pesan singkat, tombol lime. Halaman offline (public/offline.html) menyalin gaya ini.
    Variabel: $code, $title, $message
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#022b26">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
</head>
<body class="st-body">
    <header class="st-top">
        <a href="{{ url('/') }}" class="st-brand" aria-label="SIMAGTI, ke beranda">
            <img src="{{ asset('images/logo-simagti-mark.svg') }}" alt="" width="28" height="28">
            <span>SIMAGTI</span>
        </a>
    </header>

    <main class="st err-card">
        <p class="st-code" aria-label="Kode {{ $code }}">{{ $code }}</p>
        <h1 class="st-title">{{ $title }}</h1>
        <p class="st-text">{{ $message }}</p>
        <div class="st-actions">
            <a href="{{ url('/dashboard') }}" class="st-btn">Ke dashboard</a>
            <a href="javascript:history.back()" class="st-link">Kembali</a>
        </div>
    </main>
</body>
</html>
