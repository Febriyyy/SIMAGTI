@extends('layouts.app')

@section('title', 'Lowongan Magang')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Lowongan Magang</h1>
            <p class="page-sub">Dari perusahaan mitra kampus. Pilih lowongan untuk mengajukan KP dengan data perusahaan terisi.</p>
        </div>
    </div>

    @if (! $canApply)
        <div class="alert alert-info" role="status">Anda sudah punya pengajuan atau KP yang berjalan. Lowongan tetap bisa dilihat sebagai referensi.</div>
    @endif

    @if ($postings->isEmpty())
        <section class="card"><div class="empty"><strong>Belum ada lowongan dibuka</strong> Lowongan dari perusahaan mitra akan tampil di sini.</div></section>
    @else
        <ul class="job-grid">
            @foreach ($postings as $p)
                <?php
                    $left = $p->deadline ? (int) today()->diffInDays($p->deadline, false) : null;
                    $tone = crc32($p->company->name) % 4;
                ?>
                <li class="job-card job-tone-{{ $tone }}">
                    <div class="job-card-band">
                        <x-org :name="$p->company->name" class="job-card-org" />
                        @if ($left !== null)
                            <span @class(['job-card-due', 'is-soon' => $left <= 7])>{{ $left === 0 ? 'Tutup hari ini' : ($left <= 7 ? $left.' hari lagi' : 'Tutup '.$p->deadline->locale('id')->translatedFormat('j M')) }}</span>
                        @endif
                    </div>

                    <div class="job-card-body">
                        <p class="job-card-co">{{ $p->company->name }}</p>
                        <h2 class="job-card-title">{{ $p->title }}</h2>

                        <dl class="job-card-facts">
                            <div><dt>Lokasi</dt><dd>{{ $p->location ?: ($p->company->city ?: 'Menyusul') }}</dd></div>
                            <div><dt>Kuota</dt><dd>{{ $p->quota ? $p->quota.' orang' : 'Terbuka' }}</dd></div>
                            <div><dt>Bidang</dt><dd title="{{ $p->company->industry }}">{{ $p->company->industry ?: '–' }}</dd></div>
                        </dl>

                        <p class="job-card-desc">{{ \Illuminate\Support\Str::limit($p->description, 150) }}</p>

                        @if ($p->skills->isNotEmpty())
                            <ul class="job-card-skills" aria-label="Keahlian">
                                @foreach ($p->skills->take(4) as $skill)<li>{{ $skill->name }}</li>@endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="job-card-foot">
                        <button type="button" class="btn btn-sm btn-ghost" data-open="job-{{ $p->id }}">Lihat detail</button>
                        @if ($canApply)
                            <a href="{{ route('kp.status', ['lowongan' => $p->id]) }}#ajukan" class="btn btn-sm btn-primary">Ajukan KP <x-ikon name="arrow" :size="16" /></a>
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
