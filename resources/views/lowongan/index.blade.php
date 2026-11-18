@extends('layouts.app')

@section('title', 'Lowongan Magang')

@section('content')
    <section class="jb-hero">
        <svg class="jb-hero-wave" viewBox="0 0 220 90" aria-hidden="true"><path d="M6 70 C 30 70, 34 18, 62 18 S 92 72, 118 72 S 150 18, 176 18 S 206 52, 214 52" fill="none" stroke="currentColor" stroke-width="14" stroke-linecap="round"/></svg>
        <div class="jb-hero-text">
            <h1 class="jb-hero-title">{{ $postings->count() > 0 ? 'Saatnya mulai magang.' : 'Lowongan magang' }}</h1>
            <p class="jb-hero-sub">
                @if ($postings->count() > 0)
                    <b>{{ $postings->count() }} lowongan</b> dari perusahaan mitra kampus sedang dibuka. Pilih yang paling cocok, data perusahaan langsung terisi saat mengajukan KP.
                @else
                    Lowongan dari perusahaan mitra kampus akan tampil di sini.
                @endif
            </p>
        </div>
        @unless ($hasPortfolio)
            <a href="{{ route('portofolio.edit') }}" class="jb-hero-cta">
                <span><b>Lengkapi portofolio</b><small>Skill dan proyekmu dilihat perusahaan saat kamu mengajukan.</small></span>
                <span class="jb-hero-cta-go" aria-hidden="true"><x-ikon name="arrow-up-right" :size="18" /></span>
            </a>
        @endunless
    </section>

    @if (! $canApply)
        <div class="alert alert-info" role="status">Anda sudah punya pengajuan atau KP yang berjalan. Lowongan tetap bisa dilihat sebagai referensi.</div>
    @endif

    @if ($postings->isEmpty())
        <section class="card"><div class="empty"><strong>Belum ada lowongan dibuka</strong> Sambil menunggu, lengkapi portofolio agar siap saat lowongan dibuka.</div></section>
    @else
        <ul class="jb-grid">
            @foreach ($postings as $p)
                <?php
                    $left = $p->deadline ? (int) today()->diffInDays($p->deadline, false) : null;
                    $matched = $p->skills->filter(fn ($s) => in_array($s->id, $mySkills, true));
                ?>
                <li class="jb-card">
                    <div class="jb-card-top">
                        <x-org :name="$p->company->name" class="jb-org" />
                        <div class="min0">
                            <p class="jb-co">{{ $p->company->name }}</p>
                            @if ($left !== null)
                                <span @class(['jb-due', 'is-soon' => $left <= 7])>{{ $left === 0 ? 'Tutup hari ini' : ($left <= 7 ? 'Tutup '.$left.' hari lagi' : 'Tutup '.$p->deadline->locale('id')->translatedFormat('j M Y')) }}</span>
                            @endif
                        </div>
                    </div>

                    <h2 class="jb-title">{{ $p->title }}</h2>

                    <p class="jb-facts">
                        <span><x-ikon name="placement" :size="15" /> {{ $p->location ?: ($p->company->city ?: 'Lokasi menyusul') }}</span>
                        <span><x-ikon name="users" :size="15" /> {{ $p->quota ? $p->quota.' orang' : 'Kuota terbuka' }}</span>
                        @if ($p->company->industry)<span><x-ikon name="building" :size="15" /> {{ $p->company->industry }}</span>@endif
                    </p>

                    <p class="jb-desc">{{ \Illuminate\Support\Str::limit($p->description, 140) }}</p>

                    @if ($p->skills->isNotEmpty())
                        <ul class="jb-skills" aria-label="Keahlian">
                            @foreach ($p->skills->take(5) as $skill)
                                <li @class(['is-match' => in_array($skill->id, $mySkills, true)])>{{ $skill->name }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($p->skills->isNotEmpty() && $matched->isNotEmpty())
                        <div class="jb-fit" title="Skill di portofoliomu yang dicari lowongan ini">
                            <span>Kecocokan skill</span>
                            <span class="jb-fit-bar"><i style="width: {{ round($matched->count() / $p->skills->count() * 100) }}%"></i></span>
                            <b>{{ $matched->count() }}/{{ $p->skills->count() }}</b>
                        </div>
                    @endif

                    <div class="jb-foot">
                        <button type="button" class="jb-detail" data-open="job-{{ $p->id }}">Lihat detail</button>
                        @if ($canApply)
                            <a href="{{ route('kp.status', ['lowongan' => $p->id]) }}#ajukan" class="btn btn-primary jb-apply">Ajukan KP <x-ikon name="arrow" :size="16" /></a>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>

        @foreach ($postings as $p)
            <dialog id="job-{{ $p->id }}" class="dialog">
                <div class="dialog-body">
                    <div class="dialog-head">
                        <div class="job-dlg-head">
                            <x-org :name="$p->company->name" class="job-card-org" />
                            <div class="min0">
                                <h2 class="card-title">{{ $p->title }}</h2>
                                <p class="card-sub">{{ $p->company->name }}</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-icon btn-ghost" data-close aria-label="Tutup">×</button>
                    </div>
                    <div class="job-dlg-section">
                        <h3>Deskripsi pekerjaan</h3>
                        <p>{!! nl2br(e($p->description)) !!}</p>
                    </div>
                    @if ($p->requirements)
                        <div class="job-dlg-section">
                            <h3>Syarat</h3>
                            <p>{!! nl2br(e($p->requirements)) !!}</p>
                        </div>
                    @endif
                    <dl class="job-card-facts">
                        <div><dt>Lokasi</dt><dd>{{ $p->location ?: ($p->company->city ?: 'Menyusul') }}</dd></div>
                        <div><dt>Kuota</dt><dd>{{ $p->quota ? $p->quota.' orang' : 'Terbuka' }}</dd></div>
                        <div><dt>Tutup</dt><dd>{{ $p->deadline?->locale('id')->translatedFormat('j F Y') ?? 'Belum ditentukan' }}</dd></div>
                    </dl>
                    @if ($p->company->address)<p class="muted job-dlg-addr">{{ $p->company->address }}</p>@endif
                    <div class="dialog-foot row-actions">
                        <button type="button" class="btn" data-close>Tutup</button>
                        @if ($canApply)
                            <a href="{{ route('kp.status', ['lowongan' => $p->id]) }}#ajukan" class="btn btn-primary">Ajukan KP di sini <x-ikon name="arrow" :size="16" /></a>
                        @endif
                    </div>
                </div>
            </dialog>
        @endforeach
    @endif
@endsection
