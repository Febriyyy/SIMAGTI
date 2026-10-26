@extends('layouts.app')

@section('title', 'Penilaian Akhir')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Penilaian akhir</h1>
            <p class="page-sub">Diisi di akhir periode KP, lalu dikirim ke dosen pembimbing.</p>
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
                <table class="table dt">
                    <thead>
                        <tr>
                            <th>Mahasiswa</th>
                            <th class="w-date">Selesai KP</th>
                            <th class="w-progress hide-md">Task</th>
                            <th class="w-grade num">Nilai</th>
                            <th class="w-status">Status</th>
                            <th class="w-action" aria-label="Aksi"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($internships as $internship)
                            @php($a = $internship->assessment)
                            @php($open = $internship->assessmentIsOpen())
                            <tr>
                                <td>
                                    <span class="ident">
                                        <x-avatar :user="$internship->student" />
                                        <span>
                                            <span class="c-main" title="{{ $internship->student->name }}">{{ $internship->student->listName() }}</span>
                                            <span class="c-sub">{{ $internship->student->nim }}</span>
                                        </span>
                                    </span>
                                </td>
                                <td>
                                    <span class="c-main c-plain">{{ $internship->end_date->locale('id')->translatedFormat('j M Y') }}</span>
                                    <span class="c-sub">{{ $internship->status === 'berjalan' ? 'Sisa '.$internship->daysLeft().' hari' : 'KP selesai' }}</span>
                                </td>
                                <td class="hide-md"><x-progress :value="$internship->percent()" /></td>
                                <td class="num">
                                    @if ($a?->final_score !== null)
                                        <span class="c-main">{{ $a->letter() }}</span>
                                        <span class="c-sub">{{ \App\Support\Num::short($a->final_score) }}</span>
                                    @else
                                        <span class="muted">–</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($a)
                                        <x-chip :tone="$a->statusTone()">{{ $a->statusLabel() }}</x-chip>
                                    @elseif ($open)
                                        <x-chip tone="amber">Perlu dinilai</x-chip>
                                    @else
                                        <x-chip tone="gray">Belum dibuka</x-chip>
                                    @endif
                                </td>
                                <td class="act">
                                    @php($editable = ! $a || $a->status === 'draft')
                                    @if ($open || $a)
                                        <a href="{{ route('penilaian.edit', $internship) }}" class="btn btn-sm {{ $editable && $open ? 'btn-primary' : '' }}">
                                            {{ ! $a ? 'Nilai' : ($editable ? 'Lanjutkan' : 'Lihat') }}
                                        </a>
                                    @else
                                        <span class="c-sub" title="Penilaian dibuka menjelang akhir periode KP">{{ $internship->assessmentOpensOn()->locale('id')->translatedFormat('j M') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="card-foot">Penilaian dibuka {{ \App\Support\Campus::rule('assessment_open_days') }} hari sebelum KP berakhir. Pertanyaan dan skala nilai mengikuti standar kampus.</p>
        @endif
    </section>
@endsection
