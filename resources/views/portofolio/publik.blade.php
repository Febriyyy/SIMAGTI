<?php
    // Portofolio KP publik (tanpa login, tanpa NIM). Isi dipilih mahasiswa; nilai hanya bila mahasiswa mengizinkan.
    $initials = collect(preg_split('/\s+/', trim($student->name)))->filter(fn ($w) => preg_match('/^[\p{L}]/u', $w))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $statusText = $internship->status === 'selesai' ? ($internship->result ? 'KP selesai · '.$internship->resultLabel() : 'KP selesai') : 'KP sedang berjalan';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $student->name }} · Portofolio KP · {{ config('app.name') }}</title>
    <meta name="description" content="{{ $portfolio->headline ?? ($internship->position.' di '.$internship->placeName()) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
</head>
<body class="pp-body">
<main class="pp">
    <header class="pp-hero">
        <div class="pp-hero-top">
            <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" width="112" height="34">
            <span class="pp-verified">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                Terverifikasi SIMAGTI
            </span>
        </div>
        <div class="pp-who">
            <span class="pp-avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
            <div class="min0">
                <h1 class="pp-name">{{ $student->name }}</h1>
                @if ($portfolio->headline)<p class="pp-headline">{{ $portfolio->headline }}</p>@endif
                <p class="pp-meta">{{ $academic['program'] ?? 'Program studi' }} · {{ $academic['university'] }}</p>
            </div>
        </div>
        <div class="pp-role">
            <div><span>Posisi</span><b>{{ $internship->position ?? 'Kerja Praktik' }}</b></div>
            <div><span>Tempat</span><b>{{ $internship->placeName() }}@if ($internship->division) · {{ $internship->division->name }}@endif</b></div>
            <div><span>Periode</span><b>{{ $internship->periodLabel() }}</b></div>
            <div><span>Status</span><b>{{ $statusText }}</b></div>
        </div>
    </header>

    <section class="pp-stats" aria-label="Ringkasan">
        <div><b>{{ $stats['weeks'] }}</b><span>minggu KP</span></div>
        <div><b>{{ $stats['days'] }}</b><span>hari tercatat</span></div>
        <div><b>{{ $stats['done'] }}</b><span>task selesai</span></div>
        @if ($grade)
            <div class="pp-grade"><b>{{ $grade }}</b><span>nilai akhir{{ $score !== null ? ' · '.\App\Support\Num::short((float) $score) : '' }}</span></div>
        @else
            <div><b>{{ $goals->count() }}</b><span>goal tercapai</span></div>
        @endif
    </section>

    @if ($portfolio->about)
        <section class="pp-card">
            <h2 class="pp-title">Tentang</h2>
            <p class="pp-about">{{ $portfolio->about }}</p>
        </section>
    @endif

    @if (! empty($portfolio->skills))
        <section class="pp-card">
            <h2 class="pp-title">Keahlian</h2>
            <ul class="pp-skills">@foreach ($portfolio->skills as $skill)<li>{{ $skill }}</li>@endforeach</ul>
        </section>
    @endif

    @if ($tasks->isNotEmpty())
        <section class="pp-card">
            <h2 class="pp-title">Hasil kerja pilihan</h2>
            <ol class="pp-works">
                @foreach ($tasks as $task)
                    <li>
                        <span class="pp-work-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg></span>
                        <div class="min0">
                            <b>{{ $task->title }}</b>
                            @if ($task->description)<p>{{ \Illuminate\Support\Str::limit($task->description, 180) }}</p>@endif
                            <small>{{ $task->plan?->title }}@if ($task->completed_at) · selesai {{ $task->completed_at->locale('id')->translatedFormat('j M Y') }}@endif</small>
                        </div>
                    </li>
                @endforeach
            </ol>
        </section>
    @endif

    @if ($goals->isNotEmpty())
        <section class="pp-card">
            <h2 class="pp-title">Goal tercapai</h2>
            <ul class="pp-goals">@foreach ($goals as $goal)<li>{{ $goal->title }}</li>@endforeach</ul>
        </section>
    @endif

    <x-contrib :data="$contrib" title="Konsistensi selama KP" class="pp-contrib" />

    @if ($certificate)
        <a href="{{ route('sertifikat.verifikasi', $certificate->verification_token) }}" class="pp-cert">
            <span class="pp-cert-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="6"/><path d="M8.5 14.5L7 22l5-3 5 3-1.5-7.5"/></svg></span>
            <span class="min0"><b>Sertifikat Kerja Praktik</b><small>Nomor {{ $certificate->number }} · lihat verifikasi</small></span>
        </a>
    @endif

    <p class="pp-foot">Data kegiatan dicatat dan diverifikasi melalui {{ config('app.name') }}, sistem monitoring Kerja Praktik {{ $academic['university'] }}.</p>
</main>
</body>
</html>
