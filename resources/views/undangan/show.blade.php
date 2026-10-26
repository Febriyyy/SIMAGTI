<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Aktivasi akun · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
</head>
<body class="auth-body">
<div class="auth-wrap">
    <div class="auth-card">
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
            <p class="auth-sub">
                Anda diundang sebagai {{ \App\Models\User::ROLE_LABELS[$invitation->role] ?? $invitation->role }}
                di {{ $invitation->company->name }}@if ($students->isNotEmpty()) untuk membimbing {{ $students->pluck('name')->join(', ', ' dan ') }}@endif.
                Anda memantau rencana, kegiatan, dan timesheet mahasiswa, lalu memberi penilaian di akhir periode KP.
            </p>

            <form method="POST" action="{{ route('undangan.store', $invitation->token) }}" class="stack">
                @csrf

                @if ($errors->any())
                    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <div>
                    <label class="label">Email</label>
                    <input type="email" class="input" value="{{ $invitation->email }}" readonly>
                </div>

                <div>
                    <label for="name" class="label">Nama lengkap</label>
                    <input id="name" name="name" type="text" class="input" value="{{ old('name') }}" maxlength="100" required autofocus>
                </div>

                <div>
                    <label for="password" class="label">Kata sandi</label>
                    <input id="password" name="password" type="password" class="input" minlength="8" required autocomplete="new-password">
                    <p class="hint">Minimal 8 karakter.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="label">Ulangi kata sandi</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" class="input" required autocomplete="new-password">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Aktifkan akun</button>
            </form>
        @endif
    </div>
</div>
</body>
</html>
