@extends('layouts.app')

@section('title', 'Rekap KP')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Rekap KP</h1>
            <p class="page-sub">Status, nilai, laporan, dan rekrutmen seluruh mahasiswa KP.</p>
        </div>
        <a href="{{ route('rekap.unduh', array_filter(['status' => $filter, 'q' => $search])) }}" class="btn btn-primary"><x-ikon name="download" :size="18" /> Unduh Excel</a>
    </div>

    <div class="stat-grid">
        <x-stat label="Mahasiswa KP" :value="$summary['total']" unit="mahasiswa" hint="Sesuai filter" />
        <x-stat label="KP selesai" :value="$summary['done']" unit="mahasiswa" tone="green" hint="Hasil sudah diputuskan" />
        <x-stat label="Nilai rata-rata" :value="$summary['average'] !== null ? \App\Support\Num::short($summary['average']) : null" empty="Belum ada" tone="gray" hint="Nilai akhir yang sah" />
        <x-stat label="Direkrut mitra" :value="$summary['hired']" unit="mahasiswa" tone="gray" hint="Menerima tawaran kerja" />
    </div>

    <form method="GET" action="{{ route('rekap.index') }}" class="recap-filter">
        <div class="recap-seg" role="radiogroup" aria-label="Status KP">
            @foreach (\App\Support\ProdiRecap::FILTERS as $key => [$label])
                <label @class(['is-on' => $filter === $key])>
                    <input type="radio" name="status" value="{{ $key }}" @checked($filter === $key) onchange="this.form.submit()">
                    <span>{{ $label }}</span>
                </label>
            @endforeach
        </div>
        <div class="recap-search">
            <input type="search" name="q" class="input" value="{{ $search }}" placeholder="Cari nama atau NIM" aria-label="Cari nama atau NIM">
            <button type="submit" class="btn">Cari</button>
        </div>
    </form>

    <section class="card">
        @if ($internships->isEmpty())
            <div class="empty"><strong>Tidak ada data</strong> Ubah filter atau kata kunci pencarian.</div>
        @else
            <div class="table-wrap">
                <table class="table recap-table">
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            <th>Tempat KP</th>
                            <th>Status</th>
                            <th class="num">Nilai akhir</th>
                            <th class="num">Sidang</th>
                            <th class="num">Laporan</th>
                            <th>Rekrutmen</th>
                            <th><span class="sr-only">Portofolio</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($internships as $i)
                            <?php
                                $score = \App\Support\ProdiRecap::finalScore($i);
                                $report = \App\Support\ProdiRecap::reportProgress($i);
                                $c = $i->conversion;
                            ?>
                            <tr>
                                <td><x-person :user="$i->student" /></td>
                                <td class="col-place" title="{{ $i->placeName() }}">{{ $i->placeName() }}<span class="recap-sub">{{ $i->lecturer?->listName() ?? 'Dosen belum ditunjuk' }}</span></td>
                                <td><x-chip :tone="$i->statusTone()">{{ $i->statusLabel() }}</x-chip></td>
                                <td class="num">@if ($score !== null)<span class="recap-grade"><b>{{ \App\Support\ProdiRecap::finalGrade($i) }}</b>{{ \App\Support\Num::short($score) }}</span>@else<span class="muted">–</span>@endif</td>
                                <td class="num">@if ($i->defenseScore)<span class="recap-grade"><b>{{ $i->defenseScore->letter_grade }}</b>{{ \App\Support\Num::short($i->defenseScore->score) }}</span>@else<span class="muted">–</span>@endif</td>
                                <td class="num">@if ($report)<span @class(['recap-report', 'is-done' => str_starts_with($report, (string) $i->reportChapters->count().'/')])>{{ $report }}</span>@else<span class="muted">–</span>@endif</td>
                                <td>@if ($c)<span class="recap-recruit tone-{{ $c->statusTone() }}" title="{{ $c->statusLabel() }}">{{ $c->statusLabel() }}</span>@if ($c->offered_position && $c->status !== 'direkomendasikan')<span class="recap-sub">{{ $c->offered_position }}</span>@endif @else<span class="muted">–</span>@endif</td>
                                <td class="cell-action"><a href="{{ route('portofolio.lihat', $i->student_id) }}" class="btn btn-sm btn-icon btn-ghost" title="Portofolio {{ $i->student->name }}" aria-label="Portofolio {{ $i->student->name }}"><x-ikon name="star" :size="16" /></a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
