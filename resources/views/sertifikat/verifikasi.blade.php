@php
    $internship = $certificate?->internship;
    $state = ! $certificate ? 'missing' : ($certificate->isValid() ? 'valid' : 'invalid');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Verifikasi Sertifikat · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
</head>
<body class="verify-body">
<main class="verify-page">
    <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" class="verify-logo" width="150" height="46">

    <section class="card verify-card verify-{{ $state }}" aria-labelledby="verify-title">
        <div class="verify-status">
            <span class="verify-badge" aria-hidden="true">
                @if ($state === 'valid')
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                @else
                    <svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
                @endif
            </span>
            <div>
                <h1 id="verify-title" class="verify-title">
                    @switch($state)
                        @case('valid') Sertifikat sah @break
                        @case('invalid') Sertifikat tidak berlaku @break
                        @default Sertifikat tidak ditemukan
                    @endswitch
                </h1>
                <p class="verify-sub">
                    @switch($state)
                        @case('valid') Diterbitkan oleh {{ $program }}, {{ $university }}. @break
                        @case('invalid') Sertifikat ini sudah dicabut dan tidak bisa dipakai. @break
                        @default Periksa kembali kode QR atau tautan yang Anda buka.
                    @endswitch
                </p>
            </div>
        </div>

        @if ($certificate)
            <dl class="kv verify-kv">
                <div><dt>Nomor</dt><dd>{{ $certificate->number }}</dd></div>
                <div><dt>Nama</dt><dd>{{ $internship->student->name }}</dd></div>
                <div><dt>Tempat KP</dt><dd>{{ $internship->placeName() }}</dd></div>
                <div><dt>Periode</dt><dd>{{ $internship->periodLabel() }}</dd></div>
                @if ($certificate->issued_at)
                    <div><dt>Diterbitkan</dt><dd>{{ $certificate->issued_at->locale('id')->translatedFormat('j F Y') }}</dd></div>
                @endif
                @if ($state === 'invalid' && $certificate->revoked_reason)
                    <div><dt>Alasan</dt><dd>{{ $certificate->revoked_reason }}</dd></div>
                @endif
            </dl>
        @endif
    </section>

    <p class="verify-foot">Halaman ini hanya menampilkan data yang diperlukan untuk memastikan keaslian sertifikat.</p>
</main>
</body>
</html>
