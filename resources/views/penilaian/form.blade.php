@extends('layouts.app')

@section('title', 'Penilaian '.$internship->student->name)

@section('content')
    <div class="page-head">
        <div>
            <a href="{{ route('penilaian.index') }}" class="back"><x-ikon name="back" :size="16" /> Kembali ke penilaian akhir</a>
            <h1 class="page-title">Penilaian {{ $internship->student->name }}</h1>
            <p class="page-sub">{{ $internship->placeName() }} · standar penilaian kampus</p>
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
                        <h2 class="card-title">Penilaian</h2>
                        <p class="card-sub">Klik satu nilai untuk setiap pertanyaan.</p>
                    </div>
                </div>

                @if ($canEdit)
                    @php($scores = $assessment ? $assessment->scores->keyBy('rubric_aspect_id') : collect())
                    @php($scale = \App\Support\AssessmentStandard::scale())
                    @php($weights = \App\Support\AssessmentStandard::weights())
                    <form method="POST" action="{{ route('penilaian.update', $internship) }}" data-rating-form>
                        @csrf
                        @method('PUT')
                        @foreach (\App\Support\AssessmentStandard::CATEGORIES as $key => [$label, $hint])
                            @php($aspects = $rubric->aspects->where('category', $key)->where('kind', '!=', 'text'))
                            @continue($aspects->isEmpty())
                            <fieldset class="rate-group" data-category="{{ $key }}" data-weight="{{ $weights[$key] ?? 0 }}">
                                <div class="rate-head">
                                    <div>
                                        <legend class="rate-title">{{ $label }}</legend>
                                        <p class="rate-sub">{{ $hint }} · bobot {{ \App\Support\Num::short($weights[$key] ?? 0) }}%</p>
                                    </div>
                                    <div class="rate-score" aria-live="polite">
                                        <strong data-avg>–</strong>
                                        <small>rata-rata</small>
                                    </div>
                                </div>
                                <ol class="rate-list">
                                    @foreach ($aspects as $aspect)
                                        @php($current = old('scores.'.$aspect->id, $scores->get($aspect->id)?->score))
                                        <li class="rate-row rate-row-edit">
                                            <span class="rate-q" id="q-{{ $aspect->id }}">{{ $aspect->name }}@if ($aspect->description)<small>{{ $aspect->description }}</small>@endif</span>
                                            <span class="rate-options" role="radiogroup" aria-labelledby="q-{{ $aspect->id }}" style="--n: {{ count($scale) }}">
                                                @foreach ($scale as $opt)
                                                    <label class="rate-opt" title="{{ $opt['label'] }} ({{ \App\Support\Num::short($opt['value']) }})">
                                                        <input type="radio" name="scores[{{ $aspect->id }}]" value="{{ 0 + $opt['value'] }}"
                                                               @checked($current !== null && abs((float) $current - $opt['value']) < 0.001)>
                                                        <span>{{ $opt['label'] }}</span>
                                                    </label>
                                                @endforeach
                                            </span>
                                        </li>
                                    @endforeach
                                </ol>
                            </fieldset>
                        @endforeach

                        <div class="rate-total">
                            <span>Perkiraan nilai akhir <small data-progress></small></span>
                            <strong data-final>–</strong>
                        </div>

                        <div class="card-body pt">
                            <label for="comment" class="label">Catatan untuk mahasiswa (opsional)</label>
                            <textarea id="comment" name="comment" class="textarea" rows="3" maxlength="2000" placeholder="Kelebihan dan saran pengembangan">{{ old('comment', $assessment?->comment) }}</textarea>
                        </div>

                        <div class="form-actions">
                            <p class="hint">Draft bisa diubah kapan saja. Setelah dikirim, penilaian menunggu validasi dosen pembimbing dan tidak bisa diubah.</p>
                            <div class="row-actions">
                                <button type="submit" name="aksi" value="simpan" class="btn">Simpan draft</button>
                                <button type="submit" name="aksi" value="kirim" class="btn btn-primary">Kirim</button>
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
                        @elseif (! $internship->assessmentIsOpen())
                            Penilaian akhir dibuka mulai {{ $internship->assessmentOpensOn()->locale('id')->translatedFormat('j F Y') }}, menjelang akhir periode KP.
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
