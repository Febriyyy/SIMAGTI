@extends('layouts.app')

@section('title', 'Validasi Penilaian')

@section('content')
    @php($waiting = $internships->filter(fn ($i) => $i->assessment?->status === 'submitted')->count())
    <div class="page-head">
        <div>
            <h1 class="page-title">Validasi penilaian</h1>
            <p class="page-sub">Penilaian akhir dari mentor di divisi Anda. Setelah divalidasi, nilai dan huruf mutu terkunci.</p>
        </div>
    </div>

    <div class="stat-grid">
        <x-stat label="Menunggu validasi" :value="$waiting" unit="penilaian" icon="clock"
                :tone="$waiting > 0 ? 'amber' : 'gray'" :hint="$waiting > 0 ? 'Periksa di tabel bawah' : 'Tidak ada antrean'" />
        <x-stat label="Tervalidasi" :value="$internships->filter(fn ($i) => $i->assessment?->isValidated())->count()" unit="penilaian"
                icon="award" tone="green" hint="Nilai sudah dikunci" />
        <x-stat label="Mahasiswa di divisi" :value="$internships->count()" unit="orang" icon="users" hint="Berjalan dan selesai" />
    </div>

    <section class="card">
        @if ($internships->isEmpty())
            <div class="empty">
                <strong>Belum ada mahasiswa di divisi Anda</strong>
                Penilaian akan muncul setelah mentor mengirimnya.
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            <th>Mentor</th>
                            <th>Nilai</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($internships as $internship)
                            @php($a = $internship->assessment)
                            <tr>
                                <td><x-person :user="$internship->student" /></td>
                                <td>
                                    {{ $internship->mentor?->name ?? '–' }}
                                    <div class="cell-sub">{{ $internship->company->name }}</div>
                                </td>
                                <td>
                                    @if ($a?->final_score !== null)
                                        <span class="cell-title">{{ $a->letter() }}</span>
                                        <span class="muted">· {{ \App\Support\Num::short($a->final_score) }}</span>
                                    @else
                                        <span class="muted">–</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($a)
                                        <x-chip :tone="$a->statusTone()">{{ $a->statusLabel() }}</x-chip>
                                    @else
                                        <x-chip tone="gray">Belum dinilai</x-chip>
                                    @endif
                                </td>
                                <td class="col-action">
                                    @if ($a?->status === 'submitted')
                                        <a href="{{ route('divisi.validasi.show', $internship) }}" class="btn btn-sm btn-primary">Periksa</a>
                                    @elseif ($a)
                                        <a href="{{ route('divisi.validasi.show', $internship) }}" class="btn btn-sm">Lihat</a>
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
