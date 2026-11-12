<?php
    // Halaman publik hasil pindai QR sertifikat (tanpa login, tanpa NIM). Tampilan potret:
    // pita status, sertifikat potret (acuan referensi pemilik proyek), lalu kartu keterangan nilai yang terpisah.
    $internship = $certificate?->internship;
    $state = ! $certificate ? 'missing' : ($certificate->isValid() ? 'valid' : 'invalid');
    $issued = $certificate?->issued_at?->locale('id');
    $template = $certificate?->template;
    // Motif pita: kotak berisi seperempat lingkaran, warna dan arah bergantian (deterministik)
    $tiles = ['a tl', 'b br', 'c tr', 'a bl', 'b tl', 'c br', 'b tr', 'c bl', 'a br', 'c tl', 'a tr', 'b bl'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Verifikasi Sertifikat · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
</head>
<body class="vx-body">
<main class="vx">
    <header class="vx-top">
        <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" width="132" height="40">
    </header>

    {{-- Status keaslian --}}
    <section class="vx-status vx-{{ $state }}" aria-labelledby="verify-title">
        <span class="vx-status-icon" aria-hidden="true">
            @if ($state === 'valid')
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
            @else
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M6 6l12 12M18 6L6 18"/></svg>
            @endif
        </span>
        <div class="min0">
            <h1 id="verify-title" class="vx-status-title">
                @switch($state)
                    @case('valid') Sertifikat sah @break
                    @case('invalid') Sertifikat tidak berlaku @break
                    @default Sertifikat tidak ditemukan
                @endswitch
            </h1>
            <p class="vx-status-sub">
                @switch($state)
                    @case('valid') Nomor {{ $certificate->number }} · diterbitkan {{ $program }}, {{ $university }}. @break
                    @case('invalid') Nomor {{ $certificate->number }}. Sertifikat ini sudah dicabut dan tidak bisa dipakai.@if ($certificate->revoked_reason) Alasan: {{ $certificate->revoked_reason }}.@endif @break
                    @default Periksa kembali kode QR atau tautan yang Anda buka.
                @endswitch
            </p>
        </div>
    </section>

    @if ($state === 'valid')
        <nav class="vx-jump" aria-label="Bagian">
            <a href="#sertifikat">Sertifikat</a>
            <a href="#nilai">Keterangan nilai</a>
        </nav>

        {{-- Sertifikat potret --}}
        <article id="sertifikat" class="vx-sheet" aria-label="Sertifikat Kerja Praktik {{ $internship->student->name }}">
            <div class="vx-band" aria-hidden="true">
                <div class="vx-year"><span>{{ $issued?->format('Y') ?? now()->format('Y') }}</span></div>
                <div class="vx-tiles">
                    @foreach ($tiles as $tile)<i class="vx-tile {{ $tile }}"></i>@endforeach
                </div>
            </div>

            <div class="vx-sheet-body">
                <div class="vx-org">
                    <img src="{{ asset('images/logo-simagti-mark.svg') }}" alt="" width="28" height="28">
                    <div class="min0">
                        <strong>{{ $university }}</strong>
                        <span>{{ $program }}</span>
                    </div>
                    <span class="vx-date">{{ $issued?->translatedFormat('j F Y') }}</span>
                </div>

                <div class="vx-heading">
                    <h2>Sertifikat<br>Kerja Praktik</h2>
                    <svg class="vx-spark" viewBox="0 0 100 100" fill="currentColor" aria-hidden="true"><path d="M50 0C53 34 66 47 100 50 66 53 53 66 50 100 47 66 34 53 0 50 34 47 47 34 50 0z"/></svg>
                </div>

                <p class="vx-given">Diberikan kepada</p>
                <p class="vx-name">{{ $internship->student->name }}</p>

                <p class="vx-desc">
                    Telah menyelesaikan Kerja Praktik
                    @if ($internship->position)sebagai <strong>{{ $internship->position }}</strong>@endif
                    di <strong>{{ $internship->placeName() }}</strong> pada {{ $internship->periodLabel() }}, dan dinyatakan <strong>diterima</strong>.
                </p>

                <div class="vx-sheet-foot">
                    <div class="vx-sign">
                        <span class="vx-sign-line" aria-hidden="true"></span>
                        <strong>{{ $template?->signer_name ?? $program }}</strong>
                        <small>{{ $template?->signer_title ?? 'Ketua Program Studi' }}</small>
                    </div>
                    <div class="vx-seal" role="img" aria-label="Stempel terverifikasi SIMAGTI">
                        <svg viewBox="0 0 120 120" aria-hidden="true">
                            <defs><path id="vx-ring" d="M60 60 m-44 0 a44 44 0 1 1 88 0 a44 44 0 1 1 -88 0"/></defs>
                            <circle cx="60" cy="60" r="57" class="vx-seal-out"/>
                            <circle cx="60" cy="60" r="33" class="vx-seal-in"/>
                            <text class="vx-seal-text"><textPath href="#vx-ring" startOffset="0" textLength="268" lengthAdjust="spacing">TERVERIFIKASI · SIMAGTI · KERJA PRAKTIK ·</textPath></text>
                            <path d="M47 61l9 9 18-20" class="vx-seal-check"/>
                        </svg>
                    </div>
                </div>
                <p class="vx-number">No. {{ $certificate->number }}</p>
            </div>
        </article>

        {{-- Keterangan nilai (terpisah dari sertifikat) --}}
        <section id="nilai" class="vx-card" aria-labelledby="grade-title">
            <h2 id="grade-title" class="vx-card-title">Keterangan nilai</h2>
            @if ($grade)
                <div class="vx-grade">
                    <div class="vx-grade-letter" aria-label="Huruf mutu {{ $grade['letter'] }}">{{ $grade['letter'] }}</div>
                    <div class="min0">
                        <div class="vx-grade-score">{{ \App\Support\Num::short($grade['score']) }}<small>/100</small></div>
                        <div class="vx-grade-sub">Nilai akhir · divalidasi {{ $grade['assessment']->validated_at?->locale('id')->translatedFormat('j M Y') }}</div>
                    </div>
                </div>
                <ul class="vx-cats">
                    @foreach ($grade['breakdown']['categories'] as $cat)
                        <li>
                            <div class="vx-cat-top">
                                <span>{{ $cat['label'] }} <small>bobot {{ \App\Support\Num::short($cat['weight']) }}%</small></span>
                                <b>{{ $cat['average'] !== null ? \App\Support\Num::short($cat['average']) : '–' }}</b>
                            </div>
                            <span class="vx-bar" aria-hidden="true"><span style="width: {{ min(100, (float) ($cat['average'] ?? 0)) }}%"></span></span>
                        </li>
                    @endforeach
                </ul>
                <p class="vx-note">Nilai dari Company Supervisor, divalidasi dosen pembimbing.</p>
            @else
                <p class="vx-empty">Keterangan nilai belum tersedia untuk sertifikat ini.</p>
            @endif
        </section>
    @endif

    <p class="vx-foot">Halaman verifikasi resmi {{ config('app.name') }}. Data ditampilkan seperlunya untuk memastikan keaslian sertifikat.</p>
</main>
</body>
</html>
