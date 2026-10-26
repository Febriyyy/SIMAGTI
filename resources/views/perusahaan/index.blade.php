@extends('layouts.app')

@section('title', 'Perusahaan')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Perusahaan</h1>
            <p class="page-sub">Mitra dan tempat KP mandiri.</p>
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
                <table class="table dt">
                    <thead>
                        <tr>
                            <th>Perusahaan</th>
                            <th class="w-meta hide-md">Bidang</th>
                            <th class="w-status">Status</th>
                            <th class="w-num num">Magang aktif</th>
                            <th class="w-action" aria-label="Aksi" @if ($canManage) style="width: 168px" @endif></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($companies as $company)
                            <tr>
                                <td>
                                    <a href="{{ route('perusahaan.show', $company) }}" class="ident">
                                        <x-org :name="$company->name" />
                                        <span>
                                            <span class="c-main" title="{{ $company->name }}">{{ $company->name }}</span>
                                            <span class="c-sub">{{ $company->city ?? '–' }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="hide-md"><span class="c-main c-plain" title="{{ $company->industry }}">{{ $company->industry ?? '–' }}</span></td>
                                <td><x-chip :tone="$company->statusTone()">{{ $company->statusLabel() }}</x-chip></td>
                                <td class="num">
                                    <span class="c-main c-of"><b>{{ $company->active_internships_count }}</b> <small>dari {{ $company->internships_count }}</small></span>
                                </td>
                                <td class="act">
                                    @if ($canManage)
                                        <a href="{{ route('perusahaan.edit', $company) }}" class="btn btn-quiet">Ubah</a>
                                    @endif
                                    <a href="{{ route('perusahaan.show', $company) }}" class="btn btn-quiet">Statistik</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
