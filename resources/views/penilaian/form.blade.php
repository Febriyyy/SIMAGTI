@extends('layouts.app')

@section('title', 'Penilaian '.$internship->student->name)

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('mentor.penilaian') }}" class="back"><x-icon name="back" :size="16" /> Kembali ke penilaian akhir</a>
            <h1 class="page-title">Penilaian {{ $internship->student->name }}</h1>
            <p class="page-sub">{{ $internship->company->name }} · {{ $rubric?->name ?? 'Rubrik penilaian' }}</p>
        </div>
    </div>

    @if ($assessment?->wasReturned())
        <div class="alert alert-error" role="status">
            <strong>Dikembalikan oleh ketua divisi:</strong> {{ $assessment->supervisor_note }}
        </div>
    @endif

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Rubrik penilaian</h2>
                        <p class="card-sub">Nilai 0–100 untuk tiap aspek. Bobot sudah ditentukan kaprodi.</p>
                    </div>
                </div>

                @if (! $rubric)
                    <div class="empty">
                        <strong>Belum ada rubrik aktif</strong>
                        Kaprodi perlu mengaktifkan rubrik penilaian terlebih dahulu.
                    </div>
                @elseif ($canEdit)
                    @php($scores = $assessment ? $assessment->scores->keyBy('rubric_aspect_id') : collect())
                    <form method="POST" action="{{ route('mentor.penilaian.update', $internship) }}">
                        @csrf
                        @method('PUT')
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Aspek</th>
                                        <th>Bobot</th>
                                        <th style="width: 110px">Nilai</th>
                                        <th>Catatan (opsional)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rubric->aspects as $aspect)
                                        @php($s = $scores->get($aspect->id))
                                        <tr>
                                            <td class="cell-wrap">
                                                <label for="score-{{ $aspect->id }}" class="cell-title">{{ $aspect->name }}</label>
                                                @if ($aspect->description)
                                                    <div class="cell-sub">{{ $aspect->description }}</div>
                                                @endif
                                            </td>
                                            <td>{{ \App\Support\Num::short($aspect->weight) }}%</td>
                                            <td>
                                                <input id="score-{{ $aspect->id }}" type="number" name="scores[{{ $aspect->id }}][score]" class="input input-score"
                                                       min="0" max="100" step="0.5" inputmode="decimal"
                                                       value="{{ old("scores.{$aspect->id}.score", $s ? (float) $s->score : '') }}">
                                            </td>
                                            <td>
                                                <input type="text" name="scores[{{ $aspect->id }}][note]" class="input" maxlength="300"
                                                       value="{{ old("scores.{$aspect->id}.note", $s?->note) }}" aria-label="Catatan {{ $aspect->name }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="form-actions">
                            <p class="hint">Draft bisa diubah kapan saja. Setelah dikirim, penilaian menunggu validasi ketua divisi dan tidak bisa diubah.</p>
                            <div class="btn-row">
                                <button type="submit" name="aksi" value="simpan" class="btn">Simpan draft</button>
                                <button type="submit" name="aksi" value="kirim" class="btn btn-primary">Kirim ke ketua divisi</button>
                            </div>
                        </div>
                    </form>
                @else
                    @include('penilaian._scores')
                    <p class="hint form-actions">
                        @if ($assessment?->isValidated())
                            Penilaian sudah divalidasi dan terkunci permanen.
                        @elseif ($assessment?->status === 'submitted')
                            Penilaian dikirim {{ $assessment->submitted_at?->locale('id')->translatedFormat('j M Y, H:i') }} dan sedang menunggu validasi ketua divisi.
                        @else
                            Penilaian tidak bisa diubah untuk penempatan ini.
                        @endif
                    </p>
                @endif
            </section>

            @if ($assessment?->isValidated() && $assessment->supervisor_note)
                <section class="card">
                    <div class="card-head"><h2 class="card-title">Catatan ketua divisi</h2></div>
                    <div class="card-body"><p>{{ $assessment->supervisor_note }}</p></div>
                </section>
            @endif
        </div>

        <div class="col">
            @include('penilaian._summary')
            @include('penilaian._student')
        </div>
    </div>
@endsection
