@extends('layouts.app')

@section('title', $user->role === 'supervisor' ? 'Mahasiswa Magang' : 'Pantau Kegiatan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">{{ ['supervisor' => 'Mahasiswa Magang', 'dospem' => 'Mahasiswa Bimbingan'][$user->role] ?? 'Pantau Kegiatan' }}</h1>
            <p class="page-sub">{{ $user->role === 'supervisor' ? 'Mahasiswa '.\App\Support\Campus::university().', per kloter.' : 'Dikelompokkan per kloter.' }}</p>
        </div>
    </div>

    <form method="GET" class="filters pantau-filters" data-autosubmit>
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

    {{-- Kelompok per perusahaan + divisi; kloter (periode) tampil di setiap kartu supaya kartu bisa berjejer --}}
    @php($places = $cohorts->groupBy(fn ($c) => $c['first']->company_id.'-'.$c['first']->division_id.'-'.$c['first']->placeName()))
    @foreach ($places as $placeCohorts)
        @php($first = $placeCohorts->first()['first'])
        <section class="cohort" aria-labelledby="place-{{ $loop->index }}">
            <header class="cohort-head">
                <div class="min0">
                    <h2 class="cohort-title" id="place-{{ $loop->index }}">
                        {{ $first->placeName() }}
                        @if ($first->division)<span class="cohort-div">Divisi {{ $first->division->name }}</span>@endif
                    </h2>
                </div>
                <span class="cohort-count">{{ $placeCohorts->sum(fn ($c) => $c['items']->count()) }} mahasiswa</span>
            </header>
            <ul class="icard-grid">
                @foreach ($placeCohorts as $cohort)
                    @foreach ($cohort['items'] as $internship)
                        <x-intern-card :internship="$internship" :last="$lastActivity[$internship->id] ?? null"
                                       :cohort="$internship->cohortLabel().($cohort['many'] ? ' · Kloter '.$cohort['number'] : '')" />
                    @endforeach
                @endforeach
            </ul>
        </section>
    @endforeach
@endsection
