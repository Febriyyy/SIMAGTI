@extends('layouts.app')

@section('title', 'Dashboard TU Fakultas')

@section('content')
    <x-hero eyebrow="TU Fakultas"
            :title="\App\Support\Greeting::now().', '.$user->firstName()"
            :sub="$stats['verify'] > 0
                ? $stats['verify'].' KP mandiri menunggu verifikasi perusahaan.'
                : ($stats['pending'] > 0
                    ? $stats['pending'].' Company Supervisor ('.$stats['students'].' mahasiswa) menunggu diaktifkan.'
                    : 'Tidak ada antrean verifikasi maupun aktivasi supervisor.')"
            :action="$stats['verify'] > 0 ? 'Buka verifikasi' : 'Buka aktivasi'" :href="$stats['verify'] > 0 ? route('tu.verifikasi') : route('tu.aktivasi')" />

    <div class="stat-grid">
        <x-stat label="Perlu verifikasi" :value="$stats['verify']" unit="KP" :tone="$stats['verify'] > 0 ? 'amber' : 'gray'" :href="route('tu.verifikasi')"
                hint="KP mandiri" />
        <x-stat label="Menunggu aktivasi" :value="$stats['pending']" unit="supervisor" :href="route('tu.aktivasi')"
                :hint="$stats['students'].' mahasiswa belum punya supervisor'" />
        <x-stat label="Surat pengantar" :value="$stats['letters']" unit="diminta" :tone="$stats['letters'] > 0 ? 'amber' : 'gray'" :href="route('tu.surat')"
                hint="Menunggu diterbitkan" />
        <x-stat label="KP berjalan" :value="$stats['running']" unit="mahasiswa" tone="gray" :href="route('penempatan.index')" :hint="$stats['partners'].' perusahaan mitra'" />
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Antrean aktivasi</h2>
                        <p class="card-sub">Dikelompokkan per perusahaan dan email supervisor dari formulir mahasiswa</p>
                    </div>
                    <a href="{{ route('tu.aktivasi') }}" class="btn btn-sm">Proses</a>
                </div>
                @if ($groups->isEmpty())
                    <div class="calm">
                        <span class="calm-icon"><x-ikon name="check" :size="20" /></span>
                        <p>Tidak ada antrean. Pengajuan baru muncul di sini setelah disetujui kaprodi.</p>
                    </div>
                @else
                    <ul class="item-list">
                        @foreach ($groups->take(8) as $group)
                            @php($i = $group['first'])
                            <li class="item-row">
                                <span class="item-icon"><x-ikon name="building" :size="18" /></span>
                                <div class="grow" style="min-width: 0">
                                    <div class="cell-title truncate">{{ $i->supervisor_name ?? 'Nama belum diisi' }} · {{ $i->company?->name }}</div>
                                    <div class="cell-sub truncate">{{ $i->supervisor_email }} · {{ $group['count'] }} mahasiswa</div>
                                </div>
                                @if ($group['invited'])
                                    <x-chip tone="amber">Diundang</x-chip>
                                @else
                                    <x-chip tone="gray">Baru</x-chip>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Baru diaktifkan</h2>
                </div>
                @if ($recent->isEmpty())
                    <p class="meeting-empty">Belum ada.</p>
                @else
                    <ul class="item-list">
                        @foreach ($recent as $staff)
                            <li class="item-row">
                                <x-avatar :user="$staff->user" small />
                                <div class="grow" style="min-width: 0">
                                    <div class="cell-title truncate" title="{{ $staff->user->name }}">{{ $staff->user->listName() }}</div>
                                    <div class="cell-sub truncate">{{ $staff->internship->company?->name }} · {{ $staff->internship->student->name }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="card">
                <div class="card-head">
                    <h2 class="card-title">Cara aktivasi</h2>
                </div>
                <div class="card-body">
                    <ol class="howto">
                        <li><strong>Periksa data</strong> nama, jabatan, dan email supervisor dari formulir mahasiswa. Koreksi email bila salah ketik.</li>
                        <li><strong>Sudah punya akun?</strong> Tautkan langsung, mahasiswa langsung terhubung.</li>
                        <li><strong>Belum punya akun?</strong> Terbitkan undangan lalu kirim link-nya. Satu undangan berlaku untuk semua mahasiswa dengan email yang sama.</li>
                    </ol>
                </div>
            </section>
        </div>
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
                <table class="table dt">
                    <thead>
                        <tr>
                            <th>Perusahaan</th>
                            <th class="w-meta hide-md">Bidang</th>
                            <th class="w-num num">Magang aktif</th>
                            <th class="w-grade num hide-md">Nilai</th>
                            <th class="w-status">Status</th>
                            <th class="w-action" aria-label="Aksi"></th>
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
                                            <span class="c-sub">{{ $company->city ?? 'Kota belum diisi' }}</span>
                                        </span>
                                    </a>
                                </td>
                                <td class="hide-md"><span class="c-main c-plain" title="{{ $company->industry }}">{{ $company->industry ?? '–' }}</span></td>
                                <td class="num">
                                    <span class="c-main">{{ $company->active_internships_count }}</span>
                                    <span class="c-sub">dari {{ $company->internships_count }}</span>
                                </td>
                                <td class="num hide-md"><span class="c-main">{{ $company->average_score ? \App\Support\Num::short($company->average_score) : '–' }}</span></td>
                                <td><x-chip :tone="$company->statusTone()">{{ $company->statusLabel() }}</x-chip></td>
                                <td class="act"><a href="{{ route('perusahaan.show', $company) }}" class="btn btn-quiet">Statistik</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
