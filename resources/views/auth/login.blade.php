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

                <button type="submit" class="gl-submit"><span class="gl-submit-label">Masuk</span><span class="gl-spin" aria-hidden="true"></span></button>
            </form>
        </div>

        <p class="gl-foot">Akun dibuat oleh admin kampus atau lewat undangan perusahaan.</p>
    </section>

    {{-- Kanan: panel hijau berlatar cairan seperti kain, alur magang, dan kartu kaca --}}
    <section class="gl-panel" aria-label="Tentang SIMAGTI">
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

        <h2 class="gl-panel-title">Magang tercatat rapi<br>dari awal sampai akhir.</h2>

        <ol class="gl-flow" data-flow>
            <span class="gl-flow-fill" aria-hidden="true"></span>
            <li class="is-on"><span class="gl-flow-dot"></span><b>Pengajuan KP</b><small>Pilih lowongan, ajukan, tunggu persetujuan</small></li>
            <li><span class="gl-flow-dot"></span><b>Task dan catatan harian</b><small>Dikerjakan dan dipantau setiap hari</small></li>
            <li><span class="gl-flow-dot"></span><b>Bimbingan dan penilaian</b><small>Dosen dan supervisor memberi nilai</small></li>
            <li><span class="gl-flow-dot"></span><b>Sertifikat</b><small>Terbit otomatis setelah KP selesai</small></li>
        </ol>

        <div class="gl-card">
            <span class="gl-spark" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="currentColor"><path d="M12 1.5c.6 5.4 3.1 7.9 8.5 8.5-5.4.6-7.9 3.1-8.5 8.5-.6-5.4-3.1-7.9-8.5-8.5 5.4-.6 7.9-3.1 8.5-8.5Z"/></svg></span>
            <strong>Satu akun untuk setiap peran</strong>
            <p>Mahasiswa, supervisor, dosen, dan kampus bekerja di satu tempat.</p>
            <div class="gl-avatars" aria-hidden="true"><i>M</i><i>S</i><i>D</i><i>K</i></div>
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

        // Kirim formulir: tombol menunjukkan proses agar tidak diklik dua kali.
        var form = document.querySelector('.gl-form'), submit = document.querySelector('.gl-submit');
        if (form && submit) {
            form.addEventListener('submit', function () {
                submit.classList.add('is-loading');
                submit.setAttribute('aria-busy', 'true');
                setTimeout(function () { submit.disabled = true; }, 0);
            });
        }

        // Alur magang: tahap aktif bergeser pelan, garis terisi mengikuti tahap.
        var flow = document.querySelector('[data-flow]');
        var still = false;
        try { still = window.matchMedia('(prefers-reduced-motion: reduce)').matches; } catch (e) {}
        if (flow && !still) {
            var steps = [].slice.call(flow.querySelectorAll('li')), at = 0;
            var paint = function () {
                steps.forEach(function (li, n) { li.classList.toggle('is-on', n === at); li.classList.toggle('is-past', n < at); });
                flow.style.setProperty('--fill', steps.length > 1 ? at / (steps.length - 1) : 1);
            };
            paint();
            setInterval(function () { if (!document.hidden) { at = (at + 1) % steps.length; paint(); } }, 2800);
        }
    })();
</script>
</body>
</html>
