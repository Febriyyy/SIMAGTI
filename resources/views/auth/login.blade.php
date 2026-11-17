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
<body class="gl-body">
{{-- Latar: cahaya lembut hijau-biru di atas putih, wadah kaca jernih di tengah --}}
<div class="gl-aura" aria-hidden="true"><i></i><i></i><i></i></div>

<main class="gl">
    {{-- Kiri: formulir masuk --}}
    <section class="gl-form-side">
        <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" class="gl-logo" width="132" height="40">

        <div class="gl-form-box">
            <h1 class="gl-title">Masuk ke akun Anda</h1>
            <p class="gl-sub">Sistem Monitoring Magang Terintegrasi</p>

            <form method="POST" action="{{ route('login') }}" class="gl-form" novalidate>
                @csrf

                @if (session('status'))
                    <div class="alert alert-success" role="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-error" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="gl-field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@kampus.ac.id" required autofocus autocomplete="username">
                </div>

                <div class="gl-field">
                    <label for="password">Kata sandi</label>
                    <div class="gl-pass">
                        <input id="password" name="password" type="password" placeholder="••••••••" required autocomplete="current-password">
                        <button type="button" class="gl-eye" data-toggle-password aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <svg class="eye-open" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.6A9.7 9.7 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3.1 3.9M6.6 6.6C3.9 8.3 2.5 12 2.5 12S6 18.5 12 18.5c1.6 0 3-.4 4.2-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                    </div>
                </div>

                <div class="gl-row">
                    <label class="gl-check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Ingat saya</label>
                    <button type="button" class="gl-link" data-toggle-help aria-expanded="false" aria-controls="signin-help">Lupa kata sandi?</button>
                </div>
                <p id="signin-help" class="gl-help" hidden>Hubungi admin SIMAGTI untuk mengatur ulang kata sandi. Anda akan menerima kata sandi sementara yang wajib diganti saat masuk.</p>

                <button type="submit" class="gl-submit">Masuk</button>
            </form>
        </div>

        <p class="gl-foot">Akun dibuat oleh admin kampus atau lewat undangan perusahaan.</p>
    </section>

    {{-- Kanan: panel hijau dengan kutipan pengguna (bergeser) dan kartu kaca --}}
    <section class="gl-panel" aria-label="Tentang SIMAGTI">
        <svg class="gl-burst" viewBox="0 0 200 200" aria-hidden="true">
            @for ($a = 0; $a < 360; $a += 22.5)
                <line x1="100" y1="100" x2="{{ 100 + 92 * cos(deg2rad($a)) }}" y2="{{ 100 + 92 * sin(deg2rad($a)) }}" />
            @endfor
        </svg>

        <h2 class="gl-panel-title">Apa kata<br>pengguna SIMAGTI.</h2>

        <div class="gl-quotes" data-quotes aria-live="polite">
            <span class="gl-quote-mark" aria-hidden="true">“</span>
            @foreach ([
                ['Rencana, task, dan catatan harian jadi satu. Laporan akhir tinggal menyusun dari yang sudah tercatat.', 'Nabila Azzahra', 'Mahasiswa Teknik Informatika'],
                ['Saya cukup membuka satu halaman untuk melihat progres mahasiswa magang, lalu menilai di akhir dengan beberapa klik.', 'Andika Saputra', 'Company Supervisor'],
                ['Bimbingan dan validasi nilai tercatat rapi, jadi keputusan hasil KP lebih cepat dan bisa dipertanggungjawabkan.', 'Bambang Sutrisno', 'Dosen pembimbing'],
            ] as $n => [$text, $name, $role])
                <figure class="gl-quote {{ $n === 0 ? 'is-on' : '' }}" data-quote @if ($n !== 0) hidden @endif>
                    <blockquote>{{ $text }}</blockquote>
                    <figcaption><b>{{ $name }}</b><span>{{ $role }}</span></figcaption>
                </figure>
            @endforeach
        </div>

        <div class="gl-nav">
            <button type="button" class="gl-nav-btn" data-quote-prev aria-label="Kutipan sebelumnya"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg></button>
            <button type="button" class="gl-nav-btn is-dark" data-quote-next aria-label="Kutipan berikutnya"><svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg></button>
        </div>

        <div class="gl-card">
            <span class="gl-spark" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M12 1.5c.6 5.4 3.1 7.9 8.5 8.5-5.4.6-7.9 3.1-8.5 8.5-.6-5.4-3.1-7.9-8.5-8.5 5.4-.6 7.9-3.1 8.5-8.5Z"/></svg></span>
            <strong>Pantau KP dari pengajuan sampai sertifikat</strong>
            <p>Mahasiswa, perusahaan, dosen, dan kampus bekerja di satu tempat.</p>
            <div class="gl-avatars" aria-hidden="true"><i>M</i><i>S</i><i>D</i><i>K</i></div>
        </div>
    </section>
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
