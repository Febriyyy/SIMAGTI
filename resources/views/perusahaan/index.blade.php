@extends('layouts.app')

@section('title', 'Perusahaan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Perusahaan</h1>
            <p class="page-sub">Perusahaan mitra dan tempat KP yang diajukan mahasiswa.</p>
        </div>
        @if ($canManage)
            <a href="{{ route('perusahaan.create') }}" class="btn btn-primary"><x-ikon name="plus" :size="16" /> Tambah perusahaan</a>
        @endif
    </div>

    <form method="GET" action="{{ route('perusahaan.index') }}" class="inline-form">
        <input type="search" name="q" class="input" value="{{ $search }}" placeholder="Cari nama perusahaan" aria-label="Cari nama perusahaan">
        <button type="submit" class="btn">Cari</button>
    </form>

    <section class="card">
        @if ($companies->isEmpty())
            <div class="empty">
                <strong>Perusahaan tidak ditemukan</strong>
                Coba kata kunci lain atau tambahkan perusahaan baru.
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Perusahaan</th>
                            <th>Bidang</th>
                            <th>Status</th>
                            <th>Magang aktif</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($companies as $company)
                            <tr>
                                <td>
                                    <div class="cell-title truncate" title="{{ $company->name }}">{{ $company->name }}</div>
                                    <div class="cell-sub">{{ $company->city ?? '–' }}</div>
                                </td>
                                <td><span class="truncate truncate-sm" title="{{ $company->industry }}">{{ $company->industry ?? '–' }}</span></td>
                                <td><x-chip :tone="$company->statusTone()">{{ $company->statusLabel() }}</x-chip></td>
                                <td>
                                    <span class="cell-title">{{ $company->active_internships_count }}</span>
                                    <span class="muted">dari {{ $company->internships_count }} total</span>
                                </td>
                                <td class="col-action">
                                    <a href="{{ route('perusahaan.show', $company) }}" class="btn btn-sm">Statistik</a>
                                    @if ($canManage)
                                        <a href="{{ route('perusahaan.edit', $company) }}" class="btn btn-sm">Ubah</a>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
