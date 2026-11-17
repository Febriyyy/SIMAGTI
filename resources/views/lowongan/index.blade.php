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
                <?php $left = $p->deadline ? (int) today()->diffInDays($p->deadline, false) : null; ?>
                <li class="card job-card">
                    <div class="job-card-head">
                        <x-org :name="$p->company->name" class="job-card-org" />
                        <div class="min0">
                            <h2 class="job-card-title">{{ $p->title }}</h2>
                            <p class="job-card-co">{{ $p->company->name }}</p>
                        </div>
                    </div>
                    <p class="job-card-meta">
                        <span><x-ikon name="building" :size="14" /> {{ $p->location ?: ($p->company->city ?: 'Lokasi menyusul') }}</span>
                        @if ($p->quota)<span><x-ikon name="users" :size="14" /> {{ $p->quota }} orang</span>@endif
                        @if ($left !== null)<span @class(['is-soon' => $left <= 7])><x-ikon name="clock" :size="14" /> {{ $left === 0 ? 'Hari ini terakhir' : 'Tutup '.$p->deadline->locale('id')->translatedFormat('j M') }}</span>@endif
                    </p>
                    <p class="job-card-desc">{{ \Illuminate\Support\Str::limit($p->description, 160) }}</p>
                    @if ($p->skills->isNotEmpty())
                        <p class="job-card-skills">{{ $p->skills->pluck('name')->join(' · ') }}</p>
                    @endif

                    <x-detail label="Detail">
                        <dl>
                            <div><dt>Deskripsi</dt><dd>{!! nl2br(e($p->description)) !!}</dd></div>
                            @if ($p->requirements)<div><dt>Syarat</dt><dd>{!! nl2br(e($p->requirements)) !!}</dd></div>@endif
                            @if ($p->company->address)<div><dt>Alamat</dt><dd>{{ $p->company->address }}</dd></div>@endif
                        </dl>
                    </x-detail>

                    @if ($canApply)
                        <a href="{{ route('kp.status', ['lowongan' => $p->id]) }}#ajukan" class="btn btn-primary job-card-cta">Ajukan KP di sini <x-ikon name="arrow" :size="16" /></a>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
@endsection
