<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aktivasi akun · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
</head>
{{--
    Aktivasi akun dari tautan undangan (Company Supervisor): satu kartu di tengah, minimalis.
    Kepala: logo, judul, undangan dalam satu kalimat, mahasiswa yang dibimbing (avatar bertumpuk).
    Formulir: email terkunci, nama, kata sandi dan ulangi (tombol lihat/sembunyikan), tanda syarat.
    Variabel: invitation, students
--}}
<body class="av-body">
<?php
    $eye = '<svg class="eye-open" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>'
        .'<svg class="eye-closed" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.6A9.7 9.7 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3.1 3.9M6.6 6.6C3.9 8.3 2.5 12 2.5 12S6 18.5 12 18.5c1.6 0 3-.4 4.2-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';
    $usable = $invitation->isUsable();
    $role = \App\Models\User::ROLE_LABELS[$invitation->role] ?? $invitation->role;
?>
<div class="gl-aura" aria-hidden="true"><i></i><i></i><i></i></div>

<main class="av">
    <a href="{{ route('home') }}" class="av-logo" aria-label="SIMAGTI, kembali ke beranda"><img src="{{ asset('images/logo-simagti.svg') }}" alt="" width="132" height="40"></a>

    @if (! $usable)
        <h1 class="av-title">Undangan tidak berlaku</h1>
        <p class="av-lead">
            @if ($invitation->isAccepted())
                Undangan ini sudah dipakai untuk mengaktifkan akun. Silakan masuk dengan email dan kata sandi Anda.
            @else
                Undangan ini sudah kedaluwarsa. Minta TU Fakultas kampus mengirim undangan baru.
            @endif
        </p>
        @if ($invitation->isAccepted())
            <a href="{{ route('login') }}" class="av-submit av-link">Masuk</a>
        @endif
    @else
        <h1 class="av-title">Aktivasi akun</h1>
        <p class="av-lead">Anda diundang sebagai <b>{{ $role }}</b> di <b>{{ $invitation->company->name }}</b>.</p>

        @if ($students->isNotEmpty())
            <div class="av-students">
                <span class="av-stack" aria-hidden="true">
                    @foreach ($students->take(4) as $student)<x-avatar :user="$student" small />@endforeach
                </span>
                <span>Membimbing {{ $students->pluck('name')->join(', ', ' dan ') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('undangan.store', $invitation->token) }}" class="av-form" novalidate>
            @csrf

            @if ($errors->any())
                <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="av-field">
                <span class="av-label">Email</span>
                <div class="av-mail">
                    <span>{{ $invitation->email }}</span>
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-label="Tidak bisa diubah" role="img"><rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/></svg>
                </div>
            </div>

            <div class="av-field">
                <label for="name" class="av-label">Nama lengkap</label>
                <input id="name" name="name" type="text" class="av-input" value="{{ old('name') }}" maxlength="100" required autofocus autocomplete="name" placeholder="Nama beserta gelar, bila ada">
            </div>

            <div class="av-field">
                <label for="password" class="av-label">Kata sandi</label>
                <div class="av-pass">
                    <input id="password" name="password" type="password" class="av-input" minlength="8" required autocomplete="new-password" aria-describedby="av-rules">
                    <button type="button" class="av-eye" data-toggle-password="password" aria-label="Tampilkan kata sandi" aria-pressed="false">{!! $eye !!}</button>
                </div>
            </div>

            <div class="av-field">
                <label for="password_confirmation" class="av-label">Ulangi kata sandi</label>
                <div class="av-pass">
                    <input id="password_confirmation" name="password_confirmation" type="password" class="av-input" required autocomplete="new-password" aria-describedby="av-rules">
                    <button type="button" class="av-eye" data-toggle-password="password_confirmation" aria-label="Tampilkan kata sandi" aria-pressed="false">{!! $eye !!}</button>
                </div>
            </div>

            <ul class="av-rules" id="av-rules" aria-live="polite">
                <li data-rule="length">Minimal 8 karakter</li>
                <li data-rule="match">Kedua kata sandi sama</li>
            </ul>

            <button type="submit" class="av-submit">Aktifkan akun</button>
        </form>

        @if ($invitation->expires_at)
            <p class="av-foot">Undangan berlaku sampai {{ $invitation->expires_at->locale('id')->translatedFormat('j F Y') }}</p>
        @endif
    @endif
</main>

<script>
    // Lihat / sembunyikan kata sandi, serta tanda syarat panjang dan kecocokan (tanpa pustaka luar)
    (function () {
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            var input = document.getElementById(btn.getAttribute('data-toggle-password'));
            btn.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.setAttribute('aria-pressed', show ? 'true' : 'false');
                btn.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                btn.classList.toggle('is-open', show);
                input.focus();
            });
        });

        var pw = document.getElementById('password');
        var again = document.getElementById('password_confirmation');
        if (!pw || !again) return;
        var len = document.querySelector('[data-rule="length"]');
        var match = document.querySelector('[data-rule="match"]');
        var update = function () {
            len.classList.toggle('is-ok', pw.value.length >= 8);
            match.classList.toggle('is-ok', again.value !== '' && again.value === pw.value);
            match.classList.toggle('is-bad', again.value !== '' && again.value !== pw.value);
        };
        pw.addEventListener('input', update);
        again.addEventListener('input', update);
    })();
</script>
</body>
</html>
