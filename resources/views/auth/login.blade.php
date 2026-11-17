<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
</head>
<body class="signin-body">
<main class="signin">
    {{-- Kiri: formulir masuk --}}
    <section class="signin-form-side">
        <div class="signin-form-box">
            <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" class="signin-logo" width="150" height="46">

            <h1 class="signin-title">Selamat datang kembali</h1>
            <p class="signin-sub">Masukkan email dan kata sandi untuk melanjutkan.</p>

            <form method="POST" action="{{ route('login') }}" class="signin-form" novalidate>
                @csrf

                @if (session('status'))
                    <div class="alert alert-success" role="status">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <label class="signin-field">
                    <span class="sr-only">Email</span>
                    <svg class="signin-field-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="m4 7 8 6 8-6"/></svg>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="Alamat email"
                           required autofocus autocomplete="username">
                </label>

                <label class="signin-field">
                    <span class="sr-only">Kata sandi</span>
                    <svg class="signin-field-icon" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/><circle cx="12" cy="15.5" r="1.2"/></svg>
                    <input id="password" name="password" type="password" placeholder="Kata sandi" required autocomplete="current-password">
                    <button type="button" class="signin-eye" data-toggle-password aria-label="Tampilkan kata sandi" aria-pressed="false">
                        <svg class="eye-open" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-closed" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.6A9.7 9.7 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3.1 3.9M6.6 6.6C3.9 8.3 2.5 12 2.5 12S6 18.5 12 18.5c1.6 0 3-.4 4.2-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </label>

                <div class="signin-row">
                    <label class="signin-check">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))> Ingat saya
                    </label>
                    <button type="button" class="signin-link" data-toggle-help aria-expanded="false" aria-controls="signin-help">Lupa kata sandi?</button>
                </div>
                <p id="signin-help" class="signin-help" hidden>
                    Hubungi admin SIMAGTI untuk mengatur ulang kata sandi. Anda akan menerima kata sandi sementara yang wajib diganti saat masuk.
                </p>

                <button type="submit" class="signin-submit">Masuk</button>
            </form>
        </div>
        <p class="signin-foot"><strong>Semua kegiatan KP dalam satu tempat.</strong> Rencana kegiatan, task, catatan harian, bimbingan, dan penilaian.</p>
    </section>

    {{-- Kanan: panel merek dengan cuplikan aplikasi yang digambar dengan HTML (tajam di semua layar, ikut token warna) --}}
    <section class="signin-showcase" aria-hidden="true">
        <div class="showcase-grid"></div>

        <div class="signin-tagline">
            <span class="signin-kicker">Sistem Monitoring Magang Terintegrasi</span>
            <h2>Semua kegiatan KP dalam satu tempat.</h2>
            <p>Rencana kegiatan, task, catatan harian, bimbingan, dan penilaian untuk mahasiswa, perusahaan, dan kampus.</p>
        </div>

        <div class="showcase">
            <div class="sc-card sc-progress">
                <div class="sc-row"><strong>Kemajuan KP</strong><span class="sc-muted">Minggu 14 dari 22</span></div>
                <div class="sc-steps"><i class="done"></i><i class="done"></i><i class="done"></i><i class="now"></i><i></i><i></i></div>
                <div class="sc-row sc-small"><span>Pengajuan</span><span class="sc-now">Pelaksanaan</span><span>Hasil</span></div>
            </div>

            <div class="sc-card sc-gantt">
                <div class="sc-row"><strong>Rencana kegiatan</strong><span class="sc-chip">4 rencana</span></div>
                <div class="sc-lanes">
                    <span class="sc-today"></span>
                    <div class="sc-lane"><span class="sc-name">Orientasi</span><span class="sc-bar c1" style="--x: 0%; --w: 22%"><b>100%</b></span></div>
                    <div class="sc-lane"><span class="sc-name">Modul Pemesanan</span><span class="sc-bar c2" style="--x: 14%; --w: 46%"><b>60%</b></span></div>
                    <div class="sc-lane"><span class="sc-name">Integrasi API</span><span class="sc-bar c3" style="--x: 42%; --w: 36%"><b>25%</b></span></div>
                    <div class="sc-lane"><span class="sc-name">Laporan akhir</span><span class="sc-bar c4" style="--x: 70%; --w: 30%"></span></div>
                </div>
            </div>

            <div class="sc-card sc-float">
                <span class="sc-float-icon"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>
                <div><strong>Penilaian divalidasi</strong><span class="sc-muted">Nilai akhir A</span></div>
            </div>
        </div>
    </section>
</main>

<script>
    // Tampilkan / sembunyikan kata sandi, dan bantuan "lupa kata sandi" (tanpa pustaka luar).
    (function () {
        var eye = document.querySelector('[data-toggle-password]');
        var input = document.getElementById('password');
        if (eye && input) {
            eye.addEventListener('click', function (e) {
                e.preventDefault();
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                eye.setAttribute('aria-pressed', show ? 'true' : 'false');
                eye.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
                eye.classList.toggle('is-open', show);
                input.focus();
            });
        }
        var help = document.querySelector('[data-toggle-help]');
        var note = document.getElementById('signin-help');
        if (help && note) {
            help.addEventListener('click', function () {
                note.hidden = !note.hidden;
                help.setAttribute('aria-expanded', note.hidden ? 'false' : 'true');
            });
        }
    })();
</script>
</body>
</html>
