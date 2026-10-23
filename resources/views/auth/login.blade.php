<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
</head>
<body class="auth-body">
<div class="auth-wrap">
    <div class="auth-split">
    <div class="auth-panel">
        <img class="hero-mark" src="{{ asset('images/logo-simagti-mark.svg') }}" alt="" aria-hidden="true">
            <div class="hero-eyebrow">Sistem Monitoring Kerja Praktik</div>
        <p class="auth-panel-title">Task mingguan, penilaian, dan progres KP dalam satu tempat.</p>
        <p class="auth-panel-sub">Untuk mahasiswa, Company Supervisor, dosen pembimbing, kaprodi, biro kerjasama, dan TU Fakultas.</p>
    </div>
    <div class="auth-card">
        <div class="brand">
            <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" class="brand-logo" width="168" height="51">
        </div>

        <h1 class="auth-title">Masuk</h1>
        <p class="auth-sub">Pantau kerja praktik mahasiswa dalam satu tempat.</p>

        <form method="POST" action="{{ route('login') }}" class="stack">
            @csrf

            @if (session('status'))
                <div class="alert alert-success" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
            @endif

            <div>
                <label for="email" class="label">Email</label>
                <input id="email" name="email" type="email" class="input" value="{{ old('email') }}" required autofocus autocomplete="username">
            </div>

            <div>
                <label for="password" class="label">Kata sandi</label>
                <input id="password" name="password" type="password" class="input" required autocomplete="current-password">
            </div>

            <label class="check">
                <input type="checkbox" name="remember" value="1"> Ingat saya di perangkat ini
            </label>

            <button type="submit" class="btn btn-primary btn-block">Masuk</button>
        </form>
    </div>
    </div>

</div>
</body>
</html>
