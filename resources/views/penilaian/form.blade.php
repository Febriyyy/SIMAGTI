@extends('layouts.app')

@section('title', 'Penilaian '.$internship->student->name)

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('penilaian.index') }}" class="back"><x-ikon name="back" :size="16" /> Kembali ke penilaian akhir</a>
            <h1 class="page-title">Penilaian {{ $internship->student->name }}</h1>
            <p class="page-sub">{{ $internship->placeName() }} · {{ $rubric?->name ?? 'Rubrik penilaian' }}</p>
        </div>
    </div>

    @if ($assessment?->wasReturned())
        <div class="alert alert-error" role="status">
            <strong>Dikembalikan oleh dosen pembimbing:</strong> {{ $assessment->supervisor_note }}
        </div>
    @endif

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head">
                    <div>
                        <h2 class="card-title">Rubrik penilaian</h2>
                        <p class="card-sub">Nilai 0–100 untuk tiap aspek. Bobot dan pertanyaan ditentukan dosen pembimbing.</p>
                    </div>
                </div>

                @if (! $rubric)
                    <div class="empty">
                        <strong>Belum ada rubrik aktif</strong>
                        Kaprodi perlu mengaktifkan rubrik penilaian terlebih dahulu.
                    </div>
                @elseif ($canEdit)
                    @php($scores = $assessment ? $assessment->scores->keyBy('rubric_aspect_id') : collect())
                    <form method="POST" action="{{ route('penilaian.update', $internship) }}">
                        @csrf
                        @method('PUT')
                        <div class="table-wrap">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Aspek</th>
                                        <th class="col-num">Bobot</th>
                                        <th style="width: 110px">Nilai</th>
                                        <th>Catatan (opsional)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rubric->aspects->where('kind', '!=', 'text') as $aspect)
                                        @php($s = $scores->get($aspect->id))
                                        <tr>
                                            <td class="cell-wrap">
                                                <label for="score-{{ $aspect->id }}" class="cell-title">{{ $aspect->name }}</label>
                                                @if ($aspect->description)
                                                    <div class="cell-sub">{{ $aspect->description }}</div>
                                                @endif
                                            </td>
                                            <td class="col-num">{{ \App\Support\Num::short($aspect->weight) }}%</td>
                                            <td>
                                                <input id="score-{{ $aspect->id }}" type="number" name="scores[{{ $aspect->id }}][score]" class="input input-score"
                                                       min="0" max="100" step="0.5" inputmode="decimal"
                                                       value="{{ old("scores.{$aspect->id}.score", $s && $s->score !== null ? (float) $s->score : '') }}">
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
                        @php($questions = $rubric->aspects->where('kind', 'text'))
                        @if ($questions->isNotEmpty())
                            <div class="card-body pt stack questionnaire">
                                <h3 class="form-section-title">Pertanyaan</h3>
                                @foreach ($questions as $aspect)
                                    @php($s = $scores->get($aspect->id))
                                    <div>
                                        <label for="q-{{ $aspect->id }}" class="label">{{ $loop->iteration }}. {{ $aspect->name }}</label>
                                        @if ($aspect->description)<p class="hint" style="margin: calc(var(--space-1) * -1) 0 var(--space-2)">{{ $aspect->description }}</p>@endif
                                        <textarea id="q-{{ $aspect->id }}" name="scores[{{ $aspect->id }}][note]" class="textarea" rows="3" maxlength="1000">{{ old("scores.{$aspect->id}.note", $s?->note) }}</textarea>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                        <div class="form-actions">
                            <p class="hint">Draft bisa diubah kapan saja. Setelah dikirim, penilaian menunggu validasi dosen pembimbing dan tidak bisa diubah.</p>
                            <div class="btn-row">
                                <button type="submit" name="aksi" value="simpan" class="btn">Simpan draft</button>
                                <button type="submit" name="aksi" value="kirim" class="btn btn-primary">Kirim ke dosen pembimbing</button>
                            </div>
                        </div>
                    </form>
                @else
                    @include('penilaian._scores')
                    <p class="hint form-actions">
                        @if ($assessment?->isValidated())
                            Penilaian sudah divalidasi dan terkunci permanen.
                        @elseif ($assessment?->status === 'submitted')
                            Penilaian dikirim {{ $assessment->submitted_at?->locale('id')->translatedFormat('j M Y, H:i') }} dan sedang menunggu validasi dosen pembimbing.
                        @else
                            Penilaian tidak bisa diubah untuk penempatan ini.
                        @endif
                    </p>
                @endif
            </section>

            @if ($assessment?->isValidated() && $assessment->supervisor_note)
                <section class="card">
                    <div class="card-head"><h2 class="card-title">Catatan dosen pembimbing</h2></div>
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
