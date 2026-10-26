@extends('layouts.app')

@section('title', 'Hasil KP '.$internship->student->name)

@section('content')
    @php($a = $internship->assessment)
    <div class="page-head">
        <div class="min0">
            <a href="{{ route('hasil.index') }}" class="back"><x-ikon name="back" :size="16" /> Kembali ke Hasil KP</a>
            <h1 class="page-title">{{ $internship->student->name }}</h1>
            <p class="page-sub">{{ $internship->placeName() }} · {{ $internship->periodLabel() }}</p>
        </div>
        @if ($internship->result)<x-chip :tone="$internship->resultTone()">{{ $internship->resultLabel() }}</x-chip>@endif
    </div>

    <div class="layout">
        <div class="col">
            <section class="card">
                <div class="card-head"><h2 class="card-title">Syarat</h2></div>
                <div class="card-body">
                    <ul class="checks">
                        <li @class(['ok' => $a?->isValidated()])>
                            <x-ikon :name="$a?->isValidated() ? 'check' : 'clock'" :size="18" />
                            Penilaian akhir divalidasi
                            @if ($a?->isValidated())<small>Nilai {{ \App\Support\Num::short($a->final_score) }} ({{ $a->letter_grade }})</small>@endif
                        </li>
                        <li @class(['ok' => $guidance >= $min])>
                            <x-ikon :name="$guidance >= $min ? 'check' : 'clock'" :size="18" />
                            Bimbingan terkonfirmasi {{ $guidance }} dari minimal {{ $min }}
                        </li>
                    </ul>
                </div>
            </section>

            <section class="card">
                <div class="card-head"><h2 class="card-title">Ringkasan kegiatan</h2></div>
                <div class="card-body">
                    <dl class="kv">
                        <div><dt>Rencana kegiatan</dt><dd>{{ $plans }} kegiatan</dd></div>
                        <div><dt>Task selesai</dt><dd>{{ $tasks[0] }} dari {{ $tasks[1] }}</dd></div>
                        <div><dt>Goals tercapai</dt><dd>{{ $goals->whereNotNull('achieved_at')->count() }} dari {{ $goals->count() }}</dd></div>
                    </dl>
                </div>
            </section>

            @if ($a)
                <section class="card">
                    <div class="card-head"><h2 class="card-title">Penilaian Company Supervisor</h2></div>
                    @include('penilaian._scores', ['assessment' => $a, 'rubric' => $a->rubric])
                </section>
            @endif
        </div>

        <div class="col">
            <section class="card">
                <div class="card-head"><h2 class="card-title">Keputusan</h2></div>
                <div class="card-body">
                    @if ($internship->result)
                        <dl class="kv">
                            <div><dt>Hasil</dt><dd>{{ $internship->resultLabel() }}</dd></div>
                            @if ($internship->final_grade)
                                <div><dt>Nilai</dt><dd>{{ $internship->final_grade }}<small>{{ \App\Support\Num::short($internship->final_score) }}</small></dd></div>
                            @endif
                            @if ($internship->result_note)<div><dt>Catatan</dt><dd>{{ $internship->result_note }}</dd></div>@endif
                            <div><dt>Diputuskan</dt><dd>{{ $internship->result_at?->locale('id')->translatedFormat('j F Y') }}</dd></div>
                        </dl>
                    @elseif ($blockers)
                        <div class="alert alert-info" role="status">{{ implode(' ', $blockers) }}</div>
                    @elseif ($internship->status !== 'berjalan')
                        <p class="muted">KP ini sudah ditutup.</p>
                    @else
                        <form method="POST" action="{{ route('hasil.update', $internship) }}" class="stack">
                            @csrf
                            @method('PATCH')
                            <p>Nilai penilaian akhir: <strong>{{ $a->letter_grade }} · {{ \App\Support\Num::short($a->final_score) }}</strong></p>
                            <fieldset class="stack">
                                <legend class="label">Hasil KP</legend>
                                @foreach (\App\Models\Internship::RESULTS as $k => [$label])
                                    <label class="check-row"><input type="radio" name="result" value="{{ $k }}" @checked(old('result') === $k) required> <span>{{ $label }}</span></label>
                                @endforeach
                            </fieldset>
                            <div class="field">
                                <label class="label" for="result_note">Catatan (wajib jika tidak diterima)</label>
                                <textarea id="result_note" name="result_note" class="textarea" rows="3" maxlength="1000">{{ old('result_note') }}</textarea>
                                <p class="hint">Setelah disimpan, KP ditutup dan data tidak bisa diubah lagi.</p>
                            </div>
                            <div class="row-actions"><button type="submit" class="btn btn-primary" onclick="return confirm('Simpan hasil dan tutup KP ini?')"><x-ikon name="check" :size="18" /> Simpan hasil</button></div>
                        </form>
                    @endif
                </div>
            </section>

        </div>
    </div>

@endsection
