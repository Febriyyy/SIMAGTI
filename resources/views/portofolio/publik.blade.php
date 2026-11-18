<?php
    // Portofolio mahasiswa: dipakai untuk tautan publik (tanpa login, tanpa NIM) dan untuk pihak kampus / supervisor.
    $initials = collect(preg_split('/\s+/', trim($student->name)))->filter(fn ($w) => preg_match('/^[\p{L}]/u', $w))->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
    $levels = \App\Models\Portfolio::LEVELS;
    $levelPct = ['mahir' => 100, 'menengah' => 66, 'pemula' => 33];
    $orgInitials = fn (string $name) => mb_strtoupper(collect(preg_split('/\s+/', trim(preg_replace('/^(PT|CV|UD|Yayasan)\.?\s+/i', '', $name))))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('')) ?: '?';
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
<body class="pz-body">
<header class="pz-bar">
    <img src="{{ asset('images/logo-simagti.svg') }}" alt="SIMAGTI" width="112" height="34">
    @unless ($public)
        <a href="{{ ! empty($back) && $back !== url()->current() ? $back : route('dashboard') }}" class="pz-back">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5M11 6l-6 6 6 6"/></svg>
            Kembali
        </a>
    @endunless
</header>

<main class="pz">
    {{-- Kiri: kartu profil yang menempel --}}
    <aside class="pz-side">
        <section class="pz-profile">
            <div class="pz-cover" aria-hidden="true"></div>
            <span class="pz-avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
            <h1 class="pz-name">{{ $student->name }}</h1>
            @if ($portfolio->headline)<p class="pz-headline">{{ $portfolio->headline }}</p>@endif

            <ul class="pz-meta">
                @if ($academic['program'])
                    <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 9.5 12 5l9 4.5-9 4.5-9-4.5Z"/><path d="M7 11.5V16c0 1.2 2.2 2.5 5 2.5s5-1.3 5-2.5v-4.5"/></svg>{{ $academic['program'] }}</li>
                @endif
                <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 20V9l8-5 8 5v11M9 20v-6h6v6"/></svg>{{ $academic['university'] }}</li>
                @if ($academic['entry_year'])
                    <li><svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/></svg>Angkatan {{ $academic['entry_year'] }}</li>
                @endif
            </ul>

            @if ($gpa !== null || $portfolio->projects->isNotEmpty() || $portfolio->certificates->isNotEmpty())
                <dl class="pz-stats">
                    @if ($gpa !== null)<div><dt>IPK</dt><dd>{{ number_format((float) $gpa, 2, ',', '.') }}</dd></div>@endif
                    <div><dt>Proyek</dt><dd>{{ $portfolio->projects->count() }}</dd></div>
                    <div><dt>Sertifikat</dt><dd>{{ $portfolio->certificates->count() }}</dd></div>
                </dl>
            @endif

            @if (! empty($portfolio->interests))
                <div class="pz-block">
                    <h2 class="pz-label">Minat</h2>
                    <ul class="pz-interests">@foreach ($portfolio->interests as $interest)<li>{{ $interest }}</li>@endforeach</ul>
                </div>
            @endif
        </section>

        @if ($skills->isNotEmpty())
            <section class="pz-card">
                <h2 class="pz-label">Skill</h2>
                <ul class="pz-skills">
                    @foreach ($skills as $skill)
                        <li>
                            <div class="pz-skill-top"><b>{{ $skill->name }}</b><span>{{ $levels[$skill->pivot->level] ?? '' }}</span></div>
                            <span class="pz-skill-bar"><i style="width: {{ $levelPct[$skill->pivot->level] ?? 50 }}%"></i></span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($internship)
            <section class="pz-card">
                <h2 class="pz-label">Pengalaman magang</h2>
                <div class="pz-exp">
                    <span class="pz-exp-dot" aria-hidden="true"></span>
                    <div class="min0">
                        <span class="pz-exp-period">{{ $internship->periodLabel() }}</span>
                        <b>{{ $internship->position ?? 'Kerja Praktik' }}</b>
                        <span class="pz-exp-place">{{ $internship->placeName() }}</span>
                        @if ($internship->supervisor)
                            <span class="pz-exp-mentor">Dibimbing <b>{{ $internship->supervisor->name }}</b></span>
                        @endif
                    </div>
                </div>
            </section>
        @endif
    </aside>

    {{-- Kanan: cerita, proyek, sertifikat --}}
    <div class="pz-main">
        <section class="pz-intro">
            <span class="pz-kicker">Halo, saya {{ \Illuminate\Support\Str::of($student->name)->explode(' ')->first() }}</span>
            <p class="pz-about">{{ $portfolio->about ?: 'Mahasiswa '.($academic['program'] ?? '').' di '.$academic['university'].'.' }}</p>
        </section>

        <section class="pz-section">
            <div class="pz-section-head">
                <h2 class="pz-title">Proyek</h2>
                <span class="pz-count">{{ $portfolio->projects->count() }}</span>
            </div>
            @if ($portfolio->projects->isEmpty())
                <p class="pz-empty">Belum ada proyek yang ditambahkan.</p>
            @else
                <ul class="pz-projects">
                    @foreach ($portfolio->projects as $i => $project)
                        <li class="pz-project">
                            <div class="pz-project-cover pz-cover-{{ $i % 4 }}">
                                @if ($project->image)
                                    <img src="{{ $fileUrl($project->image) }}" alt="Pratinjau {{ $project->title }}" loading="lazy">
                                @else
                                    <span aria-hidden="true">{{ mb_strtoupper(mb_substr($project->title, 0, 1)) }}</span>
                                @endif
                                @if ($project->year)<em>{{ $project->year }}</em>@endif
                            </div>
                            <div class="pz-project-body">
                                <h3>{{ $project->title }}</h3>
                                @if ($project->role)<p class="pz-project-role">{{ $project->role }}</p>@endif
                                @if ($project->description)<p class="pz-project-desc">{{ $project->description }}</p>@endif
                                @if ($project->techList())
                                    <ul class="pz-tags">@foreach ($project->techList() as $t)<li>{{ $t }}</li>@endforeach</ul>
                                @endif
                                @if ($project->linkList())
                                    <div class="pz-links">
                                        @foreach ($project->linkList() as $l)
                                            <a href="{{ $l['url'] }}" target="_blank" rel="noopener nofollow ugc">{{ $l['label'] }}<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8"/></svg></a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="pz-section">
            <div class="pz-section-head">
                <h2 class="pz-title">Sertifikat pelatihan</h2>
                <span class="pz-count">{{ $portfolio->certificates->count() }}</span>
            </div>
            @if ($portfolio->certificates->isEmpty())
                <p class="pz-empty">Belum ada sertifikat yang ditambahkan.</p>
            @else
                <ul class="pz-certs">
                    @foreach ($portfolio->certificates as $cert)
                        <li class="pz-cert">
                            <div class="pz-cert-stub">
                                <span class="pz-cert-org" aria-hidden="true">{{ $orgInitials($cert->issuer) }}</span>
                                @if ($cert->issued_on)<span class="pz-cert-date">{{ $cert->issued_on->locale('id')->translatedFormat('M Y') }}</span>@endif
                            </div>
                            <div class="pz-cert-body">
                                <b>{{ $cert->name }}</b>
                                <span>{{ $cert->issuer }}</span>
                                @if ($cert->file || $cert->linkList())
                                    <div class="pz-links">
                                        @if ($cert->file)<a href="{{ $fileUrl($cert->file) }}" target="_blank" rel="noopener">Lihat sertifikat<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8"/></svg></a>@endif
                                        @foreach ($cert->linkList() as $l)<a href="{{ $l['url'] }}" target="_blank" rel="noopener nofollow ugc">{{ $l['label'] }}<svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 17 17 7M9 7h8v8"/></svg></a>@endforeach
                                    </div>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <p class="pz-foot">Diisi oleh mahasiswa melalui {{ config('app.name') }}, Sistem Monitoring Magang Terintegrasi {{ $academic['university'] }}.</p>
    </div>
</main>
</body>
</html>
