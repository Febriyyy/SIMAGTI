<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}">
</head>
<body class="auth-body">
<div class="auth-wrap">
    <div class="auth-card">
        <div class="brand">
            <span class="brand-mark">KP</span>
            <span class="brand-name">KP Monitor</span>
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

    @if (app()->environment('local'))
        <div class="demo">
            <strong>Akun contoh</strong> (kata sandi: <code>password</code>)
            <table>
                <tr><td>Mahasiswa</td><td>mahasiswa@kp.test</td></tr>
                <tr><td>Mentor</td><td>mentor@kp.test</td></tr>
                <tr><td>Ketua Divisi</td><td>divisi@kp.test</td></tr>
                <tr><td>Dosen Pembimbing</td><td>dospem@kp.test</td></tr>
                <tr><td>Kaprodi</td><td>kaprodi@kp.test</td></tr>
                <tr><td>Biro Kerjasama</td><td>biro@kp.test</td></tr>
            </table>
        </div>
    @endif
</div>
</body>
</html>
