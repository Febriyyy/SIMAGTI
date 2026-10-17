@extends('layouts.app')

@section('title', 'Perusahaan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Perusahaan</h1>
            <p class="page-sub">Perusahaan mitra dan tempat KP yang diajukan mahasiswa.</p>
        </div>
        @if ($canManage)
            <a href="{{ route('perusahaan.create') }}" class="btn btn-primary"><x-icon name="plus" :size="16" /> Tambah perusahaan</a>
        @endif
    </div>

    <form method="GET" action="{{ route('perusahaan.index') }}" class="inline-form" style="margin-bottom: 16px">
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
                            <th>Mahasiswa aktif</th>
                            <th>Total KP</th>
                            @if ($canManage)<th></th>@endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($companies as $company)
                            <tr>
                                <td>
                                    <div class="cell-title">{{ $company->name }}</div>
                                    <div class="cell-sub">{{ $company->city ?? '–' }}</div>
                                </td>
                                <td>{{ $company->industry ?? '–' }}</td>
                                <td><x-chip :tone="$company->statusTone()">{{ $company->statusLabel() }}</x-chip></td>
                                <td>{{ $company->active_internships_count }}</td>
                                <td>{{ $company->internships_count }}</td>
                                @if ($canManage)
                                    <td class="col-action"><a href="{{ route('perusahaan.edit', $company) }}" class="btn btn-sm">Ubah</a></td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
