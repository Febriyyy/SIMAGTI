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
    Aktivasi akun dari tautan undangan (Company Supervisor).
    Ringkasan undangan (perusahaan, peran, mahasiswa), lalu nama dan kata sandi dengan tombol lihat/sembunyikan.
    Variabel: invitation, students
--}}
<body class="auth-body">
<?php
    $eye = '<svg class="eye-open" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>'
        .'<svg class="eye-closed" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.6A9.7 9.7 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3.1 3.9M6.6 6.6C3.9 8.3 2.5 12 2.5 12S6 18.5 12 18.5c1.6 0 3-.4 4.2-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';
?>
<div class="auth-wrap">
    <div class="auth-card act-card">
        <div class="brand">
            <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" class="brand-logo" width="168" height="51">
        </div>

        @if (! $invitation->isUsable())
            <h1 class="auth-title">Undangan tidak berlaku</h1>
            <p class="auth-sub">
                @if ($invitation->isAccepted())
                    Undangan ini sudah dipakai. Silakan <a href="{{ route('login') }}">masuk</a> dengan akun Anda.
                @else
                    Undangan ini sudah kedaluwarsa. Minta TU Fakultas kampus mengirim ulang.
                @endif
            </p>
        @else
            <h1 class="auth-title">Aktivasi akun</h1>
            <p class="auth-sub">Buat kata sandi untuk masuk ke SIMAGTI. Setelah aktif, Anda memantau kegiatan mahasiswa dan memberi penilaian di akhir periode KP.</p>

            {{-- Ringkasan undangan --}}
            <dl class="act-sum">
                <div>
                    <dt>Perusahaan</dt>
                    <dd>{{ $invitation->company->name }}</dd>
                </div>
                <div>
                    <dt>Peran</dt>
                    <dd>{{ \App\Models\User::ROLE_LABELS[$invitation->role] ?? $invitation->role }}</dd>
                </div>
                <div>
                    <dt>Email masuk</dt>
                    <dd class="act-mail">{{ $invitation->email }}</dd>
                </div>
                @if ($students->isNotEmpty())
                    <div class="act-wide">
                        <dt>Mahasiswa yang dibimbing</dt>
                        <dd>
                            <ul class="act-students">
                                @foreach ($students as $student)
                                    <li><x-avatar :user="$student" small /> {{ $student->name }}</li>
                                @endforeach
                            </ul>
                        </dd>
                    </div>
                @endif
            </dl>

            <form method="POST" action="{{ route('undangan.store', $invitation->token) }}" class="act-form" novalidate>
                @csrf

                @if ($errors->any())
                    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="act-field">
                    <label for="name" class="label">Nama lengkap</label>
                    <input id="name" name="name" type="text" class="input" value="{{ old('name') }}" maxlength="100" required autofocus autocomplete="name" placeholder="Nama beserta gelar, bila ada">
                </div>

                <div class="act-field">
                    <label for="password" class="label">Kata sandi</label>
                    <div class="act-pass">
                        <input id="password" name="password" type="password" class="input" minlength="8" required autocomplete="new-password" aria-describedby="pw-rule">
                        <button type="button" class="act-eye" data-toggle-password="password" aria-label="Tampilkan kata sandi" aria-pressed="false">{!! $eye !!}</button>
                    </div>
                    <p class="act-rule" id="pw-rule" data-rule="length"><span class="act-dot" aria-hidden="true"></span>Minimal 8 karakter</p>
                </div>

                <div class="act-field">
                    <label for="password_confirmation" class="label">Ulangi kata sandi</label>
                    <div class="act-pass">
                        <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password" aria-describedby="pw-match">
                        <button type="button" class="act-eye" data-toggle-password="password_confirmation" aria-label="Tampilkan kata sandi" aria-pressed="false">{!! $eye !!}</button>
                    </div>
                    <p class="act-rule" id="pw-match" data-rule="match" aria-live="polite"><span class="act-dot" aria-hidden="true"></span><span data-match-text>Ketik ulang kata sandi yang sama</span></p>
                </div>

                <button type="submit" class="btn btn-primary btn-block act-submit">Aktifkan akun</button>
                @if ($invitation->expires_at)
                    <p class="act-foot">Undangan berlaku sampai {{ $invitation->expires_at->locale('id')->translatedFormat('j F Y') }}.</p>
                @endif
            </form>
        @endif
    </div>
</div>

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
        var len = document.querySelector('[data-rule="length"]');
        var match = document.querySelector('[data-rule="match"]');
        if (!pw || !again) return;
        var update = function () {
            len.classList.toggle('is-ok', pw.value.length >= 8);
            var same = again.value !== '' && again.value === pw.value;
            match.classList.toggle('is-ok', same);
            match.classList.toggle('is-bad', again.value !== '' && !same);
            match.querySelector('[data-match-text]').textContent = again.value === ''
                ? 'Ketik ulang kata sandi yang sama'
                : (same ? 'Kata sandi cocok' : 'Kata sandi belum sama');
        };
        pw.addEventListener('input', update);
        again.addEventListener('input', update);
    })();
</script>
</body>
</html>
