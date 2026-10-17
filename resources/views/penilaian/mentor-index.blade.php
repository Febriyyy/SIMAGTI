@extends('layouts.app')

@section('title', 'Penilaian Akhir')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Penilaian akhir</h1>
            <p class="page-sub">Nilai mahasiswa bimbingan dengan rubrik, lalu kirim ke ketua divisi untuk divalidasi.</p>
        </div>
    </div>

    <section class="card">
        @if ($internships->isEmpty())
            <div class="empty">
                <strong>Belum ada mahasiswa bimbingan</strong>
                Mahasiswa akan muncul di sini setelah penempatan dikonfirmasi.
            </div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            <th>Selesai KP</th>
                            <th>Progres task</th>
                            <th>Nilai</th>
                            <th>Status penilaian</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($internships as $internship)
                            @php($a = $internship->assessment)
                            <tr>
                                <td><x-person :user="$internship->student" /></td>
                                <td>
                                    {{ $internship->end_date->locale('id')->translatedFormat('j M Y') }}
                                    <div class="cell-sub">{{ $internship->status === 'berjalan' ? 'Sisa '.$internship->daysLeft().' hari' : 'KP selesai' }}</div>
                                </td>
                                <td><x-progress :value="$internship->percent()" /></td>
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
                                    @php($editable = ! $a || $a->status === 'draft')
                                    <a href="{{ route('mentor.penilaian.edit', $internship) }}" class="btn btn-sm {{ $editable ? 'btn-primary' : '' }}">
                                        {{ ! $a ? 'Nilai' : ($editable ? 'Lanjutkan' : 'Lihat') }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
