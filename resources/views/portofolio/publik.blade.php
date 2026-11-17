<?php
    // Portofolio mahasiswa: dipakai untuk tautan publik (tanpa login, tanpa NIM) dan untuk pihak kampus / supervisor.
    $initials = collect(preg_split('/\s+/', trim($student->name)))->filter(fn ($w) => preg_match('/^[\p{L}]/u', $w))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $place = collect([$academic['program'], $academic['university']])->filter()->implode(', ');
    $levels = \App\Models\Portfolio::LEVELS;
    $byLevel = $skills->groupBy(fn ($s) => $s->pivot->level);
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
<body class="pv-body">
<main class="pv">
    <div class="pv-top">
        <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" width="112" height="34">
        @unless ($public)
            <a href="{{ ! empty($back) && $back !== url()->current() ? $back : route('dashboard') }}" class="pv-back">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
                Kembali
            </a>
        @endunless
    </div>

    <header class="pv-hero">
        <svg class="pv-hero-wave" viewBox="0 0 220 90" aria-hidden="true"><path d="M6 70 C 30 70, 34 18, 62 18 S 92 72, 118 72 S 150 18, 176 18 S 206 52, 214 52" fill="none" stroke="currentColor" stroke-width="14" stroke-linecap="round"/></svg>
        <span class="pv-avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
        <div class="pv-who">
            <h1 class="pv-name">{{ $student->name }}</h1>
            @if ($portfolio->headline)<p class="pv-headline">{{ $portfolio->headline }}</p>@endif
            <p class="pv-meta">{{ $place ?: $academic['university'] }}@if ($academic['entry_year']), angkatan {{ $academic['entry_year'] }}@endif</p>
            @if (! empty($portfolio->interests))
                <ul class="pv-interests" aria-label="Minat">@foreach ($portfolio->interests as $interest)<li>{{ $interest }}</li>@endforeach</ul>
            @endif
        </div>
        @if ($gpa !== null)
            <div class="pv-gpa"><b>{{ number_format((float) $gpa, 2, ',', '.') }}</b><span>IPK</span></div>
        @endif
    </header>

    <div class="pv-grid">
        <div class="pv-main">
            @if ($portfolio->about)
                <section class="pv-card">
                    <h2 class="pv-title">Tentang</h2>
                    <p class="pv-about">{{ $portfolio->about }}</p>
                </section>
            @endif

            <section class="pv-card">
                <h2 class="pv-title">Proyek</h2>
                @if ($portfolio->projects->isEmpty())
                    <p class="pv-empty">Belum ada proyek yang ditambahkan.</p>
                @else
                    <ul class="pv-projects">
                        @foreach ($portfolio->projects as $project)
                            <li class="pv-project">
                                @if ($project->image)
                                    <img class="pv-project-img" src="{{ $fileUrl($project->image) }}" alt="Pratinjau {{ $project->title }}" loading="lazy">
                                @endif
                                <div class="pv-project-body">
                                    <div class="pv-project-head">
                                        <h3>{{ $project->title }}</h3>
                                        @if ($project->year)<span>{{ $project->year }}</span>@endif
                                    </div>
                                    @if ($project->role)<p class="pv-project-role">{{ $project->role }}</p>@endif
                                    @if ($project->description)<p class="pv-project-desc">{{ $project->description }}</p>@endif
                                    @if ($project->techList())
                                        <ul class="pv-tags">@foreach ($project->techList() as $t)<li>{{ $t }}</li>@endforeach</ul>
                                    @endif
                                    @if ($project->url)
                                        <a href="{{ $project->url }}" class="pv-link" target="_blank" rel="noopener nofollow ugc">
                                            {{ $project->host() ?: 'Buka tautan' }}
                                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8"/></svg>
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="pv-card">
                <h2 class="pv-title">Sertifikat pelatihan</h2>
                @if ($portfolio->certificates->isEmpty())
                    <p class="pv-empty">Belum ada sertifikat yang ditambahkan.</p>
                @else
                    <ul class="pv-certs">
                        @foreach ($portfolio->certificates as $cert)
                            <li class="pv-cert">
                                <span class="pv-cert-icon" aria-hidden="true"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="6"/><path d="M8.5 14.5 7 22l5-3 5 3-1.5-7.5"/></svg></span>
                                <div class="min0">
                                    <b>{{ $cert->name }}</b>
                                    <small>{{ $cert->issuer }}@if ($cert->issued_on), {{ $cert->issued_on->locale('id')->translatedFormat('F Y') }}@endif</small>
                                </div>
                                <div class="pv-cert-actions">
                                    @if ($cert->file)<a href="{{ $fileUrl($cert->file) }}" target="_blank" rel="noopener">Lihat</a>@endif
                                    @if ($cert->credential_url)<a href="{{ $cert->credential_url }}" target="_blank" rel="noopener nofollow ugc">Kredensial</a>@endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside class="pv-side">
            <section class="pv-card">
                <h2 class="pv-title">Skill</h2>
                @if ($skills->isEmpty())
                    <p class="pv-empty">Belum ada skill yang ditambahkan.</p>
                @else
                    @foreach (['mahir', 'menengah', 'pemula'] as $level)
                        @if ($byLevel->has($level))
                            <div class="pv-skill-group">
                                <span class="pv-skill-level pv-level-{{ $level }}">{{ $levels[$level] }}</span>
                                <ul class="pv-skills">@foreach ($byLevel[$level] as $skill)<li>{{ $skill->name }}</li>@endforeach</ul>
                            </div>
                        @endif
                    @endforeach
                @endif
            </section>

            @if ($internship)
                <section class="pv-card pv-exp">
                    <h2 class="pv-title">Pengalaman magang</h2>
                    <b class="pv-exp-place">{{ $internship->placeName() }}</b>
                    <span class="pv-exp-role">{{ $internship->position ?? 'Kerja Praktik' }}</span>
                    <span class="pv-exp-period">{{ $internship->periodLabel() }}</span>
                    @if ($internship->supervisor)
                        <div class="pv-exp-mentor">
                            <span>Dibimbing oleh</span>
                            <b>{{ $internship->supervisor->name }}</b>
                        </div>
                    @endif
                </section>
            @endif
        </aside>
    </div>

    <p class="pv-foot">Portofolio ini diisi oleh mahasiswa melalui {{ config('app.name') }}, Sistem Monitoring Magang Terintegrasi {{ $academic['university'] }}.</p>
</main>
</body>
</html>
