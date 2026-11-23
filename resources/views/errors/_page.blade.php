{{--
    Halaman galat bergaya SIMAGTI (403, 404, 419, 429, 500, 503). Berdiri sendiri: tidak butuh sesi atau data pengguna.
    Satu kartu minimalis di tengah, senada dengan halaman aktivasi akun dan halaman offline (public/offline.html).
    Variabel: $code, $title, $message
--}}
<?php
    // Ikon garis sederhana per jenis galat
    $icons = [
        '403' => '<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
        '404' => '<circle cx="11" cy="11" r="6"/><path d="m20 20-4.2-4.2"/>',
        '419' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
        '429' => '<path d="M6 3h12M6 21h12M7 3c0 5 10 5 10 9s-10 4-10 9M17 3c0 5-10 5-10 9"/>',
        '500' => '<path d="M12 4 2.8 19.5h18.4z"/><path d="M12 10v4M12 17v.01"/>',
        '503' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17l3 3 5.3-5.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.5-.6-.6-2.5z"/>',
    ];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
</head>
<body class="st-body">
    <div class="gl-aura" aria-hidden="true"><i></i><i></i><i></i></div>
    <main class="st err-card">
        <a href="{{ url('/') }}" class="st-logo" aria-label="SIMAGTI, ke beranda"><img src="{{ asset('images/logo-simagti.svg') }}" alt="" width="132" height="40"></a>
        <span class="st-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">{!! $icons[$code] ?? $icons['500'] !!}</svg>
        </span>
        <h1 class="st-title">{{ $title }}</h1>
        <p class="st-text">{{ $message }}</p>
        <div class="st-actions">
            <a href="{{ url('/dashboard') }}" class="st-btn">Ke dashboard</a>
            <a href="javascript:history.back()" class="st-link">Kembali</a>
        </div>
        <p class="st-code">Kode {{ $code }}</p>
    </main>
</body>
</html>
