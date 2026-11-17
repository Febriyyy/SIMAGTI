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
<body class="lq-body">
{{-- Halaman putih dengan garis lengkung tipis, bingkai besar berlatar cairan seperti kain, kartu kaca di tengah --}}
<svg class="lq-lines" viewBox="0 0 1440 900" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
    <path d="M-80 640 C 240 420, 420 980, 760 720 S 1260 360, 1540 560" />
    <path d="M-60 180 C 300 -40, 520 420, 820 260 S 1300 -60, 1520 160" />
    <path d="M420 -60 C 260 260, 640 420, 560 940" />
    <path d="M1080 -40 C 1240 320, 900 520, 1160 960" />
</svg>

<main class="lq">
    {{-- Latar cairan: pita-pita bergradasi yang diburamkan, dengan garis kilau tipis --}}
    <svg class="lq-art" viewBox="0 0 1200 760" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <defs>
            <linearGradient id="lq-a" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#6ee7b7"/><stop offset=".5" stop-color="#14b88a"/><stop offset="1" stop-color="#065f52"/></linearGradient>
            <linearGradient id="lq-b" x1="1" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#a7f3d0"/><stop offset=".55" stop-color="#2dd4a3"/><stop offset="1" stop-color="#0b7a63"/></linearGradient>
            <linearGradient id="lq-c" x1="0" y1="1" x2="1" y2="0"><stop offset="0" stop-color="#0f766e"/><stop offset="1" stop-color="#5eead4"/></linearGradient>
            <filter id="lq-soft" x="-20%" y="-20%" width="140%" height="140%"><feGaussianBlur stdDeviation="14"/></filter>
            <filter id="lq-glow" x="-20%" y="-20%" width="140%" height="140%"><feGaussianBlur stdDeviation="2.2"/></filter>
        </defs>
        <g class="lq-flow" filter="url(#lq-soft)">
            <path d="M120 -40 C 40 220, 260 360, 220 560 S 120 860, 380 820" stroke="url(#lq-a)" stroke-width="190" fill="none" opacity=".9"/>
            <path d="M560 -60 C 760 120, 1180 120, 1240 360 S 960 640, 1180 820" stroke="url(#lq-b)" stroke-width="210" fill="none" opacity=".85"/>
            <path d="M-60 720 C 260 560, 520 820, 820 640 S 1160 520, 1300 700" stroke="url(#lq-c)" stroke-width="150" fill="none" opacity=".75"/>
        </g>
        <g class="lq-sheen" filter="url(#lq-glow)" fill="none" stroke="#e8fff6" stroke-linecap="round">
            @for ($n = 0; $n < 9; $n++)
                <path d="M{{ 150 + $n * 9 }} -20 C {{ 70 + $n * 10 }} 230, {{ 290 + $n * 7 }} 360, {{ 250 + $n * 8 }} 560 S {{ 150 + $n * 9 }} 850, {{ 400 + $n * 6 }} 810" stroke-width="{{ $n % 3 === 0 ? 1.4 : 0.7 }}" opacity="{{ 0.18 + ($n % 4) * 0.06 }}"/>
                <path d="M{{ 600 + $n * 10 }} -40 C {{ 800 + $n * 8 }} 140, {{ 1150 + $n * 4 }} 150, {{ 1200 - $n * 6 }} 380 S {{ 960 + $n * 6 }} 650, {{ 1170 + $n * 5 }} 820" stroke-width="{{ $n % 3 === 1 ? 1.3 : 0.7 }}" opacity="{{ 0.14 + ($n % 4) * 0.05 }}"/>
            @endfor
        </g>
    </svg>
    <div class="lq-grain" aria-hidden="true"></div>

    <header class="lq-top">
        <span class="lq-brand"><img src="{{ asset('images/logo-simagti-mark.svg') }}" alt="" width="30" height="30"> SIMAGTI</span>
        <span class="lq-tag">Sistem Monitoring Magang Terintegrasi</span>
    </header>

    {{-- Kartu kaca: formulir masuk --}}
    <section class="lq-card" aria-labelledby="lq-title">
        <h1 id="lq-title" class="lq-title">Masuk</h1>
        <p class="lq-sub">Gunakan email dan kata sandi akun SIMAGTI Anda.</p>

        <form method="POST" action="{{ route('login') }}" class="lq-form" novalidate>
            @csrf
            @if (session('status'))
                <div class="lq-alert is-ok" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="lq-alert" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="lq-field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@kampus.ac.id" required autofocus autocomplete="username">
            </div>
            <div class="lq-field">
                <label for="password">Kata sandi</label>
                <div class="lq-pass">
                    <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" required autocomplete="current-password">
                    <button type="button" class="lq-eye" data-toggle-password aria-label="Tampilkan kata sandi" aria-pressed="false">
                        <svg class="eye-open" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="eye-closed" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.6A9.7 9.7 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3.1 3.9M6.6 6.6C3.9 8.3 2.5 12 2.5 12S6 18.5 12 18.5c1.6 0 3-.4 4.2-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                    </button>
                </div>
            </div>

            <div class="lq-row">
                <label class="lq-check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Ingat saya</label>
                <button type="button" class="lq-link" data-toggle-help aria-expanded="false" aria-controls="signin-help">Lupa kata sandi?</button>
            </div>
            <p id="signin-help" class="lq-help" hidden>Hubungi admin SIMAGTI untuk mengatur ulang kata sandi. Anda akan menerima kata sandi sementara yang wajib diganti saat masuk.</p>

            <button type="submit" class="lq-submit"><span>Masuk</span></button>
        </form>

        <p class="lq-foot">Akun dibuat oleh admin kampus atau lewat undangan perusahaan.</p>
    </section>

    {{-- Kartu kaca kecil: kutipan pengguna yang bergeser --}}
    <aside class="lq-quote-card" aria-label="Kata pengguna SIMAGTI">
        <div class="lq-quotes" aria-live="polite">
            @foreach ([
                ['Rencana, task, dan catatan harian jadi satu. Laporan akhir tinggal menyusun dari yang sudah tercatat.', 'Nabila Azzahra', 'Mahasiswa'],
                ['Cukup satu halaman untuk melihat progres mahasiswa magang, lalu menilai di akhir dengan beberapa klik.', 'Andika Saputra', 'Company Supervisor'],
                ['Bimbingan dan validasi nilai tercatat rapi, jadi keputusan hasil KP lebih cepat.', 'Bambang Sutrisno', 'Dosen pembimbing'],
            ] as $n => [$text, $name, $role])
                <figure class="lq-quote {{ $n === 0 ? 'is-on' : '' }}" data-quote @if ($n !== 0) hidden @endif>
                    <blockquote>“{{ $text }}”</blockquote>
                    <figcaption><b>{{ $name }}</b> · {{ $role }}</figcaption>
                </figure>
            @endforeach
        </div>
        <div class="lq-quote-nav">
            <button type="button" data-quote-prev aria-label="Kutipan sebelumnya"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg></button>
            <button type="button" data-quote-next aria-label="Kutipan berikutnya"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
        </div>
    </aside>
</main>

<script>
    // Tampilkan / sembunyikan kata sandi, bantuan "lupa kata sandi", dan kutipan bergeser (tanpa pustaka luar).
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

        var quotes = [].slice.call(document.querySelectorAll('[data-quote]'));
        var i = 0, timer = null, reduce = false;
        try { reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}
        function show(n) {
            quotes[i].hidden = true; quotes[i].classList.remove('is-on');
            i = (n + quotes.length) % quotes.length;
            quotes[i].hidden = false; void quotes[i].offsetWidth; quotes[i].classList.add('is-on');
        }
        function auto() { clearInterval(timer); if (!reduce) timer = setInterval(function () { show(i + 1); }, 7000); }
        var prev = document.querySelector('[data-quote-prev]'), next = document.querySelector('[data-quote-next]');
        if (quotes.length > 1 && prev && next) {
            prev.addEventListener('click', function () { show(i - 1); auto(); });
            next.addEventListener('click', function () { show(i + 1); auto(); });
            auto();
        }
    })();
</script>
</body>
</html>
