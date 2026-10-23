@extends('layouts.app')

@section('title', $user->role === 'supervisor' ? 'Mahasiswa Magang' : 'Pantau Kegiatan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">{{ ['supervisor' => 'Mahasiswa Magang', 'dospem' => 'Mahasiswa Bimbingan'][$user->role] ?? 'Pantau Kegiatan' }}</h1>
            <p class="page-sub">Dikelompokkan per kloter: tempat, divisi, dan periode magang yang sama.</p>
        </div>
    </div>

    <form method="GET" class="filters" data-autosubmit>
        <div class="filter-search">
            <x-ikon name="search" :size="16" />
            <input type="search" name="cari" value="{{ $term }}" placeholder="Cari nama atau NIM…" aria-label="Cari mahasiswa" class="input input-sm">
        </div>
        <div class="segmented">
            <a href="{{ route('pantau.index', ['status' => 'berjalan', 'cari' => $term ?: null]) }}" @class(['seg', 'is-active' => $status === 'berjalan'])>Sedang berjalan</a>
            <a href="{{ route('pantau.index', ['status' => 'selesai', 'cari' => $term ?: null]) }}" @class(['seg', 'is-active' => $status === 'selesai'])>Selesai</a>
        </div>
        <input type="hidden" name="status" value="{{ $status }}">
    </form>

    @if ($cohorts->isEmpty())
        <div class="card">
            <div class="empty">
                <strong>Tidak ada mahasiswa</strong>
                {{ $term !== '' ? 'Tidak ada yang cocok dengan pencarian.' : 'Belum ada mahasiswa dengan status ini.' }}
            </div>
        </div>
    @endif

    @foreach ($cohorts as $cohort)
        @php($first = $cohort['first'])
        <section class="card">
            <div class="card-head">
                <div class="min0">
                    <h2 class="card-title">
                        {{ $first->placeName() }}
                        @if ($first->division) <span class="muted">· Divisi {{ $first->division->name }}</span> @endif
                    </h2>
                    <p class="card-sub">
                        <span class="cohort-badge">{{ $first->isSeminar() ? 'Seminar' : $first->cohortLabel() }}@if ($cohort['many']) · Kloter {{ $cohort['number'] }}@endif</span>
                        {{ $first->periodLabel() }} · {{ $first->durationMonths() }} bulan · {{ $cohort['items']->count() }} mahasiswa
                    </p>
                </div>
            </div>
            <div class="table-wrap">
                <table class="table table-compact">
                    <thead>
                        <tr>
                            <th style="width: 30%">Mahasiswa</th>
                            <th style="width: 22%">Posisi</th>
                            <th>Task selesai</th>
                            <th class="col-num">Jam</th>
                            <th>Silabus</th>
                            <th>Waktu</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cohort['items'] as $internship)
                            @php($last = $lastActivity[$internship->id] ?? null)
                            <tr>
                                <td class="col-fluid">
                                    <div class="person">
                                        <x-avatar :user="$internship->student" small />
                                        <div class="person-text">
                                            <span class="person-name" title="{{ $internship->student->name }}">{{ $internship->student->name }}</span>
                                            <span class="person-sub">{{ $internship->student->nim }} · <x-academic :user="$internship->student" compact /></span>
                                        </div>
                                    </div>
                                </td>
                                <td class="col-fluid">
                                    <span class="truncate">{{ $internship->isSeminar() ? $internship->programTypeLabel() : ($internship->position ?? '–') }}</span>
                                    <div class="cell-sub truncate">{{ $user->role === 'supervisor' ? \App\Support\Campus::university() : ($internship->supervisor?->name ?? $internship->lecturer?->name ?? '–') }}</div>
                                </td>
                                <td><x-progress :value="$internship->percent()" /><div class="cell-sub">{{ $internship->completed_tasks_count }} dari {{ $internship->tasks_count }} task</div></td>
                                <td class="col-num">{{ \App\Support\Num::short((float) ($internship->logged_hours ?? 0)) }}</td>
                                <td><x-chip :tone="$internship->syllabusStatusTone()">{{ $internship->syllabusStatusLabel() }}</x-chip></td>
                                <td>
                                    @if ($internship->status === 'berjalan')
                                        Minggu {{ $internship->currentWeek() }}/{{ $internship->totalWeeks() }}
                                        <div class="cell-sub">sisa {{ $internship->daysLeft() }} hari</div>
                                    @else
                                        <span class="muted">Selesai</span>
                                    @endif
                                    <div class="cell-sub">{{ $last ? 'Catatan terakhir '.mb_strtolower(\App\Support\Relative::label(\Illuminate\Support\Carbon::parse($last))) : 'Belum ada catatan' }}</div>
                                </td>
                                <td class="col-action"><a href="{{ route('pantau.show', $internship) }}" class="btn btn-sm">Buka</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
@endsection
