@extends('layouts.app')

@section('title', 'Hasil KP')

@section('content')
    <div class="page-head">
        <div>
            <h1 class="page-title">Hasil KP</h1>
            <p class="page-sub">Setelah penilaian divalidasi dan bimbingan cukup.</p>
        </div>
    </div>

    <section class="card">
        @if ($internships->isEmpty())
            <div class="empty"><strong>Belum ada mahasiswa bimbingan</strong> Mahasiswa muncul di sini setelah KP-nya berjalan.</div>
        @else
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Mahasiswa</th><th>Perusahaan</th><th>Selesai</th><th>Penilaian</th><th>Bimbingan</th><th>Hasil</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($internships as $i)
                            @php($ready = $i->assessment?->isValidated() && $i->confirmed_count >= $min)
                            <tr>
                                <td><x-person :user="$i->student" /></td>
                                <td><span class="truncate" title="{{ $i->placeName() }}">{{ $i->placeName() }}</span></td>
                                <td class="muted">{{ $i->end_date->locale('id')->translatedFormat('j M Y') }}</td>
                                <td><x-chip :tone="$i->assessment?->isValidated() ? 'green' : 'gray'">{{ $i->assessment?->isValidated() ? 'Nilai '.$i->assessment->letter_grade : 'Belum' }}</x-chip></td>
                                <td><x-chip :tone="$i->confirmed_count >= $min ? 'green' : 'gray'">{{ $i->confirmed_count }} / {{ $min }}</x-chip></td>
                                <td>
                                    @if ($i->result)
                                        <x-chip :tone="$i->resultTone()">{{ $i->resultLabel() }}</x-chip>
                                    @else
                                        <span class="muted">–</span>
                                    @endif
                                </td>
                                <td class="cell-actions">
                                    <a href="{{ route('hasil.edit', $i) }}" @class(['btn', 'btn-sm', 'btn-primary' => $ready && ! $i->result])>{{ $i->result ? 'Lihat' : ($ready ? 'Putuskan' : 'Detail') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
