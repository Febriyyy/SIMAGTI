<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk · {{ config('app.name') }}</title>
    <meta name="theme-color" content="#f8fafc">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}?v={{ filemtime(public_path('css/login.css')) }}">
</head>
{{--
    Halaman masuk: kartu dua sisi.
    Kiri panel hijau dengan cahaya lembut yang bergeser pelan, judul, dan empat tahap KP yang bergantian aktif.
    Kanan formulir. Satu animasi masuk saat dibuka; semua gerak mati bila pengguna memilih kurangi gerak.
--}}
<body class="lg-body">
<main class="lg-card">
    {{-- Kiri: panel hijau --}}
    <section class="lg-panel" aria-label="Tentang SIMAGTI">
        <div class="lg-glow" aria-hidden="true"><i></i><i></i><i></i></div>

        <a href="{{ route('home') }}" class="lg-brand" aria-label="SIMAGTI, kembali ke beranda">
            <img src="{{ asset('images/logo-simagti-putih.svg') }}" alt="" width="132" height="40">
        </a>

        <div class="lg-panel-body">
            <span class="lg-chip">Sistem Monitoring Magang Terintegrasi</span>
            <h2 class="lg-panel-title">
                <span class="lg-line"><span>Magang tercatat rapi,</span></span>
                <span class="lg-line"><span>dari awal sampai akhir.</span></span>
            </h2>
            <p class="lg-panel-sub">Semua tahap KP ada di satu tempat.</p>

            <ol class="lg-steps" data-steps>
                @foreach (['Pengajuan KP', 'Task dan catatan harian', 'Bimbingan dan nilai', 'Sertifikat terbit'] as $step)
                    <li @class(['lg-step', 'is-on' => $loop->first])>
                        <span class="lg-step-n">{{ $loop->iteration }}</span>
                        <span class="lg-step-label">{{ $step }}</span>
                        <span class="lg-step-bar" aria-hidden="true"><i></i></span>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Kanan: formulir --}}
    <section class="lg-form-side">
        <a href="{{ route('home') }}" class="lg-home">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 18l-6-6 6-6"/></svg>
            Beranda
        </a>

        <div class="lg-form-box">
            <h1 class="lg-title lg-rise">Masuk ke akun Anda</h1>
            <p class="lg-sub lg-rise">Selamat datang kembali di SIMAGTI.</p>

            <form method="POST" action="{{ route('login') }}" class="lg-form" novalidate>
                @csrf

                @if (session('status'))
                    <div class="alert alert-success lg-rise" role="status">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="alert alert-error lg-rise" role="alert">{{ $errors->first() }}</div>
                @endif

                <div class="lg-field lg-rise">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@kampus.ac.id" required autofocus autocomplete="username">
                </div>

                <div class="lg-field lg-rise">
                    <label for="password">Kata sandi</label>
                    <div class="lg-pass">
                        <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" required autocomplete="current-password">
                        <button type="button" class="lg-eye" data-toggle-password aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <svg class="eye-open" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.6A9.7 9.7 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a17 17 0 0 1-3.1 3.9M6.6 6.6C3.9 8.3 2.5 12 2.5 12S6 18.5 12 18.5c1.6 0 3-.4 4.2-1"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                        </button>
                    </div>
                </div>

                <div class="lg-row lg-rise">
                    <label class="lg-check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))><span>Ingat saya</span></label>
                    <a href="{{ route('password.request') }}" class="lg-link">Lupa kata sandi?</a>
                </div>

                <button type="submit" class="lg-submit lg-rise"><span class="lg-submit-label">Masuk</span><span class="lg-spin" aria-hidden="true"></span></button>
            </form>
        </div>

        <p class="lg-foot">Akun dibuat oleh admin kampus atau lewat undangan perusahaan.</p>
    </section>
</main>

<script>
    (function () {
        // Tampilkan / sembunyikan kata sandi
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

        // Kirim formulir: tombol menunjukkan proses agar tidak diklik dua kali
        var form = document.querySelector('.lg-form'), submit = document.querySelector('.lg-submit');
        if (form && submit) {
            form.addEventListener('submit', function () {
                submit.classList.add('is-loading');
                submit.setAttribute('aria-busy', 'true');
                setTimeout(function () { submit.disabled = true; }, 0);
            });
        }

        // Tahap KP bergantian aktif; garis di kartu aktif terisi selama tahap itu tampil
        var list = document.querySelector('[data-steps]');
        var still = false;
        try { still = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}
        if (list && !still) {
            var steps = [].slice.call(list.children), at = 0;
            list.classList.add('is-playing');
            setInterval(function () {
                if (document.hidden) return;
                at = (at + 1) % steps.length;
                steps.forEach(function (li, n) { li.classList.toggle('is-on', n === at); });
            }, 3600);
        }
    })();
</script>
</body>
</html>
