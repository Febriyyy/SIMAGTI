@extends('layouts.app')

@section('title', 'Dashboard Biro Kerjasama')

@section('content')
    <x-hero eyebrow="Biro Kerjasama"
            :title="\App\Support\Greeting::now().', '.$user->firstName()"
            :sub="$stats['waiting'] > 0
                ? $stats['waiting'].' perusahaan dari jalur KP mandiri menunggu verifikasi Anda.'
                : 'Tidak ada perusahaan yang menunggu verifikasi. Pantau mitra dan statistiknya di bawah.'"
            action="Buka verifikasi" :href="route('biro.verifikasi')" />

    <div class="stat-grid">
        <x-stat label="Perusahaan mitra" :value="$stats['partners']" unit="perusahaan" icon="building" hint="Termasuk KP mandiri" />
        <x-stat label="Perlu verifikasi" :value="$stats['waiting']" unit="perusahaan" icon="clock"
                :tone="$stats['waiting'] > 0 ? 'amber' : 'gray'" :href="route('biro.verifikasi')" hint="Dari jalur KP mandiri" />
        <x-stat label="Magang aktif" :value="$stats['students']" unit="mahasiswa" icon="users" hint="Di semua perusahaan" />
    </div>

    <section class="card">
        <div class="card-head">
            <div>
                <h2 class="card-title">Perusahaan</h2>
                <p class="card-sub">Urut dari yang paling banyak mahasiswa magang</p>
            </div>
            <a href="{{ route('perusahaan.index') }}" class="btn btn-sm">Kelola perusahaan</a>
        </div>

        @if ($companies->isEmpty())
            <div class="empty">
                <strong>Belum ada perusahaan</strong>
                Perusahaan akan muncul setelah ditambahkan.
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Perusahaan</th>
                            <th>Bidang</th>
                            <th>Magang aktif</th>
                            <th>Nilai rata-rata</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($companies as $company)
                            <tr>
                                <td>
                                    <div class="cell-title truncate" title="{{ $company->name }}">{{ $company->name }}</div>
                                    <div class="cell-sub">{{ $company->city ?? 'Kota belum diisi' }}</div>
                                </td>
                                <td><span class="truncate truncate-sm" title="{{ $company->industry }}">{{ $company->industry ?? '–' }}</span></td>
                                <td class="nowrap">
                                    <span class="cell-title">{{ $company->active_internships_count }}</span>
                                    <span class="muted">dari {{ $company->internships_count }} total</span>
                                </td>
                                <td>{{ \App\Support\Num::short($company->average_score) }}</td>
                                <td><x-chip :tone="$company->statusTone()">{{ $company->statusLabel() }}</x-chip></td>
                                <td class="col-action"><a href="{{ route('perusahaan.show', $company) }}" class="btn btn-sm">Statistik</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
