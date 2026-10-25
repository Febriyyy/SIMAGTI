@extends('layouts.app')

@section('title', 'Dashboard TU Fakultas')

@section('content')
    <x-hero eyebrow="TU Fakultas"
            :title="\App\Support\Greeting::now().', '.$user->firstName()"
            :sub="$stats['pending'] > 0
                ? $stats['pending'].' Company Supervisor ('.$stats['students'].' mahasiswa) menunggu diaktifkan.'
                : 'Semua Company Supervisor dari pengajuan mahasiswa sudah aktif.'"
            action="Buka aktivasi" :href="route('tu.aktivasi')" />

    <div class="stat-grid">
        <x-stat label="Menunggu aktivasi" :value="$stats['pending']" unit="supervisor" :href="route('tu.aktivasi')"
                :hint="$stats['students'].' mahasiswa belum punya supervisor'" />
        <x-stat label="Undangan terkirim" :value="$stats['invited']" unit="supervisor" :tone="$stats['invited'] > 0 ? 'amber' : 'gray'"
                hint="Menunggu supervisor membuat akun" />
        <x-stat label="Supervisor aktif" :value="$stats['active']" unit="orang" tone="green" hint="Punya akun dan mahasiswa" />
        <x-stat label="Magang berjalan" :value="$stats['running']" unit="mahasiswa" tone="gray" :href="route('penempatan.index')" hint="KP Industri dan MBKM" />
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
@endsection
