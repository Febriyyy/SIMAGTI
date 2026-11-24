{{--
    Kerangka kartu akun di luar aplikasi (lupa kata sandi, atur ulang kata sandi), senada dengan halaman aktivasi akun (.av-*).
    Pakai: @extends('auth._card') dengan @section('title') dan @section('card').
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    @include('partials.pwa')
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/design-rules.css') }}?v={{ filemtime(public_path('css/design-rules.css')) }}">
</head>
<body class="av-body">
<div class="gl-aura" aria-hidden="true"><i></i><i></i><i></i></div>

<main class="av">
    <a href="{{ route('home') }}" class="av-logo" aria-label="SIMAGTI, kembali ke beranda"><img src="{{ asset('images/logo-simagti.svg') }}" alt="" width="132" height="40"></a>
    @yield('card')
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
