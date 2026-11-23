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
    Aktivasi akun dari tautan undangan (Company Supervisor), senada dengan halaman masuk (kelas gl-*):
    kiri formulir (nama, kata sandi dengan tombol lihat/sembunyikan dan tanda syarat), kanan panel hijau berisi
    alur setelah akun aktif dan kartu kaca ringkasan undangan.
    Variabel: invitation, students
--}}
<body class="gl-body">
<?php
    $eye = '<svg class="eye-open" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>'
        .'<svg class="eye-closed" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.6A9.7 9.7 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3.1 3.9M6.6 6.6C3.9 8.3 2.5 12 2.5 12S6 18.5 12 18.5c1.6 0 3-.4 4.2-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';
    $usable = $invitation->isUsable();
    $role = \App\Models\User::ROLE_LABELS[$invitation->role] ?? $invitation->role;
?>
<div class="gl-aura" aria-hidden="true"><i></i><i></i><i></i></div>

<main class="gl act">
    {{-- Kiri: formulir aktivasi --}}
    <section class="gl-form-side">
        <a href="{{ route('home') }}" class="gl-logo-link" aria-label="SIMAGTI, kembali ke beranda"><img src="{{ asset('images/logo-simagti.svg') }}" alt="" class="gl-logo" width="132" height="40"></a>

        <div class="gl-form-box">
            @if (! $usable)
                <h1 class="gl-title">Undangan tidak berlaku</h1>
                <p class="gl-sub act-sub">
                    @if ($invitation->isAccepted())
                        Undangan ini sudah dipakai untuk mengaktifkan akun. Silakan masuk dengan email dan kata sandi Anda.
                    @else
                        Undangan ini sudah kedaluwarsa. Minta TU Fakultas kampus mengirim undangan baru.
                    @endif
                </p>
                @if ($invitation->isAccepted())
                    <a href="{{ route('login') }}" class="gl-submit act-link">Masuk</a>
                @endif
            @else
                <h1 class="gl-title">Aktivasi akun</h1>
                <p class="gl-sub act-sub">Buat kata sandi untuk akun {{ $role }} Anda.</p>

                <form method="POST" action="{{ route('undangan.store', $invitation->token) }}" class="gl-form" novalidate>
                    @csrf

                    @if ($errors->any())
                        <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
                    @endif

                    <div class="gl-field">
                        <span class="act-label">Email masuk</span>
                        <div class="act-mail">
                            <svg viewBox="0 0 24 24" width="17" height="17" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
                            <span>{{ $invitation->email }}</span>
                        </div>
                    </div>

                    <div class="gl-field">
                        <label for="name">Nama lengkap</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" maxlength="100" required autofocus autocomplete="name" placeholder="Nama beserta gelar, bila ada">
                    </div>

                    <div class="gl-field">
                        <label for="password">Kata sandi</label>
                        <div class="gl-pass">
                            <input id="password" name="password" type="password" minlength="8" required autocomplete="new-password" placeholder="••••••••" aria-describedby="pw-rule">
                            <button type="button" class="gl-eye" data-toggle-password="password" aria-label="Tampilkan kata sandi" aria-pressed="false">{!! $eye !!}</button>
                        </div>
                        <p class="act-rule" id="pw-rule" data-rule="length"><span class="act-dot" aria-hidden="true"></span>Minimal 8 karakter</p>
                    </div>

                    <div class="gl-field">
                        <label for="password_confirmation">Ulangi kata sandi</label>
                        <div class="gl-pass">
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" placeholder="••••••••" aria-describedby="pw-match">
                            <button type="button" class="gl-eye" data-toggle-password="password_confirmation" aria-label="Tampilkan kata sandi" aria-pressed="false">{!! $eye !!}</button>
                        </div>
                        <p class="act-rule" id="pw-match" data-rule="match" aria-live="polite"><span class="act-dot" aria-hidden="true"></span><span data-match-text>Ketik ulang kata sandi yang sama</span></p>
                    </div>

                    <button type="submit" class="gl-submit">Aktifkan akun</button>
                </form>
            @endif
        </div>

        <p class="gl-foot">
            @if ($usable && $invitation->expires_at)
                Undangan berlaku sampai {{ $invitation->expires_at->locale('id')->translatedFormat('j F Y') }}.
            @else
                Butuh bantuan? Hubungi TU Fakultas kampus.
            @endif
        </p>
    </section>

    {{-- Kanan: panel hijau, alur setelah akun aktif dan ringkasan undangan --}}
    <section class="gl-panel" aria-label="Tentang undangan">
        <svg class="gl-silk" viewBox="0 0 600 700" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
            <defs>
                <linearGradient id="gl-s1" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#a7f3d0"/><stop offset="1" stop-color="#0b7a63"/></linearGradient>
                <linearGradient id="gl-s2" x1="1" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#d1fae5"/><stop offset="1" stop-color="#14b88a"/></linearGradient>
                <filter id="gl-sb" x="-20%" y="-20%" width="140%" height="140%"><feGaussianBlur stdDeviation="16"/></filter>
            </defs>
            <g filter="url(#gl-sb)" fill="none">
                <path d="M640 40 C 460 120, 520 320, 360 380 S 120 520, 220 760" stroke="url(#gl-s1)" stroke-width="150" opacity=".55"/>
                <path d="M620 -40 C 560 160, 700 300, 540 470 S 420 700, 640 760" stroke="url(#gl-s2)" stroke-width="110" opacity=".45"/>
            </g>
            <g fill="none" stroke="#ecfdf5" stroke-linecap="round">
                @for ($n = 0; $n < 8; $n++)
                    <path d="M{{ 640 - $n * 6 }} {{ 30 + $n * 4 }} C {{ 470 - $n * 5 }} 120, {{ 520 - $n * 7 }} 320, {{ 362 - $n * 6 }} 382 S {{ 130 - $n * 4 }} 520, {{ 225 - $n * 5 }} 760" stroke-width="{{ $n % 3 === 0 ? 1.2 : 0.6 }}" opacity="{{ 0.16 + ($n % 3) * 0.06 }}"/>
                @endfor
            </g>
        </svg>

        <h2 class="gl-panel-title">Satu langkah lagi<br>untuk mulai membimbing.</h2>

        <ol class="gl-flow">
            <li class="is-on"><span class="gl-flow-dot"></span><b>Aktifkan akun</b><small>Isi nama dan buat kata sandi</small></li>
            <li><span class="gl-flow-dot"></span><b>Pantau rencana dan kegiatan</b><small>Task, catatan harian, dan timesheet mahasiswa</small></li>
            <li><span class="gl-flow-dot"></span><b>Beri penilaian akhir</b><small>Di akhir periode KP, divalidasi dosen pembimbing</small></li>
            <li><span class="gl-flow-dot"></span><b>Lengkapi data sertifikat</b><small>Logo perusahaan dan tanda tangan ketua divisi</small></li>
        </ol>

        <div class="gl-card act-card">
            <span class="gl-spark" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M12 1.5c.6 5.4 3.1 7.9 8.5 8.5-5.4.6-7.9 3.1-8.5 8.5-.6-5.4-3.1-7.9-8.5-8.5 5.4-.6 7.9-3.1 8.5-8.5Z"/></svg></span>
            <span class="act-card-role">{{ $role }}</span>
            <strong>{{ $invitation->company->name }}</strong>
            @if ($students->isNotEmpty())
                <p>Mahasiswa yang dibimbing</p>
                <ul class="act-students">
                    @foreach ($students as $student)
                        <li><x-avatar :user="$student" small /> {{ $student->name }}</li>
                    @endforeach
                </ul>
            @else
                <p>Mahasiswa magang di perusahaan ini akan tampil setelah akun aktif.</p>
            @endif
        </div>
    </section>
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
