@extends('layouts.app')

@section('title', $user->role === 'supervisor' ? 'Mahasiswa Magang' : 'Pantau Kegiatan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">{{ ['supervisor' => 'Mahasiswa Magang', 'dospem' => 'Mahasiswa Bimbingan'][$user->role] ?? 'Pantau Kegiatan' }}</h1>
            <p class="page-sub">{{ $user->role === 'supervisor' ? 'Mahasiswa '.\App\Support\Campus::university().', per kloter.' : 'Dikelompokkan per kloter.' }}</p>
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
            <x-intern-list>
                @foreach ($cohort['items'] as $internship)
                    <x-intern-row :internship="$internship" :last="$lastActivity[$internship->id] ?? null" />
                @endforeach
            </x-intern-list>
        </section>
    @endforeach
@endsection
