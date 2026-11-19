{{--
    Halaman galat bergaya SIMAGTI (403, 404, 419, 429, 500, 503). Berdiri sendiri: tidak butuh sesi atau data pengguna.
    Variabel: $code, $title, $message
--}}
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
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}">
    {{-- Percobaan gaya landing (hapus baris ini untuk kembali ke UI sebelumnya) --}}
    <link rel="stylesheet" href="{{ asset('css/theme-landing.css') }}?v={{ filemtime(public_path('css/theme-landing.css')) }}">
</head>
<body class="err-body">
    <main class="err">
        <a href="{{ url('/') }}" class="err-logo"><img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" width="132" height="40"></a>
        <div class="err-card">
            <span class="err-code" aria-hidden="true">{{ $code }}</span>
            <h1 class="err-title">{{ $title }}</h1>
            <p class="err-text">{{ $message }}</p>
            <div class="err-actions">
                <a href="{{ url('/dashboard') }}" class="btn btn-primary">Ke dashboard</a>
                <a href="javascript:history.back()" class="btn">Kembali</a>
            </div>
        </div>
    </main>
</body>
</html>
