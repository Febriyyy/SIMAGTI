<?php
    // Portofolio mahasiswa sebagai CV editorial: satu lembar, label bagian di kiri, isi di kanan.
    // Dipakai untuk tautan publik (tanpa login, tanpa NIM) dan untuk pihak kampus / supervisor. Bisa dicetak / disimpan PDF.
    $levels = \App\Models\Portfolio::LEVELS;
    $byLevel = $skills->groupBy(fn ($s) => $s->pivot->level);
    $arrow = '<svg viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8"/></svg>';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $student->name }} · Portofolio · {{ config('app.name') }}</title>
    <meta name="description" content="{{ $portfolio->headline ?: 'Portofolio '.$student->name }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/logo-simagti-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/kp.css') }}?v={{ filemtime(public_path('css/kp.css')) }}">
</head>
<body class="cv-body">
<nav class="cv-bar" aria-label="Halaman">
    @unless ($public)
        <a href="{{ ! empty($back) && $back !== url()->current() ? $back : route('dashboard') }}" class="cv-bar-link">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            Kembali
        </a>
    @else
        <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" width="104" height="32" class="cv-bar-logo">
    @endunless
    <button type="button" class="cv-bar-link" onclick="window.print()">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 10l5 5 5-5M5 20h14"/></svg>
        Simpan PDF
    </button>
</nav>

<article class="cv">
    <header class="cv-head">
        <div class="cv-id">
            <h1 class="cv-name">{{ $student->name }}</h1>
            @if ($portfolio->headline)<p class="cv-headline">{{ $portfolio->headline }}</p>@endif
        </div>
        <dl class="cv-facts">
            @if ($academic['program'])<div><dt>Program studi</dt><dd>{{ $academic['program'] }}</dd></div>@endif
            <div><dt>Kampus</dt><dd>{{ $academic['university'] }}</dd></div>
            @if ($academic['entry_year'])<div><dt>Angkatan</dt><dd>{{ $academic['entry_year'] }}</dd></div>@endif
            @if ($gpa !== null)<div><dt>IPK</dt><dd class="cv-gpa">{{ number_format((float) $gpa, 2, ',', '.') }}<span> / 4,00</span></dd></div>@endif
        </dl>
    </header>

    @if ($portfolio->about)
        <section class="cv-row">
            <h2 class="cv-label">Tentang</h2>
            <p class="cv-about">{{ $portfolio->about }}</p>
        </section>
    @endif

    @if ($internship)
        <section class="cv-row">
            <h2 class="cv-label">Pengalaman</h2>
            <div class="cv-entry">
                <div class="cv-entry-head">
                    <h3>{{ $internship->position ?? 'Kerja Praktik' }}</h3>
                    <span class="cv-when">{{ $internship->periodLabel() }}</span>
                </div>
                <p class="cv-sub">{{ $internship->placeName() }}@if ($internship->division), {{ $internship->division->name }}@endif</p>
                @if ($internship->supervisor)<p class="cv-note">Pembimbing lapangan: {{ $internship->supervisor->name }}</p>@endif
            </div>
        </section>
    @endif

    <section class="cv-row">
        <h2 class="cv-label">Proyek</h2>
        <div class="cv-list">
            @forelse ($portfolio->projects as $project)
                <div class="cv-entry @if ($project->image) has-thumb @endif">
                    <div class="min0">
                        <div class="cv-entry-head">
                            <h3>{{ $project->title }}</h3>
                            @if ($project->year)<span class="cv-when">{{ $project->year }}</span>@endif
                        </div>
                        @if ($project->role)<p class="cv-sub">{{ $project->role }}</p>@endif
                        @if ($project->description)<p class="cv-text">{{ $project->description }}</p>@endif
                        @if ($project->techList())<p class="cv-note">{{ implode(', ', $project->techList()) }}</p>@endif
                        @if ($project->linkList())
                            <p class="cv-links">@foreach ($project->linkList() as $l)<a href="{{ $l['url'] }}" target="_blank" rel="noopener nofollow ugc">{{ $l['label'] }}{!! $arrow !!}</a>@endforeach</p>
                        @endif
                    </div>
                    @if ($project->image)
                        <img class="cv-thumb" src="{{ $fileUrl($project->image) }}" alt="Pratinjau {{ $project->title }}" loading="lazy">
                    @endif
                </div>
            @empty
                <p class="cv-empty">Belum ada proyek.</p>
            @endforelse
        </div>
    </section>

    <section class="cv-row">
        <h2 class="cv-label">Sertifikat</h2>
        <div class="cv-list">
            @forelse ($portfolio->certificates as $cert)
                <div class="cv-entry">
                    <div class="cv-entry-head">
                        <h3>{{ $cert->name }}</h3>
                        @if ($cert->issued_on)<span class="cv-when">{{ $cert->issued_on->locale('id')->translatedFormat('M Y') }}</span>@endif
                    </div>
                    <p class="cv-sub">{{ $cert->issuer }}</p>
                    @if ($cert->file || $cert->linkList())
                        <p class="cv-links">
                            @if ($cert->file)<a href="{{ $fileUrl($cert->file) }}" target="_blank" rel="noopener">Sertifikat{!! $arrow !!}</a>@endif
                            @foreach ($cert->linkList() as $l)<a href="{{ $l['url'] }}" target="_blank" rel="noopener nofollow ugc">{{ $l['label'] }}{!! $arrow !!}</a>@endforeach
                        </p>
                    @endif
                </div>
            @empty
                <p class="cv-empty">Belum ada sertifikat.</p>
            @endforelse
        </div>
    </section>

    @if ($skills->isNotEmpty())
        <section class="cv-row">
            <h2 class="cv-label">Keahlian</h2>
            <dl class="cv-skills">
                @foreach (['mahir', 'menengah', 'pemula'] as $level)
                    @if ($byLevel->has($level))
                        <div><dt>{{ $levels[$level] }}</dt><dd>{{ $byLevel[$level]->pluck('name')->implode(', ') }}</dd></div>
                    @endif
                @endforeach
            </dl>
        </section>
    @endif

    @if (! empty($portfolio->interests))
        <section class="cv-row">
            <h2 class="cv-label">Minat</h2>
            <p class="cv-text cv-interests">{{ implode(', ', $portfolio->interests) }}</p>
        </section>
    @endif

    <footer class="cv-foot">
        <span>Dibuat melalui {{ config('app.name') }}, {{ $academic['university'] }}</span>
        <span>Diperbarui {{ $portfolio->updated_at?->locale('id')->translatedFormat('j F Y') }}</span>
    </footer>
</article>
</body>
</html>
